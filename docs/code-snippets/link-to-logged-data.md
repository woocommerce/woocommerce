---
post_title: Add link to logged data
menu_title: Add link to logged data
tags: code-snippets
---

Logging is a crucial part of any WooCommerce extension. It helps you track errors and debug issues. Here's a code snippet that shows how to add a link to the logs in your extension, making it easy for users to access them.

```php
// Define the label and description for the logging option
$label = \_\_( 'Enable Logging', 'your-textdomain-here' );
$description = \_\_( 'Enable the logging of errors.', 'your-textdomain-here' );

// Check if the WooCommerce logging directory is defined
if ( defined( 'WC_LOG_DIR' ) ) {
  // Create a URL to the WooCommerce logs
  $log_url = add_query_arg( 'tab', 'logs', add_query_arg( 'page', 'wc-status', admin_url( 'admin.php' ) ) );
  $log_key = 'your-plugin-slug-here-' . sanitize_file_name( wp_hash( 'your-plugin-slug-here' ) ) . '-log';
  $log_url = add_query_arg( 'log_file', $log_key, $log_url );

  // Add a link to the logs to the label
  $label .= ' | ' . sprintf( \_\_( '%1$sView Log%2$s', 'your-textdomain-here' ), '<a href\="' . esc_url( $log_url ) . '">', '</a\>' );
}

// Add the logging option to the form fields
$form_fields\['wc_yourpluginslug_debug'\] = array(
  'title' => \_\_( 'Debug Log', 'your-textdomain-here' ),
  'label' => $label,
  'description' => $description,
  'type' => 'checkbox',
  'default' => 'no'
);
```

With this code, users can easily enable logging and view the logs from the WooCommerce settings page.
