# ePasskeys

Passkeys (WebAuthn) for Evolution CMS 3.5.x. Manager-first, Blade integration, no vendor lock-in.

## Requirements
- PHP 8.3+
- Evolution CMS 3.5.2+
- HTTPS (or `localhost` in dev)

## Install
From the `core` directory of your Evo site:

```bash
php artisan package:installrequire evolution-cms/epasskeys "*"
```

## Publish config and assets
```bash
php artisan vendor:publish --provider="EvolutionCMS\\ePasskeys\\ePasskeysServiceProvider" --tag=epasskeys-config
php artisan vendor:publish --provider="EvolutionCMS\\ePasskeys\\ePasskeysServiceProvider" --tag=epasskeys-assets
```

## Build Tailwind CSS (manager UI)
```bash
php artisan tailwind:build epasskeys
php artisan vendor:publish --provider="EvolutionCMS\\ePasskeys\\ePasskeysServiceProvider" --tag=epasskeys-assets
```

Optional (views override):
```bash
php artisan vendor:publish --provider="EvolutionCMS\\ePasskeys\\ePasskeysServiceProvider" --tag=epasskeys-views
```

Optional (translations override):
```bash
php artisan vendor:publish --provider="EvolutionCMS\\ePasskeys\\ePasskeysServiceProvider" --tag=epasskeys-lang
```

## Migrate
```bash
php artisan migrate
```

## SimpleWebAuthn bundle
The package includes the `@simplewebauthn/browser` UMD build in:
```
public/assets/plugins/ePasskeys/js/simplewebauthn.umd.js
```
Update this file if you want a newer version of the library.

## Usage
### Manager login
Once installed and assets published, the login form will show **“Sign in with Passkey”**.
- Uses the existing `rememberme` checkbox for persistent sessions.
- If WebAuthn is not supported, the button is disabled.

### Manage passkeys (manager)
Open (relative to manager URL):
```
{MODX_MANAGER_URL}/webauthn/credentials
```
Create/delete passkeys for the current manager user.
The menu item appears in **Tools** if the manager role has the `epasskeys` permission.
The migration `2026_01_22_000002_add_epasskeys_permissions.php` creates this permission and assigns it to role ID 1 (admin).

## Config
Config file:
```
core/custom/config/cms/settings/ePasskeys.php
```

Key settings:
- `enable`: global on/off
- `contexts.mgr.enable`: manager passkeys (default true)
- `contexts.web.enable`: optional web context (default false)
- `relying_party.id`: RP ID (defaults to current host; cannot be IP)
- `relying_party.allowed_origins`: strict whitelist (array or comma-separated string)

System settings overrides:
- `epasskeys_enable`, `epasskeys_enable_mgr`, `epasskeys_enable_web`
- `epasskeys_rp_id`, `epasskeys_rp_name`, `epasskeys_allowed_origins`

## Routes (manager)
Prefix: `webauthn` under `{MODX_MANAGER_URL}`

- `GET  {MODX_MANAGER_URL}/webauthn/auth/options`
- `POST {MODX_MANAGER_URL}/webauthn/auth`
- `GET  {MODX_MANAGER_URL}/webauthn/register/options`
- `POST {MODX_MANAGER_URL}/webauthn/register`
- `GET  {MODX_MANAGER_URL}/webauthn/credentials`
- `POST {MODX_MANAGER_URL}/webauthn/credentials/{id}/delete`

## Security notes
- WebAuthn requires HTTPS or `localhost`.
- `rp_id` must be a domain (not an IP).
- Challenges are stored in session and are one-time use.
- Recommended rate limits:
  - options: 10 req/min/IP
  - auth: 5 req/min/IP
- WebAuthn flows rely on cookies/sessions (SameSite/Secure settings must allow session cookies).

## Events
- `OnPasskeyRegistered`
- `OnPasskeyAuthenticated`
- `OnPasskeyDeleted`

Payload includes: `context`, `user_id`, `passkey_id`, masked `credential_id`, `ip`, `user_agent`.

## Troubleshooting
- **No button on login**: publish assets, verify `public/assets/plugins/ePasskeys/js/*` exists.
- **Invalid origin**: configure `relying_party.allowed_origins` or correct RP ID.
- **Passkey not working on local**: use `localhost`, not an IP.

## License
MIT (see `LICENSE`).
