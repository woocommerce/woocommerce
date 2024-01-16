---
post_title: User Experience Guidelines - Payments Onboarding and Setup
menu_title: Payments Onboarding and Setup
---

Follow the overall [user experience guidelines for WooCommerce](https://github.com/woocommerce/woocommerce/blob/trunk/docs/user-experience/user-experience-guidelines.md).

Payments plugins come in many types: payment processors and gateways, wallets, Buy Now Pay Later, crypto, and more.

The choice between payment plugins depends on the specific needs and preferences of the merchant and their customers. Some merchants may choose to use multiple types of payment plugins to offer their customers a wider range of payment options.

A merchant can discover a payments plugin in the following ways:

- Through direct installation from WordPress plugins.
- Through Woo’s public list of payment services on the Marketplace.
- Through the admin onboarding tasks and payments settings. To be added to the list of payment gateways in the Woo admin, the payments plugin must meet the criteria listed in these guidelines.

## Payments onboarding

Plugin authors should actively guide merchants through setup of the plugin once installed and activated.

### Third party onboarding

For plugins that use OAuth or a third party experience to obtain permission from the merchant, the merchant clicks on a link to set up your plugin, which redirects to the plugin onboarding.

The merchant is redirected to the payments plugin to complete configuration and completes any required authentication as part of this step.

For example:

- Prompt the merchant to login to or create an account with the payment provider.
- Ask the merchant to complete configuration for billing plans, payouts, or notifications.

### Manual setup

If manual entry of API keys is required, design the setup process to be as simple as possible, ideally with a guided setup wizard.

## Configuration & Settings

Follow the Woo User Experience guidelines for [Settings](docs/user-experience/settings.md) and [Navigation](docs/user-experience/navigation.md).

Include sensible default settings to minimize the configuration effort for the user.

After the merchant has completed all the actions that are required for your plugin’s onboarding, merchants are redirected back to the Woo admin to select payment methods to offer and configure additional settings for the plugin.

If you're offering multiple payment methods within the plugin, present the choices clearly to the merchant and provide an indication of which payment methods are enabled.

![Payment methods](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Payment-methods.png)

If a payment method is not available for any reason, provide clear and informative error messages that help users diagnose and resolve issues.

Your plugin needs to inform the merchant that it's ready to process payments. Until then, display an inline warning notice in the plugin settings as a reminder to the merchant. Do not implement a top level banner for the warning notice.

![Inline notice](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Inline-Notice.png)

---

## Payment button size and anatomy

Payment buttons must be consistent in visual appearance to other buttons in the shopper experience.

Height for payment buttons ranges from 40px (Small) to 56px (Large). The default button height is 48px. The following presets can be used for button height.

- Small: Height 40px
- Default: Height 48px
- Large: Height 56

### Anatomy

Payment buttons consist of up to three elements: a button component, payment logo, and optional label.
[Anatomy.png]

Don’t place any text outside the surface area of the button.

![Cart layout 2](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Cart-layout-2.png")

### Layout and spacing

The spacing between and around the Label and Payment Logo adapts based on the size of the button.

![Layout and spacing 0.png](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Layout-and-spacing-0.png)

### Adaptive height

The height of the button can adapt based on the needs of the theme.

![Layout and spacing.png](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Layout-and-spacing.png)

### Button size

Make payment buttons the same size, no bigger or smaller than other payment buttons, including the CTAs "Add to cart" and "Proceed to checkout"

![Same button size.png](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Same-button-size.png)

### Button width

Use the same width size for all payment buttons. Don’t make the width larger or smaller than other payment buttons.

![button width.png](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Button-width.png)

### Minimum width

Buttons have a minimum width to ensure readability.

![Layout and spacing-2.png](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Layout-and-spacing-2.png)

Follow the minimum width of each payment button size. Don’t squeeze payment buttons in a single line.

![Minimum width.png](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Minimum-width.png)

### Button Shape

Use the same corner radius for all payment buttons. Don’t make the corner radius larger or smaller than other payment buttons.

- Default corner radius: 4px
- Maximum corner radius: ½ button height

![Corner radius.png](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Corner-radius.png)

### Button Label

Provide variant buttons to accommodate merchant needs and payment experiences.
Buy
Pay
Donate
Book
Checkout
Subscribe
Continue
Order
Icon only

The button label should appear in sentence case, with only the first letter of the first word capitalized.

![Button labels.png](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Button-Labels.png)

---

## Payment button style

Use the appropriate button style depending on the site theme.

![Button styles.png](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Button-styles.png)

### Dark

Use on white or light-color backgrounds. Don’t use on black or dark backgrounds.

![Dark.png](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Dark.png)

### Light

Use on dark-color backgrounds. Don’t use on white or light backgrounds.

![Light.png](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Light.png)

### Light with outline

Use on white or light-color backgrounds that don’t provide sufficient contrast. Don’t use on dark or saturated backgrounds.

![Light with outline.png](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Light-with-outline.png)

### Button style and specifications

Buttons should contain the following states for resting, loading, active, and loading contexts. For additional guidance, follow the general Woo accessibility guidelines.

![Button States and specifications](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Button-States-and-specifications.png)

## Payment button layout

Define the position and alignment of buttons in relationship to their container, with equal hierarchy and consistent spacing between buttons.

### Product pages

Position payment buttons correctly in relation to "Add to Cart" in horizontal or vertical layout, place payment buttons to the right of or below the "Add to Cart" button.

![Product page layout.png](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Product-page-layout.png)

### Cart

Position payment buttons correctly in relation to "Proceed to checkout" buttons in vertical layout. Place payment buttons above the "Proceed to checkout" button. Do not place payment buttons below the button or in between.

![Cart layout.png](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Cart-layout.png)

### Express checkout

Position payment buttons correctly in horizontal layout, consistent in size and spacing. If a second row is required, left-align the buttons.

![Express checkout layout.png](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Express-checkout-layout.png)

### Mobile view

Express payment buttons on mobile should occupy the full width. Don’t use the express payment border as it reduces the view area.

![Mobile view.png](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Mobile-view.png)

### Clear space

Maintain the minimum amount of clear space on all sides of the payment button. The clear space adapts based on the size of the button.

![Clear space.png](https://woo-docs-multi-com.go-vip.net/docs/wp-content/uploads/sites/3/2024/01/Clear-space.png)
