# TODO: Fix Admin Products Display and Persistence

## Completed Tasks
- [x] Assign proper IDs to products with null or invalid IDs on load
- [x] Fix new product ID calculation to handle mixed types
- [x] Update 'produtos' tab to display all products (pizzas, drinks, juices) instead of filtering out drinks and juices
- [x] Adjust price display in 'produtos' tab to handle single-price items (drinks/juices) and multi-size items (pizzas)

## Summary of Changes
- Modified `js/admin.js` to assign numeric IDs to products missing or invalid IDs during initialization.
- Updated the ID generation logic for new products to correctly find the maximum numeric ID.
- Changed the 'produtos' tab rendering to show all menu items without filtering, ensuring drinks and juices are visible alongside pizzas.
- Improved price display logic to show appropriate pricing based on item type (single price for drinks/juices, size-based for pizzas).

## Next Steps
- Test the admin interface to verify all products are displayed and can be edited/deleted properly.
- Ensure new additions persist correctly after page reloads.
