# Licensing & auto-updates (Freemius)

Product: **Plug One Lipa Na M-Pesa** (Freemius id `39188`)  
Helper: `polnmp_fs()` · Free slug `plug-one-lipa-na-m-pesa` · Premium slug `plug-one-lipa-na-m-pesa-premium`

## Free / Pro split

- **Free / no license:** Manual Paybill/Till
- **Pro license:** STK Push, callback auto-complete, Resend STK

## Install SDK

```bash
cd /path/to/plug-one   # or plug-one-lipa-na-m-pesa
composer install
# or:
composer require freemius/wordpress-sdk
```

Ship the `vendor/` directory with the plugin (or run composer on the server).

## Local Freemius test (`wp-config.php` only)

**Never commit these.** Add to the WordPress site `wp-config.php` (above “stop editing”):

```php
define( 'WP_FS__DEV_MODE', true );
define( 'WP_FS__SKIP_EMAIL_ACTIVATION', true );
define( 'WP_FS__plug-one-lipa-na-m-pesa_SECRET_KEY', 'sk_YOUR_SECRET_FROM_FREEMIUS' );
```

Get the secret from Freemius → product → SDK / Keys. If a secret was ever pasted in chat or git, **rotate it** in the dashboard.

Then deactivate/reactivate the plugin and confirm the Freemius license prompt. Generate a test license in Freemius and activate it.

## Optional support URLs

Copy `config/licensing.example.php` → `config/licensing.php` (gitignored) for support email / public docs URL.

## Filters

```php
add_filter( 'plug_one_force_pro', '__return_true' ); // staging unlock
add_filter( 'plug_one_pro_unlocked_without_sdk', '__return_false' ); // lock Pro if SDK missing
```

## Folder name

Freemius expects the plugin directory to match the slug when possible:

- Free / single build: `plug-one-lipa-na-m-pesa`
- Premium build: `plug-one-lipa-na-m-pesa-premium`

Local Docker may use `plug-one`; rename before production packaging if Freemius warns about the path.
