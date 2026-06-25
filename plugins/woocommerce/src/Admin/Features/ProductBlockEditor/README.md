# Product Block Editor compatibility shims

This folder contains temporary compatibility shims for PHP classes and interfaces that were part of the removed Product Block Editor.

The Product Editor extension APIs were deprecated in WooCommerce 10.9.0, and the Product Block Editor was removed in WooCommerce 11.0.0 with no replacement. These classes remain only to avoid fatal errors in extensions or custom code that still reference the old PHP symbols during the transition period.

Do not add new Product Block Editor behavior here. Any code in this folder should be limited to compatibility with the removed APIs, should avoid reintroducing the removed editor, and may be removed in a future WooCommerce version.
