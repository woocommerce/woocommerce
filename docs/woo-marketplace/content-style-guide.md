---
sidebar_position: 8
sidebar_label: 'Content Style Guide'
---

# Marketplace Content Style Guide

This guide provides formatting and writing standards for creating product pages and documentation on the WooCommerce Marketplace. Following these guidelines ensures consistency across the Marketplace and creates a professional experience for merchants browsing and purchasing extensions.

For product page structure, required elements, and asset specifications, refer to [Product Page Content and Assets](/docs/woo-marketplace/product-page-content-and-assets/).

## Writing Standards

### Product Naming Convention

Use the format "Product Name for WooCommerce" rather than "WooCommerce Product Name" throughout the product copy.

This format ensures your product name complies with [trademark rules](https://automattic.com/trademark-policy/) and leads with its unique identifier, improving discoverability and clarity.

### Grammar and Punctuation

Follow the [WooCommerce Grammar, Punctuation, and Capitalization Guide](https://woocommerce.com/document/woocommerce-grammar-punctuation-and-capitalization-guide/) for comprehensive guidance. Key points include:

**Use the Oxford comma** when listing three or more items:

- ✓ "Track orders, manage inventory, and process refunds."
- ✗ "Track orders, manage inventory and process refunds."

**Use active voice** instead of passive voice whenever possible:

- ✓ "The extension sends email notifications when orders ship."
- ✗ "Email notifications are sent by the extension when orders are shipped."

**Capitalization:**

- Use sentence case for headings (capitalize only the first word and proper nouns)
- Do not use ALL CAPS for emphasis
- Capitalize product names, features, and WooCommerce terminology correctly

**Spelling and terminology:**

- Use American English spelling
- Use "ecommerce" (lowercase, no hyphen)
- Use "WooCommerce" (not "Woocommerce" or "wooCommerce")

## Formatting Guidelines

Maintain clean, consistent formatting by following these standards. Custom styling interferes with the Marketplace design system and creates an inconsistent experience.

### Text Formatting

**Do not:**

- Center headings or body text
- Alter the default text color
- Add custom font sizes or font families
- Use bold or italic formatting excessively

**Do:**

- Use left-aligned text throughout
- Allow headings and paragraphs to inherit default styles
- Use bold sparingly for key terms only

### Headings

**Do not:**

- Include more than one H1 tag per page (the product title serves as the H1)
- Center-align headings
- Apply custom colors or styles to headings

**Do:**

- Use H2 for main sections
- Use H3 for subsections within an H2 section
- Maintain a logical heading hierarchy (do not skip levels)

### Sections and Layout

**Do not:**

- Create custom-styled sections or containers
- Add borders around sections
- Apply background colors to content areas
- Add custom spacing between sections
- Add additional styles to columns

**Do:**

- Use standard paragraph and heading blocks
- Let the Marketplace template handle section spacing
- Use the default column block without style modifications

### Lists

**Do not:**

- Customize bullet or number designs with custom CSS
- Add custom spacing between list items
- Create nested lists more than two levels deep

**Do:**

- Use the standard unordered or ordered list blocks
- Apply the approved list classes for styled checkmarks (see Formatting Tools below)

## Content Restrictions

### Emojis

Do not use emojis in product pages or documentation. Emojis can cause accessibility issues, display inconsistently across devices, and create an unprofessional appearance.

- ✗ "🚀 Boost your sales!"
- ✓ "Boost your sales"

### External Links

Do not include links to external websites. All support and documentation links should point to Marketplace URLs:

- Link to your product documentation hosted on WooCommerce.com
- Link to Marketplace support resources
- Do not link to external support portals, documentation sites, or landing pages

## Formatting Tools

The WooCommerce Marketplace provides several formatting tools to create visually appealing content while maintaining consistency.

### Styled List Classes

Use these CSS classes to create professional checkmark lists:

#### Primary checkmark list (wccom-tick-list-primary)

Creates bold checkmarks in green circles. Use for main benefit lists at the top of your product description.

```html
<ul class="wccom-tick-list-primary">
  <li>Accept payments globally</li>
  <li>Automatic tax calculation</li>
  <li>Real-time shipping rates</li>
</ul>
```

#### Secondary checkmark list (wccom-tick-list-secondary)

Creates lightweight green checkmarks. Use for feature lists and secondary content throughout the page.

```html
<ul class="wccom-tick-list-secondary">
  <li>Supports 100+ currencies</li>
  <li>PCI compliant</li>
  <li>Automatic updates</li>
</ul>
```

### Callout Boxes

Use the box shortcode to highlight important information. Available styles:

#### Info box

For general tips and helpful information:

```plain
[box]This extension requires WooCommerce 8.0 or higher.[/box]
```

This component can be used with or without `style="info"`.

#### Alert box (style="alert")

For warnings or important notices:

```plain
[box style="alert"]Backup your site before updating to the latest version.[/box]
```

#### Error box (style="error")

For critical warnings or known limitations:

```plain
[box style="error"]This extension is not compatible with multisite installations.[/box]
```

#### Success box (style="success")

For positive confirmations or best practices:

```plain
[box style="success"]Your API credentials are securely encrypted and stored.[/box]
```

:::info
Use callout boxes sparingly. Overuse reduces their impact and creates visual clutter.
:::

## Documentation Standards

When creating documentation for your product, follow these additional guidelines:

### Structure

- Begin with a clear introduction explaining what the extension does
- Please use the synced pattern titled "Docs – Installation." This explains how our mutual customers should install purchases from WooCommerce.com.
- Document all your product's features and settings
- Provide troubleshooting guidance for common issues

For detailed documentation frameworks, refer to [Support and Documentation Best Practices](https://woocommerce.com/document/extension-development-best-practices/#support-and-documentation).

### Images and Screenshots

When adding visual content:

- Use the modern WordPress admin color scheme
- Use a recent core WordPress theme unless documenting a different theme
- Ensure screenshots are clear and legible
- Optimize file size
- Do not include sensitive or personal information in screenshots
- Add alt text to all images for accessibility
