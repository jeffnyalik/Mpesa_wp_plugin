# Secrets & security

## Never commit

- `config/licensing.php` (use `licensing.example.php` only)
- Daraja Consumer secret / passkey
- Freemius **secret** key (`sk_…`)
- `.env` files
- ngrok auth tokens
- Real customer phone numbers in screenshots/logs shared publicly

## Safe to commit

- `config/licensing.example.php` with placeholders
- Public Freemius key only after you understand it is public
- Docs without live credentials

## Checklist

- [ ] `LOCAL.md` has no live keys
- [ ] `config/licensing.php` is gitignored
- [ ] Production uses HTTPS
- [ ] Rotate any keys that were ever pasted into chat or git history
