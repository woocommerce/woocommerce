--
post_title: User Experience Review
menu_title: Review
--

### Woo Marketplace User Experience Review

[User Experience (UX) reviews](https://woocommerce.com/document/marketplace-overview/#section-16) have been introduced into the [Marketplace submission review process](https://woocommerce.com/document/marketplace-overview/#section-12) to ensure submitted products adhere to product quality, Woo Marketplace [standards](https://woocommerce.com/document/create-a-plugin/), and [guidelines](https://woocommerce.com/document/user-experience-guidelines-ux/) before launching. With this additional review, you can anticipate longer review times. However, we have some best practices that you can follow to speed up the UX review process.

### Pre-Submission

**Be proud of your product.** To make your submission stand out, it’s important to focus on the specific ways your product sets itself apart from other products in the Marketplace. Take the opportunity to showcase your product’s strengths and unique features that make it stand out. Remember, the goal of open source is to encourage innovation and progress, so be proud of what makes your product special.

**Provide a complete testing environment** that includes all the necessary and relevant data such as demo sites, demo login, demo account logins, walkthrough video URLs, plugin URLs, etc. This will enable the Marketplace team to fully test your product. You could pre-actively provide login credentials to your demo website where the product is fully configured or provide test demo accounts for 3rd party platforms that your product integrates with. If you fail to provide all the necessary testing resources, it might delay your submission.

**Always include [WooCommerce checks](https://woocommerce.com/document/create-a-plugin/#section-1)** –  the first item tested is whether your plugin breaks when WooCommerce is not installed/activated.

**Test, Test, and Test.** Ensure the product works as expected before submitting. More often than not, critical errors or major bugs are found in submitted products during UX review. You can save time by testing all possible scenarios prior to submitting. Also, be sure to [test the critical flows](https://developer.woocommerce.com/testing-extensions-and-maintaining-quality-code/critical-flows/?_gl=1*1en0vj9*_gcl_au*MTg4MjA5MTIzMy4xNzIyMjc3MjI4*_ga*MTQ2MTY0MTg2Ni4xNzEzMzQ1MjI1*_ga_98K30SHWB2*MTcyODQxNzQ2Ni42MC4xLjE3Mjg0MTg4NDQuMC4wLjA.) applicable to your product.

**Review and proofread your in-product copy again and again.** Ensure grammar and punctuation are on point and spelling/typos are non-existent. For example, capitalize the first letter of the first word in a sentence, include periods at the end of sentences, and use commas when necessary. And the first “c” in WooCommerce is always capitalized. For more, read our [Grammar, Punctuation, and Capitalization guide](https://woocommerce.com/document/grammar-punctuation-style-guide/).

**Guide the merchant during onboarding.** A clear and simple onboarding process is crucial for your product. Provide a clear next step or step-by-step process with a progress indicator to help users get started. Keep it well-balanced so users don’t abandon the product altogether. For more onboarding best practices, see our [UX guidelines](https://woocommerce.com/document/user-experience-guidelines-ux/#section-2).

**Integrate with WooCommerce.** The plugin should feel as if it’s naturally integrated into WooCommerce and not as an add-on plugin. Ask yourself the following:

- Is the plugin settings menu placed within the WooCommerce navigation menu?
- Does the plugin use the existing WordPress/WooCommerce UI, built in components (text fields, checkboxes, etc) and existing menu structures?
- Does the color scheme match WooCommerce?
- Does the plugin home screen simply state the feature of the plugin, e.g “Bookings” instead of  “VendorXYZ Bookings Plugin for WooCommerce.”

Plugins that draw on WordPress’ core design aesthetic will benefit from future updates to this design as WordPress continues to evolve. If you need to make an exception for your product, be prepared to provide a valid use case. Here are resources to reference to make sure your product is aligned with the WordPress/WooCommerce design system:

- [Marketplace User Experience Guidelines](https://woocommerce.com/document/user-experience-guidelines-ux/#section-5)
- [WordPress Components library](https://www.figma.com/file/ZtN5xslEVYgzU7Dd5CxgGZwq/WordPress-Components?node-id=0%3A1)
- [Figma for WordPress](https://make.wordpress.org/design/2018/11/19/figma-for-wordpress/)
- [WooCommerce Admin](https://woocommerce.github.io/woocommerce-admin/#/) 

**Are you using external links at any point in-product?** If so, don’t. This includes the Plugin Installer page – all links need to point to WooCommerce, e.g vendor page, plugin product page, support contact form, etc. This ensures a consistent user experience by keeping the user on WooCommerce.

**Plugins cannot send executable code through third-party systems.** Loading code from documented services is allowed, but communication must be secure. Executing outside code within a plugin is not allowed. For instance, using third-party CDNs for non-service-related JavaScript and CSS is prohibited. Iframes should not be used to connect admin pages.

**Feature overload or feature creep is real** and is to be considered when developing products for WooCommerce. After all, you don’t want to overwhelm the user with too many options that the product feels unusable to them.  During the UX review, we will highly be paying attention to this factor. Do yourself (and us) a favor by keeping this in check and only including setting options / features that are crucial to the overall functionality of the product. Use smart defaults when possible, and remember, settings should be pre-configured and optional.

**Offboarding don’ts.** When users uninstall your plugin or theme, it is disruptive and intrusive to display any sort of screen capturing the reason for uninstallation. Do not collect user information (email address, URL site, etc) as this is prohibited per [Marketplace Terms of Service](https://wordpress.com/tos/?_gl=1*sz1e3n*_gcl_au*MTg4MjA5MTIzMy4xNzIyMjc3MjI4*_ga*MTQ2MTY0MTg2Ni4xNzEzMzQ1MjI1*_ga_98K30SHWB2*MTcyODQxNzQ2Ni42MC4xLjE3Mjg0MTg4NDQuMC4wLjA.).

### Technical Documentation 

**Tend to your product technical documentation with a lot of care.** Ideally, your product is configurable without the user having to reference the technical documentation but even so, having clear, informative, and precise technical documentation is key. Having full-fledged technical documentation alone can be empowering to a user helping to improve customer experience and reduce product churn. So, with this in mind, please take your time to document the step-by-step setup, configuration, usage and FAQ for the product as if a novice beginner will be using your product. Refer to our [Technical Documentation](https://woocommerce.com/document/writing-documentation/) guide for information and examples.

**Proofread, Proofread, Proofread.** Please, please check for styling inconsistencies, punctuation and grammatical errors, misspellings and typos, etc (because trust, we do notice them and there’s no need for these minor items to delay launch.

**Incorporate product changes wherever needed.** If product update changes were made to backend or frontend experiences, remember to update applicable screenshots and text in the technical documentation.

### During UX Review

**Product feedback will be provided by the Marketplace team directly in the submission.** Please keep in mind that every time a product is sent back to your team for fixes, your product is re-queued for review meaning it’s placed at the end of our regular queue and is not prioritized, further causing delays.

**Incorporate product changes wherever needed.** Please be sure that any product changes are reflected in the product documentation and technical documentation. For example, if setting options are removed from the admin backend, remember to update the relevant product screenshots and documentation, if applicable.

**Your product can be rejected at any point in the review process.** Even after passing Code Review and Business Review, the product can be rejected and not approved for launch, this includes UX review.

All in all, we do our best to review products as swiftly as possible. You can help us out by taking the time to submit your product once it’s all buttoned up, polished and ready to shine.



