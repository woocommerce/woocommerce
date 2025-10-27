# WooCommerce Checkout Blocks Developer Documentation

A comprehensive, progressive guide to building and extending WooCommerce checkout blocks.

## Documentation Series

This documentation is organized as a progressive learning path, from foundational concepts to advanced techniques:

### 1️⃣ [Understanding Checkout Blocks](./01-understanding-checkout-blocks.md)
**Start here** | 5 min read | Conceptual foundation

Learn the fundamental architectural shift from shortcode-based checkout to the blocks paradigm. Understand the three core concepts (components, state synchronization, Store API) and decide whether checkout blocks are right for your project.

**You'll learn:**
- What changed and why
- Core architectural concepts
- What stays the same vs. what changes
- When to use blocks vs. shortcodes
- Extension mechanisms overview

**Best for:** Everyone new to checkout blocks, especially PHP developers familiar with shortcode customization.

---

### 2️⃣ [Your First Checkout Extension](./02-your-first-checkout-extension.md)
**Quick start** | 10-15 min tutorial | Hands-on

Build a working checkout extension that adds a custom "Purchase Order Number" field using only PHP. Get your first win and learn the fundamental patterns.

**You'll build:**
- Custom field registration
- Field validation (client and server-side)
- Data sanitization
- Saving to orders
- Displaying in emails and admin

**Best for:** Developers who learn by doing and want a quick success before diving deeper.

**Prerequisite:** None (but reading Document 1 first is recommended)

---

### 3️⃣ [Advanced Checkout Extensions](./03-advanced-checkout-extensions.md)
**Level up** | Comprehensive guide | Building skills

Master advanced patterns including select/checkbox fields, conditional logic, cross-field validation, API integration, and an introduction to JavaScript extensions.

**You'll learn:**
- Different field types (select, checkbox)
- Conditional field display
- Dynamic default values
- External API integration with caching
- Performance optimization
- Slot fills and filters (JavaScript)
- Multiple related fields
- Production-ready patterns

**Best for:** Developers ready to build sophisticated checkout customizations after completing the quick start.

**Prerequisite:** Complete Document 2 first

---

### 4️⃣ [Checkout Blocks API Reference](./04-checkout-blocks-api-reference.md)
**Look it up** | Complete reference | Documentation

Complete technical reference covering all hooks, filters, functions, data stores, and APIs. Use this as your lookup guide when implementing extensions.

**Contains:**
- All PHP hooks and filters (signatures, parameters, examples)
- CheckoutFields service methods
- Data store APIs (selectors, actions)
- Store API endpoints
- JavaScript APIs (slot fills, filters, inner blocks)
- Event system reference
- Migration guide (shortcode → blocks)
- Troubleshooting reference

**Best for:** Reference during development, looking up specific functions, or understanding the complete API surface.

**Prerequisite:** Familiarity with basic checkout blocks concepts

---

## Quick Navigation

### I want to...

**Understand if checkout blocks are right for me**
→ Start with [Understanding Checkout Blocks](./01-understanding-checkout-blocks.md)

**Add a custom field to checkout**
→ Jump to [Your First Checkout Extension](./02-your-first-checkout-extension.md)

**Show/hide fields conditionally**
→ See [Advanced Checkout Extensions - Pattern 2](./03-advanced-checkout-extensions.md#pattern-2-conditional-field-display)

**Validate against an external API**
→ See [Advanced Checkout Extensions - Pattern 4](./03-advanced-checkout-extensions.md#pattern-4-external-api-integration)

**Look up a specific hook or function**
→ Check [API Reference](./04-checkout-blocks-api-reference.md)

**Migrate from shortcode checkout**
→ See [Migration Guide](./04-checkout-blocks-api-reference.md#migration-guide-shortcode-to-blocks)

**Add custom content to checkout UI**
→ See [Slot Fills](./03-advanced-checkout-extensions.md#slot-fills-adding-content-to-checkout) or [API Reference - Slot Fills](./04-checkout-blocks-api-reference.md#slot-fills)

**Modify displayed prices or labels**
→ See [Advanced Extensions - Filters](./03-advanced-checkout-extensions.md#filters-modifying-displayed-data) or [API Reference - Filter Registry](./04-checkout-blocks-api-reference.md#filter-registry)

**Troubleshoot an issue**
→ Check [Troubleshooting Reference](./04-checkout-blocks-api-reference.md#troubleshooting-reference)

---

## Recommended Learning Paths

### For PHP Developers (No JavaScript Required)

1. Read [Understanding Checkout Blocks](./01-understanding-checkout-blocks.md) (5 min)
2. Complete [Your First Checkout Extension](./02-your-first-checkout-extension.md) (15 min)
3. Study [Advanced Checkout Extensions](./03-advanced-checkout-extensions.md) Patterns 1-6 (PHP sections)
4. Keep [API Reference](./04-checkout-blocks-api-reference.md) bookmarked for lookup

**You can build 80% of checkout extensions with just PHP using Additional Checkout Fields.**

### For Full-Stack Developers

1. Read [Understanding Checkout Blocks](./01-understanding-checkout-blocks.md) (5 min)
2. Complete [Your First Checkout Extension](./02-your-first-checkout-extension.md) (15 min)
3. Study all of [Advanced Checkout Extensions](./03-advanced-checkout-extensions.md) including JavaScript sections
4. Review [API Reference - JavaScript APIs](./04-checkout-blocks-api-reference.md#javascript-apis)
5. Explore Inner Blocks for custom components

### For Migrating from Shortcode Checkout

1. Read [Understanding Checkout Blocks - What Changes](./01-understanding-checkout-blocks.md#what-changes)
2. Review [Migration Guide](./04-checkout-blocks-api-reference.md#migration-guide-shortcode-to-blocks)
3. Complete [Your First Checkout Extension](./02-your-first-checkout-extension.md) to learn new patterns
4. Gradually migrate features using [Advanced Patterns](./03-advanced-checkout-extensions.md)

---

## Prerequisites

Before starting these guides, you should have:

- **WooCommerce 8.3+** installed and active
- **A checkout page using the checkout block** (not shortcode)
- **Basic PHP knowledge** (for all guides)
- **JavaScript/React knowledge** (only for advanced JavaScript sections)
- **A development environment** where you can add code

### Verify You're Using Checkout Blocks

1. Go to your checkout page
2. If you see a modern, two-column layout → You're using blocks ✓
3. If it looks like a traditional form → You're using shortcode (convert in WooCommerce settings)

Or check the page source:
```html
<!-- Checkout Block -->
<div class="wp-block-woocommerce-checkout">

<!-- Shortcode -->
[woocommerce_checkout]
```

---

## Code Examples

All code examples in this documentation:

- ✓ Are tested and production-ready
- ✓ Follow WordPress and WooCommerce coding standards
- ✓ Include proper sanitization and validation
- ✓ Are safe to copy and adapt for your projects
- ✓ Include inline comments explaining key concepts

Feel free to use these examples as starting points for your own extensions.

---

## Document Formats

Each document follows a consistent format:

**Understanding (Document 1):**
- Conceptual explanations
- Visual diagrams and comparisons
- Decision trees
- "What you've learned" summaries

**Tutorial (Document 2):**
- Step-by-step instructions
- Complete code examples
- Testing procedures
- Troubleshooting sections
- Challenges to test your knowledge

**Advanced Guide (Document 3):**
- Pattern-based organization
- Real-world use cases
- Progressive complexity
- Performance considerations
- Production checklist

**API Reference (Document 4):**
- Function signatures and parameters
- Complete hook documentation
- Quick reference tables
- Migration mappings
- Copy-paste snippets

---

## Getting Help

### Within This Documentation

- Each document has a troubleshooting section
- [API Reference](./04-checkout-blocks-api-reference.md#troubleshooting-reference) has comprehensive debugging guide
- Code examples include common pitfalls in comments

### External Resources

- [WooCommerce Blocks GitHub](https://github.com/woocommerce/woocommerce-blocks) - Official repository
- [WooCommerce Developer Documentation](https://developer.woocommerce.com/) - General WC dev docs
- [WooCommerce Community Slack](https://woocommerce.com/community-slack/) - Ask questions
- [WordPress Block Editor Handbook](https://developer.wordpress.org/block-editor/) - For JavaScript work

---

## Contributing

Found an issue or want to improve these docs?

- **For WooCommerce core:** [Submit an issue](https://github.com/woocommerce/woocommerce/issues)
- **Suggestions:** Improve examples or add missing patterns via pull request

---

## Document Versions

These documents are written for:

- **WooCommerce:** 9.0+
- **WooCommerce Blocks:** Bundled with WooCommerce 8.3+
- **WordPress:** 6.0+
- **PHP:** 7.4+

Features and APIs may change in future versions. Check the official WooCommerce Blocks changelog for updates.

---

## What's Next?

Ready to get started?

→ **[Begin with Understanding Checkout Blocks →](./01-understanding-checkout-blocks.md)**

Or jump straight to coding:

→ **[Your First Checkout Extension (Quick Start) →](./02-your-first-checkout-extension.md)**

---

**Happy building!** These guides will take you from checkout blocks beginner to confident extension developer.
