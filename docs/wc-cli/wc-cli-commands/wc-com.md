---
title: wc com
sidebar_label: wc com
---

Interacts with extensions from the WooCommerce.com marketplace via CLI.

## wc com connect

Connect the store to WooCommerce.com with an application password.

- `--password` - WooCommerce.com application password. If omitted, the command prompts for it.
- `--force` - Disconnect the site first and force a new connection if the site is already connected.

## wc com disconnect

Disconnect the store from WooCommerce.com.

- `--yes` - Do not prompt for confirmation.

## wc com extension list

List extensions owned by the connected site.

- `--format` - Render output in a particular format.

    Default: table

    Options: table, csv, json, yaml

- `--fields` - Limit the output to specific object fields.

    Default: all

    Options: product_slug, product_name, auto_renew, expires_on, expired, sites_max, sites_active, maxed

## wc com extension install `<extension>...`

Install one or more plugins from the WooCommerce.com marketplace.

- `--force` - If set, the command will overwrite any installed version of the extension without prompting for confirmation.
- `--activate` - If set, the plugin will be activated after installation.
- `--activate-network` - If set, the plugin will be network activated immediately after installation
- `--insecure` - Retry downloads without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.
