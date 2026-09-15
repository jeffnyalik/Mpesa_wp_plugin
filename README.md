# Plug One Payment Gateway for M-Pesa

Open-source WooCommerce payment gateway for **M-Pesa** (Kenya): Manual Paybill/Till **and** Daraja STK Push.

**Author:** [Jeff Nyalik](https://github.com/jeffnyalik)  
**License:** [GPL-2.0-or-later](LICENSE)

> Independent third-party software. **Not** affiliated with, endorsed by, or sponsored by Safaricom PLC or Automattic.  
> “M-Pesa”, “Safaricom”, “Lipa Na M-Pesa”, and “Daraja” are trademarks of their respective owners.

## Features

- Manual Paybill / Till checkout
- Daraja STK Push (PIN prompt) + callback auto-complete
- Resend STK / query status / sandbox simulate
- Kenyan phone validation (`07…` / `2547…`)
- Checkout Blocks + HPOS
- Transaction logging helpers

## Requirements

- WordPress 6.0+
- WooCommerce 8.0+
- Store currency: **KES**
- PHP 7.4+
- For STK: Safaricom Daraja app + public HTTPS

## Install

```bash
git clone https://github.com/jeffnyalik/Mpesa_wp_plugin.git wp-content/plugins/plug-one-payment-gateway-m-pesa
```

Or download a release ZIP:

```bash
./bin/package-release.sh
# → dist/plug-one-payment-gateway-m-pesa.zip
```

Activate in WordPress → Plugins, then configure under **WooCommerce → Settings → Payments**.

## Local development

See [LOCAL.md](LOCAL.md).

```bash
./bin/setup-local.sh
# http://localhost:8080  (admin / admin)
```

## Documentation

- [Getting started](docs/getting-started.md)
- [Paybill vs Till](docs/paybill-vs-till.md)
- [Callbacks & HTTPS](docs/callbacks.md)
- [Troubleshooting](docs/troubleshooting.md)
- [Support](docs/support.md)

## External services

**Safaricom Daraja** is used for OAuth, STK Push, and status query when you enable STK mode and configure credentials.

- [Developer docs](https://developer.safaricom.co.ke/docs)
- [Privacy](https://www.safaricom.co.ke/privacy-statement)

## Support

- Email: jeffnyak@gmail.com  
- Phone / WhatsApp: 0716431039  

## Contributing

Issues and PRs welcome. Keep secrets out of the repo (`config/licensing.php` is gitignored for local contact overrides).
