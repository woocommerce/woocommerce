---
post_title: Add link to logged data
menu_title: Add link to logged data
tags: code-snippets
---

[Logging](../extension-development/logging.md) is a crucial part of any WooCommerce extension. It helps you track errors and debug issues. A common pattern is to have a setting in your extension to enable logging when the user needs to troubleshoot an issue. The following code snippet shows an example of how to add this setting, as well as a link to the log viewer, in the context of the [Settings API](../extension-development/settings-api.md).

```php
use Automattic\WooCommerce\Utilities\LoggingUtil;

// Define the label and description for the logging option
$label = \_\_( 'Enable logging', 'your-textdomain-here' );
$description = \_\_( 'Log events and errors to help with troubleshooting.', 'your-textdomain-here' );

// Check if WooCommerce's logging feature is enabled.
if ( LoggingUtil::logging_is_enabled() ) {
    // The source value you use for your extension's log entries. Could be the same as your text domain.
    $source = 'yourpluginslug';
    
    $logs_url = add_query_arg(
        'source',
        $source,
        LoggingUtil::get_logs_tab_url()
    );
    
    $label .= ' | ' . sprintf(
        \_\_( '<a href="%s">View logs</a>', 'your-textdomain-here' ),
        $logs_url
    );
}

// Add the logging option to the form fields.
$form_fields\['yourpluginslug_debug'\] = array(
  'title'       => \_\_( 'Debugging', 'your-textdomain-here' ),
  'label'       => $label,
  'description' => $description,
  'type'        => 'checkbox',
  'default'     => 'no'
);
```
