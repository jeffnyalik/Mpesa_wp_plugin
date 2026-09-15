# Publish on WordPress.org (free) + Freemius (Pro)

## Current review status

- **2026-09-10:** Submitted (old name/slug) — automated pass, then human/AI review email (13 Sep) with trademark + locked-features + packaging issues.
- **1.5.0:** Addresses review: rename, Manual-only free package, sanitization, Daraja verify, external services, no PHPUnit, contributors `jeffnyake`.

### Reply checklist

1. Update Freemius product slug to `plug-one-payment-gateway-m-pesa` (if possible).
2. `./bin/package-for-freemius.sh` → Freemius Deployment → Released.
3. Download Freemius **free** ZIP only → upload on wordpress.org “Add your plugin” while logged in as **jeffnyake**.
4. Reply on the **same** review email thread using `docs/review-reply-draft.txt`.

Requested slug: `plug-one-payment-gateway-m-pesa`  
Display name: **Plug One Payment Gateway for M-Pesa**

## After approval (SVN)

1. SVN credentials from WordPress.org.
2. Push free build to `/trunk` and tag `/tags/1.5.0`.
3. Live URL: `https://wordpress.org/plugins/plug-one-payment-gateway-m-pesa/`

## Links

- Free Freemius plan: https://checkout.freemius.com/plugin/39188/plan/65728/
- Pro Freemius plan: https://checkout.freemius.com/plugin/39188/plan/65736/
- Guidelines: https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
