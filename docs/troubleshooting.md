# Troubleshooting

| Symptom | Fix |
|---|---|
| `Invalid TransactionType` | Use **Paybill** with sandbox `174379`. Till needs Buy Goods + real Till. |
| Could not start M-Pesa payment | Check order note + `plug-one-mpesa` log (OAuth, passkey, amount). |
| Callback loops in ngrok | URL must include `/wc-api/plug_one_mpesa/` and return 200 JSON. |
| Order is Processing after pay | Success for physical products. |
| Order stuck Pending | Callback unreachable; use Query status; verify HTTPS / ngrok. |
| STK option missing / forced manual | Set Payment mode to STK Push and save Daraja credentials. |
| Gateway hidden at checkout | Currency must be KES; enable gateway; for STK need API credentials. |
| `sendmail: not found` in Docker | Ignore — emails only; payments still work. |

Logs: **WooCommerce → Status → Logs → `plug-one-mpesa`**.
