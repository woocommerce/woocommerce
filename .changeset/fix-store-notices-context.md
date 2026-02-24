---
'@woocommerce/plugin-woocommerce': patch
---

Fix - Store notices: prevent fatal errors when context is empty.

Added try/catch guards to `getContext()` calls to fix TypeError crashes during custom AJAX navigation (without Interactivity Router).
