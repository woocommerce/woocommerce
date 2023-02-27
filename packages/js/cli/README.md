# Woo CLI

A command line interface for developing WooCommerce extensions.

## Installation

```
npm install -g @woocommerce/cli
```

## Usage

```
% woo --help
Usage: woo [options] [command]

Options:
  -v, --version           output the current version
  -h, --help              display help for command

Commands:
  new|n <extension_name>
  help [command]          display help for command
```

Example: 

```
% woo new coffee-shopper

Creating a new WordPress plugin in the coffee-shopper directory.
...
To enter the directory type:

  $ cd coffee-shopper

You can start development with:

  $ npm start

You can start WordPress with:

  $ npx wp-env start

Code is Poetry
```

## Development

for a new release do the following (changlog file, publish to npm)

- [ ] move to TS
- [ ] explore testing Commander.js applications
- [ ] dynamic WooCommerce version
