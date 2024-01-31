---
post_title: Product editor extensibility guidelines
menu_title: Extensibility guidelines
---

## Form Extensibility

**Our research shows that merchants think in terms of tasks and goals while creating new products in Woo.** For example, adding or editing a product price is a separate task from uploading images or managing inventory.

For this reason, the new product form features groups (tabs) that correspond with these tasks. Simply speaking, they are separate views where different form features live. As a developer, you can extend any of these views and offer merchants the information and tools they need to create better, more successful products.

There are several ways to extend the new product form: from a single field to a whole group or section containing multiple fields and tables. These extension points are linked to the form structure, giving you plenty of freedom to create a great user experience.

- Product form
    - Group
        - Section
            - Subsection
                - Field
                - Field
                - ...
            - Subsection
                - Field
                - Field
                - ...
            - ...
        - Section
            - Subsection
                - Field
                - Field
                - ...
            - Subsection
                - Field
                - Field
                - ...
            - ...
        - ...
    - Group
    ...

Like everything in the new product form, each extension point is a separate block. For the sake of a consistent and smooth user experience, try to integrate your extension into an existing group or section before creating a new one.

### Product form groups (tabs)

The new product form consists of groups currently displayed as tabs. Each is a separate view and may contain any number of sections and subsections. All areas serve a specific purpose, allowing merchants to quickly find the information they're looking for (both in default Woo features and extensions).

![Product form groups](https://developer.woo.com/wp-content/uploads/2023/12/product-editor-ext-guidelines-form-groups.gif)

If a tab doesn't contain any sections, it won't be shown to merchants.

- **General:** Essential product information, including the name, image, and description. This tab is also where key features live for non-standard product types: downloads, groups, links, etc.
- **Organization:** This tab contains all the data used to organize and categorize product information: from categories to attributes. Best for extensions that provide new ways to describe products, e.g., product identifiers, statuses, special tags, etc.
- **Pricing:** List price, sale price, and tax options. Best for extensions that allow merchants to set up additional payment methods (e.g., Subscriptions) or add advanced pricing schemes, like wholesale.
- **Inventory:** Basic inventory settings and stock management options. Here merchants come to update the quantity at hand or mark the product as out of stock. Best for extensions that enable conditional inventory management, dropshipping options, or various restrictions.
- **Shipping:** All the information merchants need to enter to present customers with accurate shipping rates at checkout. Best for physical product details that may impact shipping (e.g. capacity or volume), additional shipping carrier settings, or custom shipping options.
- **Variations:** Contains variation options and product variations.

Custom product types manage the visibility of the default groups and add new ones. This is particularly useful if a custom product has a unique structure and requires extra information that isn't included in the default groups.

[Learn more about custom product types](#custom-product-types)

### Where should your extension go?

Depending on the type of your extension (and your use case), you can pick the interface location that best suits how users will interact with it.

To choose, put yourself in the merchant's shoes: where would you go to find this feature? What is it related to? Adding your extension to a group of similar features will help make it easier for merchants to find your extension.

See the guide below for some practical examples.

#### Product code extension

##### What it does

The extension allows merchants to enter a product identifier, such as ISBN, EAN, or UPC.

##### Our recommendations

The identifier is a single piece of information that helps merchants describe and categorize the product across their store and other sales channels. It's best suited to be added as a field in the Product catalog section in the Organization group.

[Learn more about fields](#fields)

#### Ticket extension

##### What it does

Merchants can set up and sell tickets with advanced settings, such as unique input fields, expiration dates, restrictions, tiers, and more.

##### Our recommendations

With so much advanced functionality, the plugin would best register a new product type and define the structure and appearance of the product form. This could include the tabs at the top of the screen, the subgroups and sections inside, and the default values.

[Learn more about custom product types](#custom-product-types)

### Fields

![Fields example](https://developer.woo.com/wp-content/uploads/2023/12/product-editor-ext-guidelines-fields.png)

Fields are the simplest type of extension. They let users add extra product information, replace or manage the visibility of other fields assigned to a specific product type, and control the contents of other fields.

**What they *are* for:**

- Single-field, supplementary features
- Showing or hiding form elements depending on specific conditions

**What they *aren't* for:**

- Multi-field or multi-step forms
- Complex tables, e.g., permissions, restrictions, shipping volumes, etc
- Embedded third-party experiences and websites

Field extensions should always be logically related to the form area they are in. For example, if you're building a dropshipping extension, your warehouse selection field should live in the first section of the Inventory group. To ensure an excellent experience for our merchants, we do not recommend placing it in a separate group, section, or subsection.

**Other use cases include:**

- Adding extra product details, f.e. volume under Shipping
- Entering custom data, f.e. color or date and time
- Selecting from a third-party system, f.e. warranty type

### Subsections

![Subsections example](https://developer.woo.com/wp-content/uploads/2023/12/product-editor-ext-guidelines-subsections.png)

Subsections add extra fields to existing form groups. They are small forms with a low to medium level of complexity. This interface location works best for extensions that add extra features that build off an existing Woo functionality.

**What they *are* for:**

- Relevant features that can be crucial to merchants' product creation flow
- 2-5 field forms with simple functionality, e.g., dimensions or tax settings
- Lists of items, e.g., attachments, channels, or accounts

**What they *aren't* for:**

- Simple extensions with 1-2 fields
- Multi-step forms and complex tables
- Read-only descriptions, setup guides, and advertisements

**Example:**

If you're developing an extension that allows merchants to upload 360 images or videos, you could add it as a field or a button in the Images section in the General tab. This way, merchants can create the perfect product gallery without jumping between multiple tabs.

**Other use cases include:**

- Adding extra product details, e.g., measurements under Shipping
- Setting up social channels in the Visibility section in the General tab
- Changing the VAT tax settings in the Pricing tab

### Sections

![Sections example](https://developer.woo.com/wp-content/uploads/2023/12/product-editor-ext-guidelines-sections.png)

Sections are significant parts of the form that may consist of multiple subsections and fields. They should be used sparsely throughout the form, so merchants are not overwhelmed with options while filling out the information about their products.

**What they *are* for:**

- Complex forms with multiple fields, tables, and list items
- Standalone features that don't build off of anything else
- Extensions that rely on user-created items, such as tags or attributes

**What they *aren't* for:**

- Simple extensions with 1-2 fields
- Read-only descriptions, setup guides, and advertisements
- Multi-step setup wizards and external content

**Example:**

If you're working on an extension that allows merchants to offer discounts based on the number of purchased items, you may consider adding a new section in the Pricing tab. This will give you enough space to present the information in a legible, easy-to-navigate manner.

**Other use cases include:**

- Adding product labels with a robust interactive preview
- Managing product warranty options
- Creating product packages and bundles

### Top bar (header) *(future feature)*

![Top bar example](https://developer.woo.com/wp-content/uploads/2023/12/product-editor-ext-guidelines-top-bar.png)

Top bar extensions offer supplementary experiences **not vital** to the critical product creation flows. They're secondary, meaning that they shouldn't contain information that may impact the product's overall quality or completeness.

Each top bar extension has its unique icon in the top navigation bar. Note that when the number of extensions exceeds 4, they're truncated in a dropdown menu.

For example, top bar extensions can be used to:

- Provide product scheduling or extra visibility options,
- Let merchants view additional SEO metadata
- Offer easy access to documentation or step-by-step guides

Depending on their roles, top bar extensions can be displayed in either a **popover** or a **modal**.

### Dialog extensions *(future feature)*

![Dialog example](https://developer.woo.com/wp-content/uploads/2023/12/product-editor-ext-guidelines-dialog-extensions.png)

Dialog extensions differ from other extensions as they are unrelated to any section or functionality within the product form. They can connect to third-party systems or come with complex interfaces that require a separate, focused experience.

Dialogs can have different sizes (small, medium, large, or custom) and trigger locations (text or icon button anywhere in the form or in the form's top bar).

**What they *are* for:**

- Focused experiences that require taking over most of the screen
- Advanced configuration and setup flows
- Dedicated content embedded from a third-party service

**What they *aren't* for:**

- Single-field features or simple settings screens
- Small functionalities that could fit within the form
- Onboarding experiences, product demos, and advertisements

**Example use cases:**

- Third-party fulfillment, warehousing, and accounting service integration
- Robust image editing tool with complex interactions
- Media-heavy knowledge base with plenty of videos and interactive tutorials

## Custom product types

Custom product types allow you to design a custom form and completely control its structure. They are convenient for extensions that enable merchants to create products from start to finish.

With custom product types, you can:

- Add, hide, and reorder groups
- Add and hide sections within a group
- Add and hide subsections and fields
    - Includes core fields
    - Can be set up conditionally based on a custom field's value

Custom product types include niche and specific use cases, such as bookings, tickets, gift cards, rentals, etc. Here's when we suggest you should consider creating a custom product type:

- Your extension consists of several different form sections scattered across several different tabs
- Using just your extension, merchants can completely a product
- You want to help merchants create products faster and automate some of their work

