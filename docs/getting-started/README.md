---
slug: /
sidebar_position: 0
sidebar_label: 'Getting started'
title: 'WooCommerce Developer Docs'
hide_title: true
hide_table_of_contents: true
---

{/* <!-- markdownlint-disable MD033 MD041 --> */}

import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

<div className="woo-docs-homepage">

<div className="woo-docs-homepage__lead">

<div className="woo-docs-homepage__intro">

<h1>WooCommerce Developer Docs</h1>

WooCommerce is a customizable, open-source eCommerce platform built on WordPress. It empowers businesses worldwide to sell anything from physical products and digital downloads to subscriptions, content, and even appointments.

</div>

<div className="woo-docs-homepage__setup">

<Tabs className="woo-docs-homepage__setup-tabs">

<TabItem value="manual" label="Command" default>

```bash
# Install the Studio CLI if needed (macOS and Linux).
curl -fsSL https://wordpress.studio/install.sh | bash

# In a new terminal and empty directory, start a Woo store.
studio blueprint use woo-shop
```

Studio starts the local site and gives you its URL. For setup details, other local environments, and working with WooCommerce Core, read [Set up your development environment](/docs/getting-started/development-environment/).

</TabItem>

<TabItem value="agent" label="Agent prompt">

Copy this prompt into a coding agent with terminal access.

```text
Fetch the instructions from https://developer.woocommerce.com/docs/llms.txt.
```

</TabItem>

</Tabs>

</div>

</div>

## Build and extend WooCommerce

Choose a starting point for the work you want to do. Each guide has the full setup and implementation details.

<div className="woo-docs-homepage__capability-grid">

<section className="woo-docs-homepage__capability-card">

### Extensions

Add functionality to a Woo store, such as store settings, payment methods, or shipping options.

[Build your first extension](/docs/extensions/getting-started-extensions/building-your-first-extension/)

</section>

<section className="woo-docs-homepage__capability-card">

### Storefronts and themes

Create and customize shopper-facing templates and styles with WooCommerce block themes or classic themes.

[Develop a block theme](/docs/theming/block-theme-development/theming-woo-blocks/)

</section>

<section className="woo-docs-homepage__capability-card">

### REST APIs

Use the REST API for store management data. Use the Store API for shopper-facing product, cart, and checkout flows.

[REST API](/docs/apis/rest-api/) · [Store API](/docs/apis/store-api/)

</section>

<section className="woo-docs-homepage__capability-card">

### WooCommerce CLI

Manage products, orders, and store settings from the command line with `wp wc`.

[Explore WooCommerce CLI](/docs/wc-cli/cli-overview/)

</section>

<section className="woo-docs-homepage__capability-card">

### WooCommerce MCP

Connect a compatible AI client to a running store through the WordPress MCP Adapter and WooCommerce abilities. This integration is a developer preview.

[Set up WooCommerce MCP](/docs/features/mcp/)

</section>

<section className="woo-docs-homepage__capability-card">

### Contribute to WooCommerce

Fix bugs, improve documentation, and help test WooCommerce in the open-source project.

[Read the contributor guide](/docs/contribution/contributing/)

</section>

</div>

## Popular resources

Find releases, changes, and extensions.

<div className="woo-docs-homepage__resource-list">

- [Releases](https://developer.woocommerce.com/releases/) — find WooCommerce Core release downloads.
- [Changelog](https://developer.woocommerce.com/changelog/) — read recent release notes.
- [Woo Marketplace](https://woocommerce.com/product-category/woocommerce-extensions/) — browse extensions for your store.

</div>

## Browse the docs

The directory below keeps the broader guides, feature docs, examples, and references in view.

<div className="woo-docs-homepage__directory-grid">

<section className="woo-docs-homepage__directory-card">

### Getting started

- [Project structure](/docs/getting-started/project-structure/)
- [Local development](/docs/getting-started/development-environment/)
- [Scaffolding and sample data](/docs/getting-started/scaffolding/)
- [Developer tools](/docs/getting-started/developer-tools/)
- [Debugging](/docs/getting-started/debugging/)
- [AI](/docs/getting-started/ai/)

</section>

<section className="woo-docs-homepage__directory-card">

### Extensions

- [Extension development](/docs/extensions/getting-started-extensions/)
- [Designing an extension](/docs/extensions/getting-started-extensions/how-to-design-a-simple-extension/)
- [Extension best practices](/docs/extensions/best-practices-extensions/extension-development-best-practices/)

</section>

<section className="woo-docs-homepage__directory-card">

### Theming and blocks

- [Block theme development](/docs/theming/block-theme-development/theming-woo-blocks/)
- [Child themes](/docs/theming/theme-development/set-up-a-child-theme/)
- [Classic theme handbook](/docs/theming/theme-development/classic-theme-developer-handbook/)
- [Theme design guidelines](/docs/theming/theme-development/theme-design-ux-guidelines/)
- [Block development](/docs/block-development/)

</section>

<section className="woo-docs-homepage__directory-card">

### Features

- [Products](/docs/features/products/wc-get-products/)
- [Orders](/docs/features/orders/wc-get-orders/)
- [Payments](/docs/features/payments/payment-gateway-api/)
- [Shipping](/docs/features/shipping/shipping-method-api/)
- [Analytics](/docs/features/analytics/extending-woocommerce-admin-reports/)

</section>

<section className="woo-docs-homepage__directory-card">

### Best practices

- [Security](/docs/best-practices/security/security-best-practices/)
- [Performance](/docs/best-practices/performance/performance-best-practices/)
- [Data management](/docs/best-practices/data-management/crud-objects/)
- [Webhooks](/docs/best-practices/urls-and-routing/webhooks/)

</section>

<section className="woo-docs-homepage__directory-card">

### Code snippets and references

- [Code snippets](/docs/code-snippets/)
- [WooCommerce Code Reference](https://woocommerce.github.io/code-reference/)
- [REST API v3](/docs/apis/rest-api/v3/)
- [Store API endpoints](/docs/apis/store-api/resources-endpoints/products/)
- [WooCommerce CLI](/docs/wc-cli/cli-overview/)

</section>

</div>

## More ways to learn and contribute

Start with the [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/) and [plugin basics](https://developer.wordpress.org/plugins/plugin-basics/) if you are new to WordPress development. Find the [WooCommerce monorepo](https://github.com/woocommerce/woocommerce), [open issues](https://github.com/woocommerce/woocommerce/issues), [all WooCommerce repositories](https://woocommerce.github.io/), and [Woo community Slack](https://woo.com/community-slack/).

For store setup and merchant tasks, visit the [WooCommerce website](https://woocommerce.com/), [Woo Marketplace](https://woocommerce.com/marketplace), and [merchant documentation](https://woocommerce.com/documentation/woocommerce/).

</div>
