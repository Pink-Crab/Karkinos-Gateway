# Karkinos Gateway

WordPress install that acts as a proxy between GitHub Actions / GitHub webhooks and a home server on a rotating ISP IP.

## What's in here

This repo tracks `wp-content/` only. Two custom pieces:

- **`themes/karkinos-gateway/`** — minimal theme. Picks a random image from `assets/bg/` on each request and renders it full-viewport on a black background. The site is API-only; the theme is what casual visitors see.
- **`mu-plugins/karkinos-gateway/`** — Perique-framework mu-plugin. Houses the settings page, REST endpoints, and webhook logger.

Two upstream mu-plugins are ignored (`mu-plugins/debug-plugin`, `mu-plugins/safety-net`).

## mu-plugin: Karkinos Gateway

Boots a Perique App with the Form Components, Admin Menu, Settings Page, and Route modules. PHP 8.3+.

### Endpoints

- `POST /wp-json/karkinos-gateway/v1/settings/local-server-ip` — updates the stored home-server IP. Auth: WP user with `manage_options` (use a WP application password).
- `POST /wp-json/karkinos-gateway/v1/webhooks/github` — receives org-level GitHub webhooks. Verifies `X-Hub-Signature-256` (HMAC SHA-256) against the secret in `KARKINOS_GH_WEBHOOK_SECRET`. Logs every delivery (signature valid or not), then **gates on the sender** (see below).
- `POST /wp-json/karkinos-gateway/v1/actors/sync` — refreshes the cached authorised-actors roster from the GitHub org members API. Auth: `manage_options`. Returns `{ org, count, synced_at }`.
- `GET /wp-json/karkinos-gateway/v1/actors` — roster health (`{ org, count, synced_at }`). Auth: `manage_options`.
- `POST /wp-json/karkinos-gateway/v1/dispatch/tick` — drains the dispatch queue to Karkinos. Auth: `manage_options`. Returns the run summary `{ sent, rejected, stopped }`.
- `GET /wp-json/karkinos-gateway/v1/dispatch` — backlog visibility (`{ pending }`). Auth: `manage_options`.

### Authorise-or-drop gating

A verified webhook is gated on `sender.login` (GitHub's "who triggered this delivery" — the labeller on a `labeled` action, the commenter on a comment, etc.):

- **Authorised** (the sender is in the cached org-members roster) → a signed envelope is enqueued and one inline dispatch is attempted.
- **Unauthorised / no sender** → nothing is enqueued.

Either way the response is **202**, so the gate is not observable from GitHub's delivery UI. The gate decision (`actor` / `authorised` / `dispatched` / `dispatch_reason`) is recorded in the webhook log. Bot/App actors (e.g. `github-actions[bot]`) are never in an org-members roster and are dropped (roster-only).

### What actually triggers a forward

Four things, and nothing else:

| Trigger | Event | Goes to |
|---|---|---|
| An exact `[karkinos] …` label added to an issue or PR | `issues` / `pull_request`, action `labeled` | Karkinos |
| A PR's checks finishing | `check_suite`, action `completed`, attached to a PR | Karkinos |
| A PR opened, reopened, or pushed to | `pull_request`, action `opened` / `reopened` / `synchronize` | Actions tool |
| A release published on an org `*_stubs` repo | `release`, action `published` | Blog stubs-section rebuild |

The first and third are **actor-gated**; the second is a bot-sent system event, so the PR + completion condition is the gate instead. The fourth is condition-gated the same way — publishing a release on an org repo already requires write access, so repo ownership is the gate.

Gating PR runs on `sender.login` is what stops an outsider burning compute on the home server: GitHub sets `sender` to the opener on `opened` and to the **pusher** on `synchronize`, so a stranger opening a PR — or pushing more commits to their own — is dropped both times. A PR opened by a member whose commits were *authored* by a non-member does run; a member pushing it is the vouch.

### Dispatch model

The queue has two states only, on one column: `dispatched_at` is NULL (not sent) or set (handled). There is **no local single-flight lock** — one-at-a-time is enforced by Karkinos answering its capacity probe (`GET /dispatch/capacity` → `{"available":true|false}`) and holding its own host-wide lock. Each tick: ask "are you busy?" → if free, take the oldest undispatched job, POST the signed envelope to Karkinos, stamp `dispatched_at`. A `2xx` (incl. a deduped `{"duplicate":true}`) or a permanent `4xx` reject stamps the job; a `429`/`5xx`/transport error leaves it NULL to retry on a later tick. A process that dies mid-send simply leaves the job undispatched — the next tick re-sends it (Karkinos dedupes on the delivery id).

Outbound TLS is **pinned to the home server's self-signed cert by identity, not hostname** (the IP rotates): `sslverify` stays on with `KARKINOS_DISPATCH_CA` as the pinned cert; only the hostname match is disabled (`Karkinos_TLS_Pinning`).

#### Job kinds

Every job carries a `kind` naming the protocol used to deliver it:

- **`karkinos`** — capacity probe, HMAC-signed envelope, pinned self-signed cert, to the rotating home-server IP. The default, and what every pre-existing row is treated as.
- **`act`** — `POST {"url": "<PR html_url>"}` to the Actions tool at `KARKINOS_ACT_URL`, HTTP basic auth, ordinary TLS verification. The tool sits behind a Cloudflare Tunnel with a real certificate and a stable hostname, so there is no IP to track and nothing to pin.
- **`blog`** — not a forward: the job is a signal to rebuild the stubs section of the blog post at `KARKINOS_BLOG_URL`. The sync lists the org's `*_stubs` repos and their tags from GitHub, renders the section (versions newest-first, linked to their releases, with the `composer require` line), and rewrites everything between the `<!-- stub-forge:start -->` / `<!-- stub-forge:end -->` markers in the post via the WP REST API (application password, basic auth). Nothing outside the markers is touched, and a rebuild is total + idempotent — queued releases coalesce, backfilled versions land in the right order, and manual edits inside the markers are overwritten. A post with no markers permanently rejects the job (synthesised 422) instead of retrying forever.

A tick only offers the queue the kinds whose target is currently configured, so an unconfigured — or busy — target leaves its own jobs queued instead of blocking everything behind them. Karkinos answering "busy" stops it being offered more work for that tick while act and blog jobs keep draining.

The Actions tool clones the PR on demand and runs whatever workflows its head contains, so the gateway needs no knowledge of any repo's workflow files.

### Driving it (external cron on the home server)

The gateway never self-schedules. The home server's crontab drives both jobs by hitting the endpoints (WP application password, `manage_options`):

```cron
* * * * *   curl -fsS -u user:app-pass -X POST https://<gateway>/wp-json/karkinos-gateway/v1/dispatch/tick   # drain the queue
17 * * * *  curl -fsS -u user:app-pass -X POST https://<gateway>/wp-json/karkinos-gateway/v1/actors/sync     # refresh the roster
```

### Settings page

**Settings → Karkinos Gateway** — currently a single field for the local server IP. Persisted as the option `karkinos_gateway_local_server_ip` with autoload disabled via the `Ensure_Settings_Not_Autoloaded` hook.

### `wp-config.php` constants

```php
// Inbound: verifies GitHub's X-Hub-Signature-256. Receiver returns 401 if missing.
define( 'KARKINOS_GH_WEBHOOK_SECRET', 'paste-the-same-string-you-entered-in-github-here' );

// Roster sync: PAT with org-members read (read:org). Sync no-ops + keeps the
// existing roster if missing. Org defaults to Pink-Crab.
define( 'KARKINOS_GH_API_TOKEN', 'github_pat_...' );
// define( 'KARKINOS_GH_ORG', 'Pink-Crab' ); // optional

// Dispatch: shared secret signing the forward body (Karkinos reads the same
// value from /etc/karkinos/dispatch.secret) + the pinned home-server cert.
define( 'KARKINOS_DISPATCH_SECRET', 'shared-secret' );
define( 'KARKINOS_DISPATCH_CA', '/path/on/gateway/karkinos-ca.pem' );

// Optional full-URL overrides. Unset = derived from the rotating local_server_ip
// setting: https://{local_server_ip}/dispatch and .../dispatch/capacity.
// define( 'KARKINOS_DISPATCH_URL', 'https://82.15.236.87/dispatch' );
// define( 'KARKINOS_CAPACITY_URL', 'https://82.15.236.87/dispatch/capacity' );

// Actions tool (act runner) behind the Cloudflare Tunnel. All three are
// required together — a partial set is treated as unconfigured and act jobs
// stay queued rather than being sent unauthenticated.
define( 'KARKINOS_ACT_URL', 'https://tools.pinkcrab.co.uk/actions/api.php' );
define( 'KARKINOS_ACT_USER', 'basic-auth-user' );
define( 'KARKINOS_ACT_PASS', 'basic-auth-password' );

// Blog stubs-section sync. All four are required together — a partial set is
// treated as unconfigured and blog jobs stay queued. The user/pass pair is a
// WP application password on the blog with edit rights on the post.
define( 'KARKINOS_BLOG_URL', 'https://glynnquelch.co.uk' );
define( 'KARKINOS_BLOG_USER', 'wp-username' );
define( 'KARKINOS_BLOG_PASS', 'application-password' );
define( 'KARKINOS_BLOG_POST_ID', 6731 );
// define( 'KARKINOS_BLOG_REST_BASE', 'software' ); // optional, REST base of the post's type (default 'posts')
// define( 'KARKINOS_BLOG_VENDOR', 'pinkcrab' );    // optional, composer vendor prefix
```

Check the Actions tool is reachable from the gateway (read-only, queues nothing):

```bash
wp eval 'foreach(["KARKINOS_ACT_URL","KARKINOS_ACT_USER","KARKINOS_ACT_PASS"] as $c){if(!defined($c)){echo "MISSING $c\n";$bad=1;}} if(!empty($bad))exit(1); $r=wp_remote_get(KARKINOS_ACT_URL."?a=state",["timeout"=>15,"headers"=>["Authorization"=>"Basic ".base64_encode(KARKINOS_ACT_USER.":".KARKINOS_ACT_PASS)]]); echo is_wp_error($r)?"ERR: ".$r->get_error_message():wp_remote_retrieve_response_code($r)." ".substr(wp_remote_retrieve_body($r),0,400),PHP_EOL;'
```

Export the pinned cert from the home server once (re-pin only if Karkinos regenerates its cert — IP rotation does not require it):

```bash
openssl s_client -connect 82.15.236.87:443 </dev/null 2>/dev/null | openssl x509 > /path/on/gateway/karkinos-ca.pem
```

### Webhook logs

JSONL, one line per delivery, written to `wp-content/karkinos-gateway-logs/<YYYY-MM-DD>-<random-hex>.jsonl`:

- Directory created on first write with mode `0700`.
- An `index.php` blocker (Silence is golden) is dropped in to defeat directory listing on Apache. Nginx requires a separate `location` rule in server config.
- Filenames carry a 12-char hex suffix (random per day) so the URL can't be guessed externally. Date → filename map is held in the option `karkinos_gateway_webhook_log_files` (NOT autoloaded — the filenames are effectively secret).

## Install

```bash
cd mu-plugins/karkinos-gateway
composer install
```

The mu-loader (`mu-plugins/mu-loader.php`) picks up subdirectories with a plugin header automatically.

## Tests

PHPUnit + WP-PHPUnit. DB credentials in `mu-plugins/karkinos-gateway/tests/.env` (defaults assume devilbox MariaDB on `127.0.0.1`; copy `.env_sample` if missing).

```bash
cd mu-plugins/karkinos-gateway
composer test       # phpunit + clover
composer analyse    # phpstan
composer sniff      # phpcs (WordPress-Extra)
composer all        # all three
```

## Repo layout

```
wp-content/
├── .gitignore                                   # editor/IDE, .claude, assets, upstream mu-plugins, build artefacts
├── README.md
├── themes/
│   └── karkinos-gateway/                        # random-bg full-viewport theme
└── mu-plugins/
    ├── mu-loader.php                            # scans subdirs for plugin-header files
    └── karkinos-gateway/                        # the Perique mu-plugin
        ├── karkinos-gateway.php                 # entry — autoload + App boot
        ├── composer.json
        ├── phpunit.xml.dist, phpstan.neon.dist, phpcs.xml, .gitattributes
        ├── config/
        │   ├── di.php                           # DI container rules
        │   ├── settings.php                     # App_Config
        │   └── registration.php                 # Hookable class list
        ├── src/
        │   ├── Settings/                        # Gateway_Settings, Gateway_Settings_Page, Ensure_Settings_Not_Autoloaded
        │   ├── Rest/                            # Settings_Routes, Webhook_Routes
        │   └── Logging/                         # Webhook_Logger
        └── tests/                               # Integration tests against WP-PHPUnit
```
