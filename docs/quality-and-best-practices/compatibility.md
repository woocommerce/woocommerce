---
post_title: Compatibility and interoperability for WooCommerce extensions
menu_title: Compatibility best practices
tags: reference
---

Ensuring your WooCommerce extension is compatible and interoperable with the core platform, various components, and other extensions is fundamental to providing a seamless experience for users. This document covers the importance of compatibility, the process for self-declared compatibility checks, manual testing for compatibility issues, and troubleshooting common problems.

## Compatibility importance

Compatibility ensures that your extension works as expected across different environments, including various versions of WordPress and WooCommerce, as well as with other plugins and themes. Ensuring compatibility is crucial for:

- **User experience**: Preventing conflicts that can lead to functionality issues or site downtime.
- **Adoption and retention**: Users are more likely to install and keep updates if they're assured of compatibility.
- **Security and performance**: Compatible extensions are less likely to introduce vulnerabilities or degrade site performance.

## Self-declared compatibility checks

Developers should declare their extension's compatibility with the latest versions of WordPress and WooCommerce, as well as with specific components like Cart & Checkout blocks, High Performance Order Storage (HPOS), Product Editor, and Site Editor. This process involves:

1. **Testing**: Before release, thoroughly test the extension with the latest versions of WordPress and WooCommerce, as well as the specified components.
2. **Declaration**: Update the extension's documentation and metadata to reflect its compatibility with these platforms and components.
3. **Communication**: Inform users of the compatible versions in the extension's change log, website, or repository.

## Manual Compatibility Testing

Manual testing is essential to identify and resolve potential compatibility issues. Follow these steps for effective manual compatibility testing:

1. **Set up a testing environment** that mirrors a typical user setup, including the latest versions of WordPress and WooCommerce.
2. **Test with Core components**: Verify the extension's functionality with core components like Cart & Checkout blocks, HPOS, Product Editor, and Site Editor.
3. **Cross-plugin compatibility**: Activate your extension alongside other commonly used plugins to check for conflicts.
4. **Theme compatibility**: Test your extension with several popular themes to ensure it works correctly and maintains a consistent appearance.

## Troubleshooting and resolving compatibility issues

Despite thorough testing, compatibility issues may arise. Here are common problems and steps to resolve them:

### Conflicts with other extensions

- **Diagnosis**: Use tools like [Health Check & Troubleshooting plugin](https://wordpress.org/plugins/health-check/) to identify conflicting plugins.
- **Resolution**: Contact the other plugin's developer for collaboration, or implement conditional logic in your extension to avoid the conflict.

### Theme Compatibility Issues

- **Diagnosis**: Check for styling or layout issues when your extension is used with different themes.
- **Resolution**: Use more generic CSS selectors and provide configuration options for better theme integration.

### Updates Breaking Compatibility

- **Preventive Measures**: Subscribe to the [WooCommerce developer blog](https://developer.woocommerce.com) to stay informed about upcoming changes.
- **Quick Fixes**: Prepare patches or minor updates to address compatibility issues as soon as possible after a core update.

### No Errors with Multiple Extensions Activated

- **Best Practice**: Regularly test your extension in a multi-plugin environment to ensure it does not cause or suffer from conflicts.

## Conclusion

Maintaining compatibility and interoperability is a continuous effort that requires regular testing, updates, and communication with your users and the broader developer community. By following these guidelines, you can enhance the reliability, user satisfaction, and success of your WooCommerce extension.
