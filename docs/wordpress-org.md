# Publish on WordPress.org (free) + Freemius (Pro)

Goal: people find **Plug One** by searching WordPress plugins; Pro/STK is sold and updated via Freemius.

## What we already prepared in code (v1.4.1+)

- `is_org_compliant` => `true`
- STK UI / initiate / Resend STK wrapped with Freemius `__premium_only` helpers (stripped from free ZIP)
- Free default mode = Manual Paybill/Till
- Pro stays locked if Freemius SDK is missing (local unlock: `plug_one_force_pro` filter)
- `Tested up to: 7.1`, text domain / Plugin Name slug `plug-one-lipa-na-m-pesa`, `languages/` folder

## Your step-by-step checklist

### A. Redeploy on Freemius (required before .org)

1. Package:
   ```bash
   cd ~/work_space/wp_plugs/plug_one
   ./bin/package-for-freemius.sh
   ```
2. Freemius Dashboard → **Deployment** → **Add New Version** → upload `dist/plug-one-lipa-na-m-pesa.zip`.
3. Wait for processing → set release to **Released**.
4. Download the **free** ZIP Freemius generated (not your local zip).
5. Spot-check free ZIP on a clean WP site:
   - Manual mode works
   - No STK payment mode (or STK not runnable)
   - Freemius opt-in / upgrade still appears
6. Spot-check **premium** ZIP or upgrade path: Pro license → STK works.

### B. WordPress.org account

1. Create / log in: https://wordpress.org/support/register/
2. Complete profile (display name, etc.).

### C. Submit the plugin for review

1. Open: https://wordpress.org/plugins/developers/add/
2. Upload **only** the Freemius-generated **free** ZIP.
3. Suggested slug (if available): `plug-one-lipa-na-m-pesa` (must match Freemius free slug when possible).
4. Fill description honestly: free = Manual; Pro via Freemius upgrade.
5. Keep trademark disclaimer (not affiliated with Safaricom).
6. Submit and wait for review email (often several days to a few weeks).

**Submitted 2026-09-10:** v1.4.2, slug `plug-one-lipa-na-m-pesa`, status **Awaiting Review**. Email: `jeffnyak@gmail.com` — subject will be `[WordPress Plugin Directory] Review in Progress: Plug One Lipa Na M-Pesa` (check spam; whitelist `plugins@wordpress.org`). Do **not** submit another plugin until this one is reviewed.

### D. After approval (SVN)

1. WordPress.org emails SVN credentials.
2. Check out the plugin SVN repo.
3. Put the free build in `/trunk` (and tag `/tags/1.4.2`).
4. Commit. Plugin goes live on wordpress.org/plugins/plug-one-lipa-na-m-pesa/

For later updates: bump version → Freemius deploy → download new **free** ZIP → commit to SVN trunk + new tag.

### E. After it is live

1. Search wordpress.org for “M-Pesa WooCommerce” / your plugin name.
2. Install from a real site → upgrade with [Pro checkout](https://checkout.freemius.com/plugin/39188/plan/65736/).
3. Share the .org URL in Kenya WooCommerce groups (discovery + trust).

## Do not submit

- Your raw repo zip with Pro STK still fully present and only license-gated
- `config/licensing.php` with private overrides (example file is fine)
- Docker / tests / LOCAL.md (already excluded by `bin/package-for-freemius.sh`; Freemius free ZIP is preferred)

## Links

- Free download (Freemius): https://checkout.freemius.com/plugin/39188/plan/65728/
- Pro checkout: https://checkout.freemius.com/plugin/39188/plan/65736/
- Add plugin: https://wordpress.org/plugins/developers/add/
- Freemius deployment docs: https://freemius.com/help/documentation/wordpress/deployment-process/
