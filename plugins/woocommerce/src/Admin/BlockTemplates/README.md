# BlockTemplates compatibility shims

This folder contains temporary compatibility shims for PHP interfaces that were part of the removed Block Templates API.

The Block Templates extension APIs were deprecated in WooCommerce 10.9.0, and the Block Templates API was removed in WooCommerce 11.0.0 with no replacement. These interfaces remain only to avoid fatal errors in extensions or custom code that still reference the old PHP symbols during the transition period.

Do not add new Block Templates behavior here. Any code in this folder should be limited to compatibility with the removed APIs, should avoid reintroducing the removed template system, and may be removed in a future WooCommerce version.
