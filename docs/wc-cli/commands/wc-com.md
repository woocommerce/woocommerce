---
title: wc com
sidebar_label: wc com
sidebar_position: 2
---

## wc com connect

- `--password` - WooCommerce.com application password. If omitted, the command prompts for it.
- `--force` - Disconnect the site first and force a new connection if the site is already connected.

## wc com disconnect

- `--yes` - Do not prompt for confirmation.

## wc com extension list

- `--format` - Render output in a particular format.

Default: table

Options: table, csv, json, yaml

- `--fields` - Limit the output to specific object fields.

Default: all

Options: product_slug, product_name, auto_renew, expires_on, expired, sites_max, sites_active, maxed

## wc com extension install `<extension>...`

- `<extension>...` - One or more plugins to install from the available extensions. Accepts plugin slugs.
- `--force` - If set, the command will overwrite any installed version of the extension without prompting for confirmation.
- `--activate` - If set, after installation, the plugin will activate it.
- `--activate-network` - If set, the plugin will be network activated immediately after installation
- `--insecure` - Retry downloads without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.
