# Licensing & auto-updates (Freemius)

Product: **Plug One Lipa Na M-Pesa** (Freemius id `39188`)  
Helper: `polnmp_fs()` · Free slug `plug-one-lipa-na-m-pesa` · Premium slug `plug-one-lipa-na-m-pesa-premium`

## Free / Pro split

- **Free / no license / WordPress.org:** Manual Paybill/Till (STK code stripped from free ZIP)
- **Pro license (premium ZIP):** STK Push, callback auto-complete, Resend STK

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

Local Pro without license (QA only):

```php
add_filter( 'plug_one_force_pro', '__return_true' );
```

If Composer/SDK is missing, Pro stays **locked**. To unlock without SDK (dev only):

```php
add_filter( 'plug_one_pro_unlocked_without_sdk', '__return_true' );
```

## Optional support URLs

Copy `config/licensing.example.php` → `config/licensing.php` (gitignored) for support email / public docs URL.

## Filters

```php
add_filter( 'plug_one_force_pro', '__return_true' ); // staging unlock
add_filter( 'plug_one_pro_unlocked_without_sdk', '__return_true' ); // unlock Pro if SDK missing (dev only)
```

## Folder name

Freemius expects the plugin directory to match the slug when possible:

- Free / single build: `plug-one-lipa-na-m-pesa`
- Premium build: `plug-one-lipa-na-m-pesa-premium`

Local Docker may use `plug-one`; rename before production packaging if Freemius warns about the path.

## List / sell on Freemius

1. Freemius Dashboard → **Plans**: Free + Pro pricing.
2. Package: `./bin/package-for-freemius.sh` → upload `dist/plug-one-lipa-na-m-pesa.zip`.
3. Release the version → share free / Pro checkout links.

## WordPress.org

See **[wordpress-org.md](wordpress-org.md)** for the full publish checklist.  
Short version: deploy on Freemius → download the **generated free ZIP** → submit that to wordpress.org (not the premium ZIP).
