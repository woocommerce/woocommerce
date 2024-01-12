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