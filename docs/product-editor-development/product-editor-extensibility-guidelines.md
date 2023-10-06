# Product Editor Extensibility Guidelines

## Form Extensibility

Our research shows that merchants think in terms of tasks and goals while creating new products in Woo. For example, adding or editing a product price is a separate task from uploading images or managing inventory.

For this reason, the new product form features tabs that correspond with these tasks. Simply speaking, they are separate views where different form features live. As a developer, you can add your extension to any of these views and offer merchants the information and tools they need to create better, more successful products.

There are several ways to extend the new product form: from a single field to a whole section containing multiple fields and tables. These extension points are tightly linked to the form structure, giving you plenty of freedom to create a great user experience.

Like everything in the new product form, each extension point is a separate block. However, each existing block can be extended the same as the Block Editor, so for the sake of a consistent and smooth user experience, try to integrate your extension into an existing block before creating a new one.

## Product form tabs

The new product form consists of areas displayed as tabs. Each is a separate view and may contain any number of sections. All areas serve a specific purpose, allowing merchants to quickly find the information they’re looking for (both in default Woo features and extensions).

* **General**: Essential product information, including the name, image, and description. This tab is also where key features live for non-standard product types: downloads, groups, links, etc.

* **Organization**: This tab contains all the data used to organize and categorize product information: from categories to attributes. Best for extensions that provide new ways to describe products, e.g., product identifiers, statuses, special tags, etc.

* **Pricing**: List price, sale price, and tax options. Best for extensions that allow merchants to set up additional payment methods (e.g., Subscriptions) or add advanced pricing schemes, like wholesale.

* **Inventory**: Basic inventory settings and stock management options. Here merchants come to update the quantity at hand or mark the product as out of stock. Best for extensions that enable conditional inventory management, dropshipping options, or various restrictions.

* **Shipping**: All the information merchants need to enter to present customers with accurate shipping rates at checkout. Best for physical product details that may impact shipping (e.g. capacity or volume), additional shipping carrier settings, or custom shipping options.

* **Variations**: Contains product options and variations. It is the only tab that doesn’t support extensibility in its current form (you can add your extension to the variation details page as well as provide custom update actions triggered from the table view or the bulk editor).

## Where should your extension go?

Depending on the type of your extension (and your use case), you can pick an interface location that best suits how users will interact with it.

To choose, put yourself in the merchant’s shoes: where would you go to find this feature? What is it related to? Adding your extension to a group of similar features will help merchants navigate the form and make it easier for them to find your extension.

### Field 

Field extensions are the simplest type of extension. They can add new, replace, and expand existing form fields in any section, hide fields, and change form defaults.

#### ✅ What they are for:

* Single-field, supplementary features
* Showing or hiding form elements depending on specific conditions

#### ❌ What they aren’t for:

* Multi-field or multi-step forms
* Complex tables

#### Use cases

* Adding extra product details, f.e. volume under Shipping
* Entering custom data, f.e. color or date and time
* Selecting from a third-party system, f.e. warranty type

### Group 

Groups add extra fields to existing form sections. They are small forms with a low to medium level of complexity. This interface location works best for extensions that add extra features that build off an existing Woo functionality.

#### ✅ What they are for:

* Relevant features that can be crucial to merchants’ product creation flow
* 2-5 field forms with limited functionality, f.e. dimensions or tax settings
* Lists of items, f.e. attachments, channels, or accounts

#### ❌ What they aren’t for:

* Simple extensions with 1-2 fields
* Multi-step forms and complex tables
* Read-only descriptions, setup guides, and advertisements

#### Use cases

* Adding extra product details, f.e. measurements under Shipping
* Setting up social channels in the Visibility section in the General tab
* Changing the VAT tax settings in the Pricing tab

### Section

Sections are significant parts of the form that may consist of multiple groups and fields. They should be used sparsely throughout the form, so merchants are not overwhelmed with options while filling out the information about their products.

#### ✅ What they are for:

* Complex forms with multiple fields, tables, and list items
* Standalone features that that doesn’t build off of anything else
* Extensions that rely on user-created items, such as tags or attributes

#### ❌ What they aren’t for:

* Simple extensions with 1-2 fields
* Read-only descriptions, setup guides, and advertisements
* Multi-step setup wizards and external content

#### Use cases

* Adding product labels with a robust interactive preview
* Managing product warranty options
* Creating product packages and bundles
