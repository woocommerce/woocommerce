# WooCommerce Enumerators <!-- omit in toc -->

This directory contains enumerators used in the WooCommerce plugin. Enumerators are used to define a set of named constants, which can be used to represent a set of possible values.

The enum classes make it easier to reference string values and avoid typos. They also make the code stricter, make it easier to find the usage of the possible values, centralize them, improve their documentation, and many other advantages that should help developers create related code.

## Available Enumerators

- [CatalogSortOrder](./CatalogSortOrder.php) - Enumerates the possible values of the `woocommerce_default_catalog_orderby` option.
- [CatalogVisibility](./CatalogVisibility.php) - Enumerates the possible catalog visibility options for a product.
- [CurrencyPosition](./CurrencyPosition.php) - Enumerates the possible values of the `woocommerce_currency_pos` option.
- [DefaultCustomerAddress](./DefaultCustomerAddress.php) - Enumerates the possible values of the `woocommerce_default_customer_address` option.
- [DimensionUnit](./DimensionUnit.php) - Enumerates the possible values of the `woocommerce_dimension_unit` option.
- [FeaturePluginCompatibility](./FeaturePluginCompatibility.php) - Enumerates the possible default compatibility values between a plugin and a feature.
- [OrderInternalStatus](./OrderInternalStatus.php) - Enumerates the possible internal statuses of an order (when stored in the database).
- [OrderItemType](./OrderItemType.php) - Enumerates the possible types of an order line item.
- [OrderStatus](./OrderStatus.php) - Enumerates the possible statuses of an order.
- [PaymentGatewayFeature](./PaymentGatewayFeature.php) - Enumerates the possible features of a payment gateway.
- [ProductStatus](./ProductStatus.php) - Enumerates the possible statuses of a product.
- [ProductStockStatus](./ProductStockStatus.php) - Enumerates the possible stock statuses of a product.
- [ProductTaxStatus](./ProductTaxStatus.php) - Enumerates the possible tax statuses of a product.
- [ProductType](./ProductType.php) - Enumerates the possible types of a product.
- [StockDisplayFormat](./StockDisplayFormat.php) - Enumerates the possible values of the `woocommerce_stock_format` option.
- [TaxBasedOn](./TaxBasedOn.php) - Enumerates the possible values of the `woocommerce_tax_based_on` option.
- [TaxDisplayMode](./TaxDisplayMode.php) - Enumerates the possible values of the `woocommerce_tax_display_shop` and `woocommerce_tax_display_cart` options.
- [WeightUnit](./WeightUnit.php) - Enumerates the possible values of the `woocommerce_weight_unit` option.

## Where an enumerator belongs

The plugin declares enumerated vocabularies in three places. Which one to use depends on who consumes the values.

- **`src/Enums/` (this directory)** is the default. Use it for any vocabulary that is persisted, passed through a hook, or referenced from more than one module — order statuses, product types, settings option values.
- **A module-local `Enums/` sub-namespace** is acceptable when the vocabulary belongs to a single internal module and nothing outside it consumes the values, as in `src/Internal/StockNotifications/Enums/`. Move it here once a second module needs it.
- **`src/Api/Enums/`** holds native PHP 8.1 backed enums for the Code API, which requires PHP 8.1 and so cannot be referenced from the rest of the plugin. When a concept exists both there and here, the two must stay in step; `EnumParityTest` fails when they drift.

## Conventions for a new enumerator

- One `final` class per concept, no behavior, in this directory's namespace.
- Explicit `public` visibility and a docblock on every constant.
- Add it to the list above. `EnumsReadmeTest` fails when a class in this directory is missing from it.
- Native PHP enums are not an option here: the minimum supported PHP version is 7.4, and the raw string values are the contract persisted in databases and consumed by extensions.

## Constants name values; they never change them

The string is the contract and the constant is a permanent alias for it. Never change a constant's value, and never rename or remove one — these constants are public API that extensions rely on. Deprecate instead, and introduce the replacement alongside.

## Contributing

The WooCommerce plugin contains many string values that still need to be converted to enumerators. Feel free to contribute by creating new classes.
