# Licensing & auto-updates (Freemius)

Plug One is built for **Freemius**: license keys, checkout, and plugin auto-updates.

## Free / Pro split

- **Free:** Manual Paybill/Till
- **Pro:** STK Push, callback auto-complete, Resend STK

Until Freemius is configured, Pro stays **unlocked** so local/dev keeps working.

## Your setup (product owner)

1. Create a product at [Freemius Dashboard](https://dashboard.freemius.com/).
2. Copy `config/licensing.example.php` → `config/licensing.php`.
3. Paste Freemius **product id** and **public key** (never commit the secret key to git).
4. Set support email + public docs/pricing URLs.
5. Install the SDK:

```bash
cd wp-content/plugins/plug-one
composer require freemius/wordpress-sdk
```

6. Deploy `vendor/` with the plugin (or run composer on the server).
7. Create a paid plan; Freemius handles checkout + updates.

Optional EDD alternative: sell on your site with Software Licensing and replace `Plug_One_Licensing::can_use_pro()` with an EDD license check — same free/pro gate.

## Filters

```php
// Force Pro on a staging site:
add_filter( 'plug_one_force_pro', '__return_true' );

// When Freemius is absent, lock Pro (default is unlocked):
add_filter( 'plug_one_pro_unlocked_without_license', '__return_false' );
```
