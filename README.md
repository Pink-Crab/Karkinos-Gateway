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

### Dispatch model

The queue has two states only, on one column: `dispatched_at` is NULL (not sent) or set (handled). There is **no local single-flight lock** — one-at-a-time is enforced by Karkinos answering its capacity probe (`GET /dispatch/capacity` → `{"available":true|false}`) and holding its own host-wide lock. Each tick: ask "are you busy?" → if free, take the oldest undispatched job, POST the signed envelope to Karkinos, stamp `dispatched_at`. A `2xx` (incl. a deduped `{"duplicate":true}`) or a permanent `4xx` reject stamps the job; a `429`/`5xx`/transport error leaves it NULL to retry on a later tick. A process that dies mid-send simply leaves the job undispatched — the next tick re-sends it (Karkinos dedupes on the delivery id).

Outbound TLS is **pinned to the home server's self-signed cert by identity, not hostname** (the IP rotates): `sslverify` stays on with `KARKINOS_DISPATCH_CA` as the pinned cert; only the hostname match is disabled (`Karkinos_TLS_Pinning`).

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
