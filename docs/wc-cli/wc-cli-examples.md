---
post_title: WooCommerce CLI Examples
menu_title: Examples
tags: reference
---

Full documentation for every command is available using `--help`. Below are some example commands to show what the CLI can do.

All the examples below use user ID 1 (usually an admin account), but you should replace that with your own user account.

You can also find other examples (without output) by looking at [the testing files for our CLI tests](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/cli/features).

Each command will have a `.feature` file. For example, [these some payment gateway commands](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/cli/features/payment_gateway.feature).

## Clearing the product/shop transients cache

Command: 

`$ wp wc tool run clear_transients --user=1`

Response:

`Success: Updated system_status_tool clear_transients.`

## Listing all system tools

Command: 

`$ wp wc tool list --user=1`

Response:

```bash
+----------------------------+----------------------------------+-------------------------------+-----------------------------------------------------------------------------------+
| id                         | name                             | action                        | description                                                                       |
+----------------------------+----------------------------------+-------------------------------+-----------------------------------------------------------------------------------+
| clear_transients           | WC transients                    | Clear transients              | This tool will clear the product/shop transients cache.                           |
| clear_expired_transients   | Expired transients               | Clear expired transients      | This tool will clear ALL expired transients from WordPress.                       |
| delete_orphaned_variations | Orphaned variations              | Delete orphaned variations    | This tool will delete all variations which have no parent.                        |
| recount_terms              | Term counts                      | Recount terms                 | This tool will recount product terms - useful when changing your settings in a wa |
|                            |                                  |                               | y which hides products from the catalog.                                          |
| reset_roles                | Capabilities                     | Reset capabilities            | This tool will reset the admin, customer and shop_manager roles to default. Use t |
|                            |                                  |                               | his if your users cannot access all of the WooCommerce admin pages.               |
| clear_sessions             | Customer sessions                | Clear all sessions            | <strong class="red">Note:</strong> This tool will delete all customer session dat |
|                            |                                  |                               | a from the database, including any current live carts.                            |
| install_pages              | Install WooCommerce pages        | Install pages                 | <strong class="red">Note:</strong> This tool will install all the missing WooComm |
|                            |                                  |                               | erce pages. Pages already defined and set up will not be replaced.                |
| delete_taxes               | Delete all WooCommerce tax rates | Delete ALL tax rates          | <strong class="red">Note:</strong> This option will delete ALL of your tax rates, |
|                            |                                  |                               |  use with caution.                                                                |
| reset_tracking             | Reset usage tracking settings    | Reset usage tracking settings | This will reset your usage tracking settings, causing it to show the opt-in banne |
|                            |                                  |                               | r again and not sending any data.                                                 |
+----------------------------+----------------------------------+-------------------------------+-----------------------------------------------------------------------------------+
```

## Creating a customer

Command:

`$ wp wc customer create --email='woo@woo.local' --user=1 --billing='{"first_name":"Bob","last_name":"Tester","company":"Woo", "address_1": "123 Main St.", "city":"New York", "state:": "NY", "country":"USA"}' --shipping='{"first_name":"Bob","last_name":"Tester","company":"Woo", "address_1": "123 Main St.", "city":"New York", "state:": "NY", "country":"USA"}' --password='hunter2' --username='mrbob' --first_name='Bob' --last_name='Tester'`

Response:

`Success: Created customer 17.`

## Getting a customer in CSV format

Command:

`$ wp wc customer get 17 --user=1 --format=csv`

Response:

```bash
Field,Value
id,17
date_created,2016-12-09T20:22:10
date_modified,2016-12-09T20:22:10
email,woo@woo.local
first_name,Bob
last_name,Tester
role,customer
username,mrbob
billing,"{""first_name"":""Bob"",""last_name"":""Tester"",""company"":""Woo"",""address_1"":""123 Main St."",""address_2"":"""",""city"":""New York"",""state"":"""",""postcode"":"""","
"country"":""USA"",""email"":"""",""phone"":""""}"
shipping,"{""first_name"":""Bob"",""last_name"":""Tester"",""company"":""Woo"",""address_1"":""123 Main St."",""address_2"":"""",""city"":""New York"",""state"":"""",""postcode"":"""",
""country"":""USA""}"
is_paying_customer,false
meta_data,"[{""id"":825,""key"":""shipping_company"",""value"":""Woo""},{""id"":829,""key"":""_order_count"",""value"":""0""},{""id"":830,""key"":""_money_spent"",""value"":""0""}]"
orders_count,0
total_spent,0.00
avatar_url,http://2.gravatar.com/avatar/5791d33f7d6472478c0b5fa69133f09a?s=96
```

## Adding a customer note on order 355

Command:

`$ wp wc order_note create 355 --note="Great repeat customer" --customer_note=true --user=1`

Response:

`Success: Created order_note 286.`

## Getting an order note

Command:

`$ wp wc order_note get 355 286 --user=1`

Response:

```bash
+---------------+-----------------------+
| Field         | Value                 |
+---------------+-----------------------+
| id            | 286                   |
| date_created  | 2016-12-09T20:27:26   |
| note          | Great repeat customer |
| customer_note | true                  |
+---------------+-----------------------+
```

## Updating a coupon

Command:

`$ wp wc shop_coupon update 45 --amount='10' --discount_type='percent' --free_shipping=true --user=1`

Response:

`Success: Updated shop_coupon 45.`

## Getting a coupon

Command:

`$ wp wc shop_coupon get 45 --user=1`

Response:

```bash
+-----------------------------+---------------------+
| Field                       | Value               |

+-----------------------------+---------------------+
| id                          | 45                  |
| code                        | hello               |
| amount                      | 10.00               |
| date_created                | 2016-08-09T17:37:28 |
| date_modified               | 2016-12-09T20:30:32 |
| discount_type               | percent             |
| description                 | Yay                 |
| date_expires                | 2016-10-22T00:00:00 |
| usage_count                 | 2                   |
| individual_use              | false               |
| product_ids                 | []                  |
| excluded_product_ids        | []                  |
| usage_limit                 | null                |
| usage_limit_per_user        | null                |
| limit_usage_to_x_items      | null                |
| free_shipping               | true                |
| product_categories          | []                  |
| excluded_product_categories | []                  |
| exclude_sale_items          | false               |
| minimum_amount              | 0.00                |
| maximum_amount              | 0.00                |
| email_restrictions          | []                  |
| used_by                     | ["1","1"]           |
| meta_data                   | []                  |
+-----------------------------+---------------------+
```
