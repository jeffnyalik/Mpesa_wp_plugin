# Callbacks & HTTPS

Safaricom must reach your site over **public HTTPS**.

## Correct URL

```text
https://YOUR-DOMAIN/wc-api/plug_one_mpesa/
```

Wrong (will loop or 404):

```text
https://YOUR-DOMAIN/plug_one_mpesa/
https://YOUR-DOMAIN/Bsing/mpesa/callback   ← Nest app, not this plugin
```

## Local development

```bash
ngrok http 8080
```

Set **Callback URL override** in gateway settings to:

```text
https://YOUR-SUBDOMAIN.ngrok-free.app/wc-api/plug_one_mpesa/
```

Confirm in ngrok: one `POST` → **200** → body `{"ResultCode":0,"ResultDesc":"Accepted"}`.

Plug One ACKs first, then processes the order (Action Scheduler / shutdown) so Safaricom does not retry forever.

## REST fallback

```text
https://YOUR-DOMAIN/wp-json/plug-one/v1/mpesa/callback
```
