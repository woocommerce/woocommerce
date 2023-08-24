# User Experience Guidelines

This guide covers general guidelines, and best practices to follow in order to ensure your product experience aligns with WooCommerce for ease of use, seamless integration, and strong adoption.

We strongly recommend you review the current [WooCommerce setup experience](https://www.google.com/url?q=https://woocommerce.com/documentation/plugins/woocommerce/getting-started/&sa=D&source=editors&ust=1692895324238396&usg=AOvVaw2TmgGeQmH4N_DZY6QS9Bve) to get familiar with the user experience and taxonomy.

We also recommend you review the [WordPress core guidelines](https://www.google.com/url?q=https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/&sa=D&source=editors&ust=1692895324239052&usg=AOvVaw1E61gu1LlpT1F6yqYdMrcl) to ensure your product isn’t breaking any rules, and review [this helpful resource](https://www.google.com/url?q=https://woocommerce.com/document/grammar-punctuation-style-guide/&sa=D&source=editors&ust=1692895324239337&usg=AOvVaw0tMP_9YsdpSjtiAOQSw_D-) on content style.

## General

Use existing WordPress/WooCommerce UI, built in components (text fields, checkboxes, etc) and existing menu structures.

Plugins which draw on WordPress’ core design aesthetic will benefit from future updates to this design as WordPress continues to evolve. If you need to make an exception for your product, be prepared to provide a valid use case.

- [WordPress Components library](https://www.google.com/url?q=https://wordpress.github.io/gutenberg/?path%3D/story/docs-introduction--page&sa=D&source=editors&ust=1692895324240206&usg=AOvVaw12wUm2BSmyxcEjcAQxlwaU)
- [Figma for WordPress](https://www.google.com/url?q=https://make.wordpress.org/design/2018/11/19/figma-for-wordpress/&sa=D&source=editors&ust=1692895324240568&usg=AOvVaw1iTxXh4YpA9AZlAACquK3g) | ([WordPress Design Library Figma](https://www.google.com/url?q=https://www.figma.com/file/e4tLacmlPuZV47l7901FEs/WordPress-Design-Library?type%3Ddesign%26node-id%3D7-42%26t%3Dm8IgUWrqfZX0GNCh-0&sa=D&source=editors&ust=1692895324240869&usg=AOvVaw0N2Y5nktcq9dypK8N68nMD))
- [WooCommerce Component Library](https://www.google.com/url?q=https://woocommerce.github.io/woocommerce-admin/%23/&sa=D&source=editors&ust=1692895324241224&usg=AOvVaw0rXxnruNoF8alalpaev9yD)

## Best practices

Plugin name should simply state the feature of the plugin and not use an existing core feature or extension in its’ title. The plugin name should appear at all times in the UI as a functional and original name. e.g “Appointments” instead of “VendorXYZ Bookings Plugin for WooCommerce.”

Avoid creating new UI. Before considering a new UI, review the WordPress interface to see if a component can be repurposed. Follow existing UI navigation patterns so merchants have context on where they are when navigating to a new experience.

Be considerate of mobile for the merchant (and shopper-facing if applicable) experience. Stores operate 24/7. Merchants shouldn’t be limited to checking their store on a desktop. Extensions need to be built responsively so they work on all device sizes.

It’s all about the merchant. Don’t distract with unrelated content. Keep the product experience front and center to help the user achieve the tasks they purchased your product for.

Present a review request at the right time. Presenting users with a request for review is a great way to get feedback on your extension. Think about best placement and timing to show these prompts.

Here are some best practices:

- Avoid showing the user a review request upon first launching the extension. Once the user has had a chance to set up, connect, and use the plugin they’ll have a better idea of how to rate it.
- Try to present the review request at a time that’s least disruptive, such as after successful completion of a task or event.

Don’t alter the core interface. Don’t express your brand by changing the shape of containers in the Woo admin.

Focus on the experience. After the customer installs your product, the experience should be the primary focus. Keep things simple and guide the user to successful setup. Do not convolute the experience or distract the user with branding, self promotion, large banners, or anything obtrusive.

Keep copy short and simple. Limit instructions within the interface to 120-140 characters. Anything longer should be placed in the product documentation.

Maintain a consistent tone when communicating with a user. Maintain the same communication style and terminology across an extension, and avoid abbreviations and acronyms.

In extensions:

- Use sentences for descriptions, feedback, and headlines. Avoid all-caps text.
- Use standard punctuation and avoid excessive exclamation marks.
- Use American English.

For more, read our [Grammar, Punctuation, and Capitalization guide](https://www.google.com/url?q=https://woocommerce.com/document/grammar-punctuation-style-guide/&sa=D&source=editors&ust=1692895324244468&usg=AOvVaw2FWh4SUBI0dLsCqUZtXGFt).

## Colors

When creating extensions for the WordPress wp-admin, use the core colors, respect the user’s WordPress admin color scheme selection, and ensure your designs pass AA level guidelines.

When using components with text, such as buttons, cards, or navigation, the background-to-text contrast ratio should be at least 4.5:1 to be [WCAG AA compliant](https://www.google.com/url?q=https://www.w3.org/WAI/WCAG21/Understanding/contrast-minimum.html&sa=D&source=editors&ust=1692895324245359&usg=AOvVaw04OufEgaTguaV-k6wMtlMU). Be sure to [test your color contrast ratios](https://www.google.com/url?q=https://webaim.org/resources/contrastchecker/&sa=D&source=editors&ust=1692895324245608&usg=AOvVaw1aGcU7vUM05t3bxPA2qrIX) to abide by WCAG standards.

- [Accessibility handbook on uses of color and contrast](https://www.google.com/url?q=https://make.wordpress.org/accessibility/handbook/current-projects/use-of-color/&sa=D&source=editors&ust=1692895324245960&usg=AOvVaw3DDtjcP5MkNoQgX3VgPKXr)
- [Color contrast ratio checker](https://www.google.com/url?q=http://webaim.org/resources/contrastchecker/&sa=D&source=editors&ust=1692895324246320&usg=AOvVaw1RTR_DT4liFu_SiBOF8RxK)
- [More resources regarding accessibility and color testing](https://www.google.com/url?q=http://webaim.org/resources/contrastchecker/&sa=D&source=editors&ust=1692895324246679&usg=AOvVaw316-gDJXDzTH8gOjibWeRm)

For WooCommerce-specific color use, review our [Style Guide](https://www.google.com/url?q=https://woocommerce.com/brand-and-logo-guidelines/&sa=D&source=editors&ust=1692895324247100&usg=AOvVaw2cgvb_mHoClPzhtW57QooS).

## Accessibility

Your extensions must meet the [Web Content Accessibility Guidelines](https://www.google.com/url?q=https://www.w3.org/WAI/standards-guidelines/wcag/&sa=D&source=editors&ust=1692895324247620&usg=AOvVaw3zuZP9mII_1wB0hF2DHvqz) (WCAG). Meeting 100% conformance with WCAG 2.0 is hard work; meet the AA level of conformance at a minimum.

For more information on accessibility, check out the [WordPress accessibility quick start guide](https://www.google.com/url?q=https://make.wordpress.org/accessibility/handbook/best-practices/quick-start-guide/&sa=D&source=editors&ust=1692895324247995&usg=AOvVaw1FOL7wC9TwyiIxLUiQZ34k).

