# Paybill vs Till

## Paybill (`CustomerPayBillOnline`)

- Customer pays to your **Paybill** number.
- Safaricom **sandbox** shortcode `174379` uses this type only.
- In Plug One: Transaction type → **Paybill**. Party B = shortcode (or blank).

## Till / Buy Goods (`CustomerBuyGoodsOnline`)

- Customer pays to a **Till** (Buy Goods).
- Often **Party B (Till) ≠ Business shortcode**.
- No public Till STK sandbox like `174379`. Needs a real Till after Daraja **Go Live**.
- In Plug One: Transaction type → **Buy Goods / Till**, set Party B to the Till number.

## Manual mode

Shows Paybill/Till on checkout; customer pays in the M-Pesa app; order stays **On hold** until you confirm (or use Simulate in sandbox). No Daraja STK required.
