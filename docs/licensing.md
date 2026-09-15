# Licensing & auto-updates (Freemius)

Product id: **39188** (`polnmp_fs`)  
WordPress.org / free slug: `plug-one-payment-gateway-m-pesa`  
Premium slug: `plug-one-payment-gateway-m-pesa-premium`

**Important:** After the WordPress.org rename, update the Freemius product **slug** in the Freemius Developer Dashboard to match `plug-one-payment-gateway-m-pesa` (and premium slug), or contact Freemius support if the dashboard slug cannot be edited. The SDK `slug` must match.

## Free / Pro split

- **WordPress.org free package:** Manual Paybill/Till only (STK / Daraja client stripped)
- **Freemius Pro package:** STK Push, callback auto-complete, Resend STK

## Install SDK

```bash
composer install --no-dev
```

## Local Freemius test (`wp-config.php` only)

```php
define( 'WP_FS__DEV_MODE', true );
define( 'WP_FS__SKIP_EMAIL_ACTIVATION', true );
define( 'WP_FS__plug-one-payment-gateway-m-pesa_SECRET_KEY', 'sk_YOUR_SECRET_FROM_FREEMIUS' );
```

(Previous constant used `plug-one-lipa-na-m-pesa` — update wp-config after the rename.)

## Filters

```php
add_filter( 'plug_one_force_pro', '__return_true' ); // staging unlock on premium package
```

## Deploy

```bash
./bin/package-for-freemius.sh
```

Upload `dist/plug-one-payment-gateway-m-pesa.zip` to Freemius → Deployment.  
For WordPress.org, upload Freemius’s **generated free** ZIP (not the local premium source zip).

See [wordpress-org.md](wordpress-org.md) and [review-reply-draft.txt](review-reply-draft.txt).
