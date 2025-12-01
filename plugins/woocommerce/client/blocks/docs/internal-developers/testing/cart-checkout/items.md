# Cart Items <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

- [Setup](#setup)
- [What to test](#what-to-test)

## Setup

-   You will need an item that is [sold individually](https://docs.woocommerce.com/wp-content/uploads/2016/06/disable-stock-mgmt.png).
-   You will need a low stock item with a [low threshold quantity](https://docs.woocommerce.com/wp-content/uploads/2016/06/simpleproduct-inventory.png) below the stock quantity.
-   You will need a low stock item with a [low threshold quantity](https://docs.woocommerce.com/wp-content/uploads/2016/06/simpleproduct-inventory.png) above 0 and a stock quantity below the threshold.

## What to test

-   [ ] You should be able to add items to your cart.
-   [ ] You should be able to change item quantity in your Cart.
-   [ ] You should not be able to change "sold individually" items quantity.
-   [ ] Items that have quantity lower than the threshold should show "x Left in stock". - [ ] You should not be able to increase that item quantity to above that is left in stock.
-   [ ] If you try to increase an item quantity to above its stock quantity, you get an error. **Note:** This is not something that can be tested with a single browser instance. To test you need to do the following:
    -   [ ] Set a stock of 4 on an item.
    -   [ ] Open tabs in two different browsers (so you have two different sessions in play).
    -   [ ] In both browsers add 1 of that item into the cart.
    -   [ ] In both browsers, load the cart (block).
    -   [ ] In one browser, increase the quantity of that item to the maximum you can.
    -   [ ] In the other browser, try increasing the quantity. An error should appear.
-   [ ] You should be able to remove an item.

[![Create Todo list](https://raw.githubusercontent.com/senadir/todo-my-markdown/master/public/github-button.svg?sanitize=true)](https://git-todo.netlify.app/create)
