# WooCommerce Enumerators <!-- omit in toc -->

This directory contains enumerators used in the WooCommerce plugin. Enumerators are used to define a set of named constants, which can be used to represent a set of possible values.

The enum classes make it easier to reference string values and avoid typos. They also make the code stricter, make it easier to find the usage of the possible values, centralize them, improve their documentation, and many other advantages that should help developers create related code.

## Available Enumerators

- [CatalogVisibility](./CatalogVisibility.php) - Enumerates the possible catalog visibility options for a product.
- [CurrencyPosition](./CurrencyPosition.php) - Enumerates the possible values of the `woocommerce_currency_pos` option.
- [DefaultCustomerAddress](./DefaultCustomerAddress.php) - Enumerates the possible values of the `woocommerce_default_customer_address` option.
- [DimensionUnit](./DimensionUnit.php) - Enumerates the possible values of the `woocommerce_dimension_unit` option.
- [OrderInternalStatus](./OrderInternalStatus.php) - Enumerates the possible internal statuses of an order (when stored in the database).
- [OrderItemType](./OrderItemType.php) - Enumerates the possible types of an order line item.
- [OrderStatus](./OrderStatus.php) - Enumerates the possible statuses of an order.
- [PaymentGatewayFeatures](./PaymentGatewayFeatures.php) - Enumerates the possible features of a payment gateway.
- [ProductStatus](./ProductStatus.php) - Enumerates the possible statuses of a product.
- [ProductStockStatus](./ProductStockStatus.php) - Enumerates the possible stock statuses of a product.
- [ProductType](./ProductType.php) - Enumerates the possible types of a product.
- [TaxBasedOn](./TaxBasedOn.php) - Enumerates the possible values of the `woocommerce_tax_based_on` option.
- [TaxDisplayMode](./TaxDisplayMode.php) - Enumerates the possible values of the `woocommerce_tax_display_shop` and `woocommerce_tax_display_cart` options.
- [WeightUnit](./WeightUnit.php) - Enumerates the possible values of the `woocommerce_weight_unit` option.

## Contributing

The WooCommerce plugin contains many string values that still need to be converted to enumerators. Feel free to contribute by creating new classes.
