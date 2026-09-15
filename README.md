# Plug One Payment Gateway for M-Pesa

Open-source WooCommerce payment gateway for **M-Pesa** (Kenya).

**Author:** [Jeff Nyalik](https://github.com/jeffnyalik)  
**License:** [GPL-2.0-or-later](LICENSE)

> Independent third-party software. **Not** affiliated with, endorsed by, or sponsored by Safaricom PLC or Automattic.  
> “M-Pesa”, “Safaricom”, “Lipa Na M-Pesa”, and “Daraja” are trademarks of their respective owners.

## Features

| Feature | Free (this repo / free build) | Pro (Freemius premium package) |
|---|---|---|
| Manual Paybill / Till checkout | Yes | Yes |
| Kenyan phone validation | Yes | Yes |
| Checkout Blocks + HPOS | Yes | Yes |
| Daraja STK Push | — | Yes |
| Auto-complete from Safaricom callback | — | Yes |
| Resend STK / status query | — | Yes |

## Requirements

- WordPress 6.0+
- WooCommerce 8.0+
- Store currency: **KES**
- PHP 7.4+
- For Pro STK: Safaricom Daraja app + public HTTPS

## Install (from GitHub)

1. Download or clone this repository into `wp-content/plugins/plug-one-payment-gateway-m-pesa` (or any folder name).
2. From the plugin folder:
   ```bash
   composer install --no-dev
   ```
3. Activate in WordPress → Plugins.
4. WooCommerce → Settings → Payments → **Plug One Payment Gateway for M-Pesa**.
5. Enter your Paybill/Till and enable the gateway.

### Releases / Freemius builds

Packaged ZIPs (with Composer vendor + Freemius SDK):

- Free download: [Freemius free plan](https://checkout.freemius.com/plugin/39188/plan/65728/)
- Pro / license: [Freemius Pro plan](https://checkout.freemius.com/plugin/39188/plan/65736/)

```bash
./bin/package-for-freemius.sh   # builds dist/*.zip for Freemius upload
```

## Local development

See [LOCAL.md](LOCAL.md) for Docker WordPress + WooCommerce.

```bash
./bin/setup-local.sh
# Site: http://localhost:8080  (admin / admin)
```

## Documentation

Bundled docs in [`docs/`](docs/):

- [Getting started](docs/getting-started.md)
- [Paybill vs Till](docs/paybill-vs-till.md)
- [Callbacks & HTTPS](docs/callbacks.md)
- [Licensing (Freemius)](docs/licensing.md)
- [Troubleshooting](docs/troubleshooting.md)
- [Support](docs/support.md)

## External services

- **Safaricom Daraja** (Pro STK): OAuth, STK Push, status query — [developer docs](https://developer.safaricom.co.ke/docs), [privacy](https://www.safaricom.co.ke/privacy-statement)
- **Freemius** (optional licenses / Pro delivery): [terms](https://freemius.com/terms/), [privacy](https://freemius.com/privacy/)

## Support

- Email: jeffnyak@gmail.com  
- Phone / WhatsApp: 0716431039  

## WordPress.org

Directory listing is optional and currently paused in favour of GitHub + Freemius distribution. Notes: [docs/wordpress-org.md](docs/wordpress-org.md).

## Contributing

Issues and pull requests are welcome. Keep secrets out of the repo (`config/licensing.php` is gitignored; Freemius secret keys belong in `wp-config.php` only).
