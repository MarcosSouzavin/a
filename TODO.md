# TODO: Fix Cart Bugs

## Completed Tasks
- [x] Identify main bugs: inconsistent localStorage keys, sync issues, frete/address keys, cart counter not updating, persistence problems.

## Completed Tasks
- [x] Standardize localStorage keys: use "cart" for all users, "cart_user" if logged in.
- [x] Simplify cart sync: save to localStorage primarily, optional session via API.
- [x] Standardize frete and endereco keys: use "frete" and "endereco" for all.
- [x] Update cart counter badge on every cart change.
- [x] Ensure correct cart loading in all pages (index.html, cliente.php, checkout.php).
- [x] Update index.html to use standardized keys for guests.
- [x] Update cliente.php to use standardized keys for logged users.
- [x] Update checkout.php to load cart correctly.
- [x] Simplify API/cart.php to optional session sync.

## Summary of Changes
- Unified localStorage keys to avoid confusion.
- Improved cart persistence and counter updates.
- Standardized frete and address handling.

## Next Steps
- [ ] Test adding items to cart and persistence across pages.
- [ ] Verify cart counter updates correctly.
- [ ] Test checkout with proper cart loading.
