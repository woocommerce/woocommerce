# Default Categories

Routes are matched top-to-bottom. First match wins.
More specific patterns should come before more general ones.

## API Version 4 (v4)

|                                                     |                              |
|-----------------------------------------------------|------------------------------|
| /wc/v4/products/.*/variations(/.*)?                 | v4/Products/Variations       |
| /wc/v4/products(/.*)?                               | v4/Products                  |
| /wc/v4/orders(/.*)?                                 | v4/Orders                    |
| /wc/v4/order-notes(/.*)?                            | v4/Orders/Notes              |
| /wc/v4/customers(/.*)?                              | v4/Customers                 |
| /wc/v4/refunds(/.*)?                                | v4/Refunds                   |
| /wc/v4/fulfillments(/.*)?                           | v4/Fulfillments              |
| /wc/v4/settings/emails(/.*)?                        | v4/Settings/Emails           |
| /wc/v4/settings/payment-gateways(/.*)?              | v4/Settings/Payment Gateways |
| /wc/v4/settings(/.*)?                               | v4/Settings                  |
| /wc/v4/shipping-zones(/.*)?                         | v4/Shipping/Zones            |
| /wc/v4/shipping-zone-method(/.*)?                   | v4/Shipping/Zone Methods     |

## API Version 3 (v3)

|                                                     |                              |
|-----------------------------------------------------|------------------------------|
| /wc/v3/products/.*/variations(/.*)?                 | v3/Products/Variations       |
| /wc/v3/products/attributes/.*/terms(/.*)?           | v3/Products/Attribute Terms  |
| /wc/v3/products/attributes(/.*)?                    | v3/Products/Attributes       |
| /wc/v3/products/categories(/.*)?                    | v3/Products/Categories       |
| /wc/v3/products/tags(/.*)?                          | v3/Products/Tags             |
| /wc/v3/products/brands(/.*)?                        | v3/Products/Brands           |
| /wc/v3/products/shipping_classes(/.*)?              | v3/Products/Shipping Classes |
| /wc/v3/products/reviews(/.*)?                       | v3/Products/Reviews          |
| /wc/v3/products(/.*)?                               | v3/Products                  |
| /wc/v3/orders/.*/notes(/.*)?                        | v3/Orders/Notes              |
| /wc/v3/orders/.*/refunds(/.*)?                      | v3/Orders/Refunds            |
| /wc/v3/orders(/.*)?                                 | v3/Orders                    |
| /wc/v3/customers/.*/downloads(/.*)?                 | v3/Customers/Downloads       |
| /wc/v3/customers(/.*)?                              | v3/Customers                 |
| /wc/v3/coupons(/.*)?                                | v3/Coupons                   |
| /wc/v3/refunds(/.*)?                                | v3/Refunds                   |
| /wc/v3/variations(/.*)?                             | v3/Variations                |
| /wc/v3/reports(/.*)?                                | v3/Reports                   |
| /wc/v3/settings(/.*)?                               | v3/Settings                  |
| /wc/v3/payment_gateways(/.*)?                       | v3/Payment Gateways          |
| /wc/v3/shipping_methods(/.*)?                       | v3/Shipping/Methods          |
| /wc/v3/shipping/zones/.*/methods(/.*)?              | v3/Shipping/Zone Methods     |
| /wc/v3/shipping/zones/.*/locations(/.*)?            | v3/Shipping/Zone Locations   |
| /wc/v3/shipping/zones(/.*)?                         | v3/Shipping/Zones            |
| /wc/v3/taxes/classes(/.*)?                          | v3/Taxes/Classes             |
| /wc/v3/taxes(/.*)?                                  | v3/Taxes                     |
| /wc/v3/webhooks(/.*)?                               | v3/Webhooks                  |
| /wc/v3/system_status/tools(/.*)?                    | v3/System Status/Tools       |
| /wc/v3/system_status(/.*)?                          | v3/System Status             |
| /wc/v3/data/continents(/.*)?                        | v3/Data/Continents           |
| /wc/v3/data/countries(/.*)?                         | v3/Data/Countries            |
| /wc/v3/data/currencies(/.*)?                        | v3/Data/Currencies           |
| /wc/v3/data(/.*)?                                   | v3/Data                      |
| /wc/v3/layout-templates(/.*)?                       | v3/Layout Templates          |
| /wc/v3/marketplace(/.*)?                            | v3/Marketplace               |

## API Version 2 (v2)

|                                                     |                              |
|-----------------------------------------------------|------------------------------|
| /wc/v2/products/.*/variations(/.*)?                 | v2/Products/Variations       |
| /wc/v2/products/.*/reviews(/.*)?                    | v2/Products/Reviews          |
| /wc/v2/products/attributes/.*/terms(/.*)?           | v2/Products/Attribute Terms  |
| /wc/v2/products/attributes(/.*)?                    | v2/Products/Attributes       |
| /wc/v2/products/categories(/.*)?                    | v2/Products/Categories       |
| /wc/v2/products/tags(/.*)?                          | v2/Products/Tags             |
| /wc/v2/products/brands(/.*)?                        | v2/Products/Brands           |
| /wc/v2/products/shipping_classes(/.*)?              | v2/Products/Shipping Classes |
| /wc/v2/products(/.*)?                               | v2/Products                  |
| /wc/v2/orders/.*/notes(/.*)?                        | v2/Orders/Notes              |
| /wc/v2/orders/.*/refunds(/.*)?                      | v2/Orders/Refunds            |
| /wc/v2/orders(/.*)?                                 | v2/Orders                    |
| /wc/v2/customers/.*/downloads(/.*)?                 | v2/Customers/Downloads       |
| /wc/v2/customers(/.*)?                              | v2/Customers                 |
| /wc/v2/coupons(/.*)?                                | v2/Coupons                   |
| /wc/v2/reports(/.*)?                                | v2/Reports                   |
| /wc/v2/settings(/.*)?                               | v2/Settings                  |
| /wc/v2/payment_gateways(/.*)?                       | v2/Payment Gateways          |
| /wc/v2/shipping_methods(/.*)?                       | v2/Shipping/Methods          |
| /wc/v2/shipping/zones/.*/methods(/.*)?              | v2/Shipping/Zone Methods     |
| /wc/v2/shipping/zones/.*/locations(/.*)?            | v2/Shipping/Zone Locations   |
| /wc/v2/shipping/zones(/.*)?                         | v2/Shipping/Zones            |
| /wc/v2/taxes/classes(/.*)?                          | v2/Taxes/Classes             |
| /wc/v2/taxes(/.*)?                                  | v2/Taxes                     |
| /wc/v2/webhooks(/.*)?                               | v2/Webhooks                  |
| /wc/v2/system_status/tools(/.*)?                    | v2/System Status/Tools       |
| /wc/v2/system_status(/.*)?                          | v2/System Status             |

## API Version 1 (v1)

|                                                     |                              |
|-----------------------------------------------------|------------------------------|
| /wc/v1/products/.*/reviews(/.*)?                    | v1/Products/Reviews          |
| /wc/v1/products/attributes/.*/terms(/.*)?           | v1/Products/Attribute Terms  |
| /wc/v1/products/attributes(/.*)?                    | v1/Products/Attributes       |
| /wc/v1/products/categories(/.*)?                    | v1/Products/Categories       |
| /wc/v1/products/tags(/.*)?                          | v1/Products/Tags             |
| /wc/v1/products/shipping_classes(/.*)?              | v1/Products/Shipping Classes |
| /wc/v1/products(/.*)?                               | v1/Products                  |
| /wc/v1/orders/.*/notes(/.*)?                        | v1/Orders/Notes              |
| /wc/v1/orders/.*/refunds(/.*)?                      | v1/Orders/Refunds            |
| /wc/v1/orders(/.*)?                                 | v1/Orders                    |
| /wc/v1/customers/.*/downloads(/.*)?                 | v1/Customers/Downloads       |
| /wc/v1/customers(/.*)?                              | v1/Customers                 |
| /wc/v1/coupons(/.*)?                                | v1/Coupons                   |
| /wc/v1/reports(/.*)?                                | v1/Reports                   |
| /wc/v1/taxes/classes(/.*)?                          | v1/Taxes/Classes             |
| /wc/v1/taxes(/.*)?                                  | v1/Taxes                     |
| /wc/v1/webhooks(/.*)?                               | v1/Webhooks                  |
| /wc/v1/connect(/.*)?                                | v1/Connect                   |
| /wc/v1/marketplace(/.*)?                            | v1/Marketplace               |

## Store API

|                                                     |                              |
|-----------------------------------------------------|------------------------------|
| /wc/store/v1/cart/coupons(/.*)?                     | Store/v1/Cart/Coupons        |
| /wc/store/v1/cart/items(/.*)?                       | Store/v1/Cart/Items          |
| /wc/store/v1/cart(/.*)?                             | Store/v1/Cart                |
| /wc/store/v1/checkout(/.*)?                         | Store/v1/Checkout            |
| /wc/store/v1/order(/.*)?                            | Store/v1/Order               |
| /wc/store/v1/products/attributes(/.*)?              | Store/v1/Products/Attributes |
| /wc/store/v1/products/brands(/.*)?                  | Store/v1/Products/Brands     |
| /wc/store/v1/products/categories(/.*)?              | Store/v1/Products/Categories |
| /wc/store/v1/products(/.*)?                         | Store/v1/Products            |
| /wc/store/v1(/.*)?                                  | Store/v1                     |
| /wc/store/cart/coupons(/.*)?                        | Store/Cart/Coupons           |
| /wc/store/cart/items(/.*)?                          | Store/Cart/Items             |
| /wc/store/cart(/.*)?                                | Store/Cart                   |
| /wc/store/checkout(/.*)?                            | Store/Checkout               |
| /wc/store/order(/.*)?                               | Store/Order                  |
| /wc/store/products/attributes(/.*)?                 | Store/Products/Attributes    |
| /wc/store/products/brands(/.*)?                     | Store/Products/Brands        |
| /wc/store/products/categories(/.*)?                 | Store/Products/Categories    |
| /wc/store/products(/.*)?                            | Store/Products               |
| /wc/store(/.*)?                                     | Store                        |

## Other WooCommerce Routes

|                                                     |                              |
|-----------------------------------------------------|------------------------------|
| /wc/private(/.*)?                                   | Private                      |
| /wc(/.*)?                                           | Other                        |
