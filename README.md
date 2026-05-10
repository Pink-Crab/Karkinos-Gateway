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
- `POST /wp-json/karkinos-gateway/v1/webhooks/github` — receives org-level GitHub webhooks. Verifies `X-Hub-Signature-256` (HMAC SHA-256) against the secret in `KARKINOS_GH_WEBHOOK_SECRET`. Logs every delivery (signature valid or not).

### Settings page

**Settings → Karkinos Gateway** — currently a single field for the local server IP. Persisted as the option `karkinos_gateway_local_server_ip` with autoload disabled via the `Ensure_Settings_Not_Autoloaded` hook.

### Required `wp-config.php` constant

```php
define( 'KARKINOS_GH_WEBHOOK_SECRET', 'paste-the-same-string-you-entered-in-github-here' );
```

The receiver returns 401 if it's missing.

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
