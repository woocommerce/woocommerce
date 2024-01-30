---
post_title: WooCommerce Extension Guidelines - Navigation
menu_title: Navigation
---

**Menu Structure.** Place your product navigation elements within the existing WooCommerce taxonomy.

Examples:

- If your extension is extending a component within WooCommerce, it should live within either the Extensions navigation drawer (in Woo Express stores), or directly within that category's section.

Extensions drawer (Woo Express)
![Navigation extensions drawer](https://developer.woo.com/docs/wp-content/uploads/sites/3/2024/01/Image-1224x572-1.png)

![Navigation category](https://developer.woo.com/docs/wp-content/uploads/sites/3/2024/01/Image-1242x764-1.png)

- If your plugin adds a settings screen to set up the plugin, settings should be under an appropriate tab on the WooCommerce > Settings screen. Only if necessary, create a top-level settings tab if your plugin has settings that don't fit under existing tabs and creating a sub-tab isn't appropriate.

**No iframes, only APIs.** To create a cohesive experience, application data should be loaded via API instead of an iFrame.

**One line navigation label.** Keep all navigation labels on one line. Do not introduce a second line in any of the preexisting menu items.

**Keep menu structure simple.** Use existing WooCommerce menu structures as much as possible to reduce redundancies. If your plugin must introduce multiple pages or areas, consider grouping them in tabs using existing components to remain consistent with WooCommerce structure. 

**No top level navigation.** If your product is extending WooCommerce, then there's a 99.9% chance your product navigation, and settings should live within the WooCommerce nav structure-see above menu structure examples.
