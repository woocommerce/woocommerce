[![Create Todo list](https://raw.githubusercontent.com/senadir/todo-my-markdown/master/public/github-button.svg?sanitize=true)](https://git-todo.netlify.app/create)

# Cart Items

## Setup

- You will need an item that is [sold individually](https://docs.woocommerce.com/wp-content/uploads/2016/06/disable-stock-mgmt.png).
- You will need a low stock item with a [low threshold quantity](https://docs.woocommerce.com/wp-content/uploads/2016/06/simpleproduct-inventory.png) below the stock quantity.
- You will need a low stock item with a [low threshold quantity](https://docs.woocommerce.com/wp-content/uploads/2016/06/simpleproduct-inventory.png) above 0 and a stock quantity below the threshold.

## What to test

- [ ] You should be able to add items to your cart.
- [ ] You should be able to change item quantity in your Cart.
- [ ] You should not be able to change "sold individually" items quantity.
- [ ] Items that have quantity lower than the threshold should show "x Left in stock".
	- [ ] You should not be able to increase that item quantity to above that is left in stock.
- [ ] If you try to increase an item quantity to above its stock quantity, you get an error.
- [ ] You should be able to remove an item.