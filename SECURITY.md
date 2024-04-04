# Security Policy

Full details of the Automattic Security Policy can be found on [automattic.com/security](https://automattic.com/security/).

## Supported Versions

Generally, *only the latest version of WooCommerce has continued support*. If a critical vulnerability is found in the current version of WooCommerce, we may opt to backport any patches to previous versions. 

## Reporting a Vulnerability

[WooCommerce](https://wordpress.org/plugins/woocommerce) is an open-source plugin for WordPress. Our HackerOne program covers the plugin software, as well as a variety of related projects and infrastructure.

**For responsible disclosure of security issues and to be eligible for our bug bounty program, please submit your report based on instructions found on [automattic.com/security](https://automattic.com/security).**

Our most critical targets are:

* WooCommerce core (this repository)
* WooCommerce [Blocks](https://wordpress.org/plugins/woo-gutenberg-products-block/) and [Admin](https://wordpress.org/plugins/woocommerce-admin/) packages and plugins
* WooCommerce.com -- the primary marketplace and marketing site, and all of it subdomains, e.g. [developer.woo.com](https://developer.woo.com/)
* WordPress.com -- hosted WooCommerce for Business and eCommerce offering on WordPress.com.

For more targets, see the `In Scope` section on [HackerOne](https://hackerone.com/automattic).

Please note that the **WordPress software is a separate entity** from Automattic. Please report vulnerabilities for WordPress through [the WordPress Foundation's HackerOne page](https://hackerone.com/wordpress).

## Guidelines

We're committed to working with security researchers to resolve the vulnerabilities they discover. You can help us by following these guidelines:

*   Follow [HackerOne's disclosure guidelines](https://www.hackerone.com/disclosure-guidelines).
*   Pen-testing Production:
    *   Please **setup a local environment** instead whenever possible. Most of our code is open source (see above).
    *   If that's not possible, **limit any data access/modification** to the bare minimum necessary to reproduce a PoC.
    *   **Don't automate form submissions!** That's very annoying for us, because it adds extra work for the volunteers who manage those systems, and reduces the signal/noise ratio in our communication channels.
    *   To be eligible for a bounty, please follow all of these guidelines.
*   Be Patient - Give us a reasonable time to correct the issue before you disclose the vulnerability.

We also expect you to comply with all applicable laws. You're responsible to pay any taxes associated with your bounties.
