# WC CLI: overview

WooCommerce CLI (WC-CLI) offers the ability to manage WooCommerce (WC) via the command-line, using WP CLI. The documentation here covers the version of WC CLI that started shipping in WC 3.0.0 and later.

WC CLI is powered by the [WC REST API](https://woocommerce.github.io/woocommerce-rest-api-docs/), meaning most of what is possible with the REST API can also be achieved via the command-line.

_If you're looking for documentation on the [WC 2.5 and 2.6's CLI go here](https://github.com/woocommerce/woocommerce/wiki/Legacy-CLI-commands-(v2.6-and-below))._

## What is WP-CLI?

For those who have never heard before WP-CLI, here's a brief description extracted from the [official website](http://wp-cli.org/).

> **WP-CLI** is a set of command-line tools for managing WordPress installations. You can update plugins, set up multisite installs and much more, without using a web browser.

## WooCommerce Commands

A full listing of WC-CLI commands and their accepted arguments can be found on the [commands page](https://github.com/woocommerce/woocommerce/wiki/WC-CLI-Commands).

All WooCommerce-related commands are grouped into `wp wc` command. The available commands (as of WC 3.0) are:

```bash
$ wp wc
usage: wp wc customer <command>
   or: wp wc customer_download <command>
   or: wp wc order_note <command>
   or: wp wc payment_gateway <command>
   or: wp wc product <command>
   or: wp wc product_attribute <command>
   or: wp wc product_attribute_term <command>
   or: wp wc product_cat <command>
   or: wp wc product_review <command>
   or: wp wc product_shipping_class <command>
   or: wp wc product_tag <command>
   or: wp wc product_variation <command>
   or: wp wc shipping_method <command>
   or: wp wc shipping_zone <command>
   or: wp wc shipping_zone_location <command>
   or: wp wc shipping_zone_method <command>
   or: wp wc shop_coupon <command>
   or: wp wc shop_order <command>
   or: wp wc shop_order_refund <command>
   or: wp wc tax <command>
   or: wp wc tax_class <command>
   or: wp wc tool <command>
   or: wp wc webhook <command>
   or: wp wc webhook_delivery <command>

See 'wp help wc <command>' for more information on a specific command.
```

**Note**: When using the commands, you must specify your username or user ID using the `--user` argument. This is to let the REST API know which user should be used.

You can see more details about the commands using `wp help wc` or with the `--help` flag, which explains arguments and subcommands.

Example:

`wp wc customer --help`

```bash
NAME

  wp wc customer

SYNOPSIS

  wp wc customer <command>

SUBCOMMANDS

  create      Create a new item.
  delete      Delete an existing item.
  get         Get a single item.
  list        List all items.
  update      Update an existing item.
```

`wp wc customer list --help`

```bash
NAME

  wp wc customer list

DESCRIPTION

  List all items.

SYNOPSIS

  wp wc customer list [--context=<context>] [--page=<page>]
  [--per_page=<per_page>] [--search=<search>] [--exclude=<exclude>]
  [--include=<include>] [--offset=<offset>] [--order=<order>]
  [--orderby=<orderby>] [--email=<email>] [--role=<role>] [--fields=<fields>]
  [--field=<field>] [--format=<format>]


OPTIONS

  [--context=<context>]
    Scope under which the request is made; determines fields present in
    response.

  [--page=<page>]
    Current page of the collection.

  [--per_page=<per_page>]
    Maximum number of items to be returned in result set.

  [--search=<search>]
    Limit results to those matching a string.

  [--exclude=<exclude>]
    Ensure result set excludes specific IDs.

  [--include=<include>]
    Limit result set to specific IDs.

  [--offset=<offset>]
    Offset the result set by a specific number of items.

  [--order=<order>]
    Order sort attribute ascending or descending.

  [--orderby=<orderby>]
    Sort collection by object attribute.

  [--email=<email>]
    Limit result set to resources with a specific email.

  [--role=<role>]
    Limit result set to resources with a specific role.

  [--fields=<fields>]
    Limit response to specific fields. Defaults to all fields.

  [--field=<field>]
    Get the value of an individual field.

  [--format=<format>]
    Render response in a particular format.
    ---
    default: table
    options:
      - table
      - json
      - csv
      - ids
      - yaml
      - count
      - headers
      - body
      - envelope
    ---
```

Arguments like `--context`, `--fields`, `--field`, `--format` can be used on any `get` or `list` WC CLI command.

The `--porcelain` argument can be used on any `create` or `update` command to just get back the ID of the object, instead of a response.

Updating or creating some fields will require passing JSON. These are fields that contain arrays of information — for example, setting [https://woocommerce.github.io/woocommerce-rest-api-docs/#customer-properties](billing information) using the customer command. This is just passing key/value pairs.

Example:

`$ wp wc customer create --email='me@woo.local' --user=1 --billing='{"first_name":"Justin","last_name":"S","company":"Automattic"}' --password='he
llo'`

`Success: Created customer 16.`

`$ wp wc customer get 16 --user=1`

```bash
+--------------------+----------------------------------------------------------------------------------------------------------------------------------------------------------------+
| id                 | 16                                                                                                                                                             |
| date_created       | 2016-12-09T20:07:35                                                                                                                                            |
| date_modified      | 2016-12-09T20:07:35                                                                                                                                            |
| email              | me@woo.local                                                                                                                                                   |
| first_name         |                                                                                                                                                                |
| last_name          |                                                                                                                                                                |
| role               | customer                                                                                                                                                       |
| username           | me                                                                                                                                                             |
| billing            | {"first_name":"Justin","last_name":"S","company":"Automattic","address_1":"","address_2":"","city":"","state":"","postcode":"","country":"","email":"","phone" |
|                    | :""}                                                                                                                                                           |
| shipping           | {"first_name":"","last_name":"","company":"","address_1":"","address_2":"","city":"","state":"","postcode":"","country":""}                                    |
| is_paying_customer | false                                                                                                                                                          |
| meta_data          |                                                                     |
| orders_count       | 0                                                                                                                                                              |

| total_spent        | 0.00                                                                                                                                                           |
| avatar_url         | http://2.gravatar.com/avatar/81a56b00c3b9952d6d2c107a8907e71f?s=96                                                                                             |
+--------------------+----------------------------------------------------------------------------------------------------------------------------------------------------------------+
```

## Examples

Full documentation for every command is available using `--help`. Below are some example commands to show what the CLI can do.

All the examples below use user ID 1 (usually an admin account), but you should replace that with your own user account.

You can also find other examples (without output) by looking at [the testing files for our CLI tests](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/cli/features).

Each command will have a `.feature` file. For example, [these some payment gateway commands](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/cli/features/payment_gateway.feature).

### Clearing the product/shop transients cache

Command: 

`$ wp wc tool run clear_transients --user=1`

Response:

`Success: Updated system_status_tool clear_transients.`

### Listing all system tools

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
````

### Creating a customer

Command:

`$ wp wc customer create --email='woo@woo.local' --user=1 --billing='{"first_name":"Bob","last_name":"Tester","company":"Woo", "address_1": "123 Main St.", "city":"New York", "state:": "NY", "country":"USA"}' --shipping='{"first_name":"Bob","last_name":"Tester","company":"Woo", "address_1": "123 Main St.", "city":"New York", "state:": "NY", "country":"USA"}' --password='hunter2' --username='mrbob' --first_name='Bob' --last_name='Tester'`

Response:

`Success: Created customer 17.`

### Getting a customer in CSV format

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

### Adding a customer note on order 355

Command:

`$ wp wc order_note create 355 --note="Great repeat customer" --customer_note=true --user=1`

Response:

`Success: Created order_note 286.`

### Getting an order note

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

### Updating a coupon

Command:

`$ wp wc shop_coupon update 45 --amount='10' --discount_type='percent' --free_shipping=true --user=1`

Response:

`Success: Updated shop_coupon 45.`

### Getting a coupon

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

## Frequently Asked Questions

### I get a 401 error when using commands, what do I do?

If you are getting a 401 error like `Error: Sorry, you cannot list resources. {"status":401}`, you are trying to use the command unauthenticated. The WooCommerce CLI as of 3.0 requires you to provide a proper user to run the action as. Pass in your user ID using the `--user` flag.

### I am trying to update a list of X, but it's not saving

Some 'lists' are actually objects. For example, if you want to set categories for a product, [the REST API expects an _array of objects_](https://woocommerce.github.io/woocommerce-rest-api-docs/#product-properties).

To set this you would use JSON like this:

```bash
wp wc product create --name='Product Name' --categories='[ { "id" : 21 } ]' --user=admin
```
