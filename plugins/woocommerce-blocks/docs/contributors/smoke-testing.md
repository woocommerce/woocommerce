# Smoke Testing

When testing builds the following things should be tested to ensure critical parts
of the Blocks plugin are still functional.

## Prerequisites

To make future testing more efficient, we recommend setting up some Blocks in advance so you can repeat tests on them whenever smoke testing.

1. Have a page with all regular and SSR blocks (such as the product grids) setup and configured.
2. Have a page with the All Products Block, and some Filter Blocks, setup to test that functionality in isolation. Using the columns block here too is a good idea to keep things organized.

## Editor Tests

1. Ensure all WooCommerce Blocks are shown in the Block Inserter.
2. Check existing Blocks;
    - Do they look correct?
    - Can you change options/attributes in the Block inspector?
    - Are changes persisted on save?
    - Is the Browser error console free from errors/notices/warnings?
3. Test inserting various blocks into the editor;
    - All Products Blocks (this is powered by the Store API)
    - Featured Product (this is powered by the REST API)
    - On Sale Products (this is SSR)
    - Is the Browser error console free from errors/notices/warnings after inserting them?
    - Do they persist and continue to display correctly after save/refresh?

## Frontend Tests

1. Do the blocks on your pre-made pages render correctly?
2. Test interaction with blocks, such as the All Products Block and filters.
3. Is the Browser error console free from errors/notices/warnings?
