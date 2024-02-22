---
post_title: WooCommerce CLI Frequently Asked Questions
menu_title: Frequently Asked Questions
tags: reference
---

## General Questions

### What is WooCommerce CLI?

- WooCommerce CLI (WC-CLI) is a command-line interface for managing WooCommerce settings and data. It provides a fast and efficient way to perform many tasks that would otherwise require manual work via the WordPress admin interface.

### How do I install WooCommerce CLI?

- WooCommerce CLI is included as part of WooCommerce from version 3.0.0 onwards. Ensure you have WooCommerce 3.0.0 or later installed, and you automatically have access to WC-CLI.

### Is WooCommerce CLI different from WP-CLI?

- WooCommerce CLI is a part of WP-CLI specifically designed for WooCommerce. While WP-CLI deals with general WordPress management, WC-CLI focuses on WooCommerce-specific tasks.

## Technical Questions

### How can I create a backup of my WooCommerce data?

- WC-CLI doesn't directly handle backups. However, you can use other WP-CLI commands to export WooCommerce data or rely on WordPress backup plugins.

### Can I update WooCommerce settings using the CLI?

- Yes, you can update many WooCommerce settings using WC-CLI. For example, to update store email settings, you would use a command like wp wc setting update [options].

## Troubleshooting

### Why am I getting a "permission denied" error?

- This error often occurs if your user role doesn't have the necessary permissions. Ensure you're using an account with administrative privileges.

### What should I do if a command is not working as expected

- Check for typos and verify the command syntax with --help. If the issue persists, consult the Command Reference or seek support from the WooCommerce community.

**What do I do if I get 404 errors when using commands?

If you are getting a 401 error like `Error: Sorry, you cannot list resources. {"status":401}`, you are trying to use the command unauthenticated. The WooCommerce CLI as of 3.0 requires you to provide a proper user to run the action as. Pass in your user ID using the `--user` flag.

### I am trying to update a list of X, but it’s not saving

Some ‘lists’ are actually objects. For example, if you want to set categories for a product, the REST API expects an array of objects.

To set this you would use JSON like this:

```bash
wp wc product create --name='Product Name' --categories='[ { "id" : 21 } ]' --user=admin
```

## Advanced Usage

### Can I use WooCommerce CLI for bulk product updates?

- Yes, WC-CLI is well-suited for bulk operations. You can use scripting to loop through a list of products and apply updates.

### How do I handle complex queries with WC-CLI?

- WC-CLI supports various arguments and filters that you can use to build complex queries. Combining these with shell scripting can yield powerful results.
