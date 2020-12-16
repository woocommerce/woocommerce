# Unreleased

## Fixed

- Missing `config` package dependency

## Added

- `clickFilter()` util helper method that clicks on a list page filter
- `moveAllItemsToTrash()` util helper method that checks every item in a list page and moves them to the trash
- `createSimpleOrder( status )` component which accepts an order status string and creates a basic order with that status
- `addProductToOrder( orderId, productName )` component which adds the provided productName to the passed in orderId
- `createCoupon( couponAmount )` component which accepts a cooupon amount string (it defaults to 5) and creates a basic coupon. Returns the generated coupon code.

## Changes

- `createSimpleOrder( status )` returns the ID of the order that was created

# 0.1.1

- Initial/beta release
