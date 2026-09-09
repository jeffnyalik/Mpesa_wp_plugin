# Production Till checklist

Use this when you have a real Safaricom **Buy Goods Till** (not sandbox `174379`).

## Before go-live

- [ ] Business Till / shortcode issued by Safaricom
- [ ] Daraja app **Go Live** approved for Lipa Na M-Pesa Online
- [ ] Production Consumer key / secret and **passkey**
- [ ] Site on HTTPS with valid certificate
- [ ] Pretty permalinks enabled
- [ ] WooCommerce currency = **KES**
- [ ] Plug One **Pro** license active (STK)

## Gateway settings

| Field | Value |
|---|---|
| Environment | Production |
| Payment mode | STK Push |
| Transaction type | Buy Goods / Till |
| Shortcode | Business shortcode (password / BusinessShortCode) |
| Party B | **Till number** |
| Callback | `https://your-live-domain/wc-api/plug_one_mpesa/` |

## Test plan

1. **Test Daraja credentials** (OAuth must succeed against `api.safaricom.co.ke`).
2. Place a **KES 1–10** order with your Safaricom line.
3. Confirm STK prompt → PIN → order **Processing** + receipt in order notes.
4. In Safaricom / bank portal, confirm funds to the Till.
5. Retry checkout quickly → only one STK (idempotency).
6. Check logs: WooCommerce → Status → Logs → `plug-one-mpesa`.

## Rollback

If live STK fails, switch Payment mode to **Manual** temporarily and show the Till number at checkout while you debug.
