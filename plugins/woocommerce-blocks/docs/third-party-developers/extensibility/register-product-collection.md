# Register Product Collection

The `__experimentalRegisterProductCollection` function is part of the `@woocommerce/blocks-registry` package. This function allows 3PDs to register a new collection. This function accepts most of the arguments that are accepted by [Block Variation](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-variations/#defining-a-block-variation).

> [!WARNING]
> It's experimental and may change in the future. Please use it with caution.

**There are two ways to use this function:**

1. Using `@woocommerce/dependency-extraction-webpack-plugin` in a Webpack configuration: This will allow you to import the function from the package & use it in your code. For example:

	```tsx
	import { __experimentalRegisterProductCollection } from "@woocommerce/blocks-registry";
	```

2. Using the global `wc` object: This will allow you to use the function using the global JS object without importing it. For example:

	```tsx
	wc.wcBlocksRegistry.__experimentalRegisterProductCollection({
	...
	});
	```

> [!TIP]
> The first method is recommended if you are using Webpack.

## Defining a Collection

We will explain important arguments that can be passed to `__experimentalRegisterProductCollection`. For other arguments, you can refer to the [Block Variation](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-variations/#defining-a-block-variation) documentation.

A Collection is defined by an object that can contain the following fields:

- `name` (type `string`): A unique and machine-readable collection name. We recommend using the format `<plugin-name>/product-collection/<collection-name>`. Both `<plugin-name>` and `<collection-name>` should consist only of alphanumeric characters and hyphens (e.g., `my-plugin/product-collection/my-collection`).
- `title` (type `string`): The title of the collection, which will be displayed in various places including the block inserter and collection chooser.
- `description` (optional, type `string`): A human-readable description of the collection.
- `innerBlocks` (optional, type `Array[]`): An array of inner blocks that will be added to the collection. If not provided, the default inner blocks will be used.
- `isDefault`: ⚠️ It's set to `false` for all collections. 3PDs doesn't need to pass this argument.
- `isActive`: ⚠️ It will be managed by us. 3PDs doesn't need to pass this argument.
- `usesReference` (optional, type `Array[]`): An array of strings specifying the required reference for the collection. Acceptable values are `product`, `archive`, `cart`, and `order`. When the required reference isn't available on Editor side but will be available in Frontend, we will show a preview label.

### Attributes

Attributes are the properties that define the behavior of the collection. All the attributes are *optional*. Here are some of the important attributes that can be passed to `__experimentalRegisterProductCollection`:

- `query` (type `object`): The query object that defines the query for the collection. It can contain the following fields:
    - `offset` (type `number`): The number of items to offset the query by.
    - `order` (type `string`): The order of the query. Accepted values are `asc` and `desc`.
    - `orderBy` (type `string`): The field to order the query by.
    - `pages` (type `number`): The number of pages to query.
    - `perPage` (type `number`): The number of products per page.
    - `search` (type `string`): The search term to query by.
    - `taxQuery` (type `object`): The tax query to filter the query by. For example, if you wanna fetch products with category `Clothing` & `Accessories` and tag `Summer` then you can pass `taxQuery` as `{"product_cat":[20,17],"product_tag":[36]}`. Where array values are the term IDs.
    - `featured` (type `boolean`): Whether to query for featured items.
    - `timeFrame` (type `object`): The time frame to query by.
        - `operator` (type `string`): The operator to use for the time frame query. Accepted values are `in` and `not-in`.
        - `value` (type `string`): The value to query by. It should be a valid date string that PHP's `strtotime` function can parse.
    - `woocommerceOnSale` (type `boolean`): Whether to query for items on sale.
    - `woocommerceStockStatus` (type `array`): The stock status to query by. Some of the accepted values are `instock`, `outofstock`, `onbackorder`.
    - `woocommerceAttributes` (type `array`): The attributes to query by.
        - For example, if you wanna fetch products with color `blue` & `gray` and size `Large` then you can pass `woocommerceAttributes` as `[{"termId":23,"taxonomy":"pa_color"},{"termId":26,"taxonomy":"pa_size"},{"termId":29,"taxonomy":"pa_color"}]`.
    - `woocommerceHandPickedProducts` (type `array`): The hand-picked products to query by. It should contain the product IDs.
    - `priceRange` (type `object`): The price range to query by.
        - `min` (type `number`): The minimum price.
        - `max` (type `number`): The maximum price.

- `displayLayout` (type `object`): The display layout object that defines the layout of the collection. It can contain the following fields:
    - `type` (type `string`): The type of layout. Accepted values are `grid` and `stack`.
    - `columns` (type `number`): The number of columns to display.
    - `shrinkColumns` (type `boolean`): Whether the layout should be responsive.
- `hideControls` (type `array`): The controls to hide. Possible values:
    - `order` - "Order by" setting
    - `attributes` - "Product Attributes" filter
    - `created` - "Created" filter
    - `featured` - "Featured" filter
    - `hand-picked` - "Hand-picked Products" filter
    - `keyword` - "Keyword" filter
    - `on-sale` - "On Sale" filter
    - `stock-status` - "Stock Status" filter
    - `taxonomy` - "Product Categories", "Product Tags" and custom taxonomies filters
    - `price-range` - "Price Range" filter

#### Preview Attribute

The `preview` attribute is optional, and it is used to set the preview state of the collection. It can contain the following fields:

- `initialPreviewState` (type `object`): The initial preview state of the collection. It can contain the following fields:
    - `isPreview` (type `boolean`): Whether the collection is in preview mode.
    - `previewMessage` (type `string`): The message to be displayed in the Tooltip when the user hovers over the preview label.
- `setPreviewState` (optional, type `function`): The function to set the preview state of the collection. It receives the following arguments:
    - `setState` (type `function`): The function to set the preview state. You can pass a new preview state to this function containing `isPreview` and `previewMessage`.
    - `attributes` (type `object`): The current attributes of the collection.
    - `location` (type `object`): The location of the collection. Accepted values are `product`, `archive`, `cart`, `order`, `site`.

For more info, you may check PR #46369, in which the Preview feature was added

## Examples

### Example 1: Registering a new collection

```tsx
__experimentalRegisterProductCollection({
  name: "your-plugin-name/product-collection/my-custom-collection",
  title: "My Custom Collection",
  icon: "games",
  description: "This is a custom collection.",
  keywords: ["custom collection", "product collection"],
});
```

As you can see in the example above, we are registering a new collection with the name `woocommerce/product-collection/my-custom-collection` & title `My Custom Collection`. Here is screenshot of how it will look like:
![image](https://github.com/woocommerce/woocommerce/assets/16707866/7fddbc02-a6cd-494e-b2f4-13dd5ef9cf96)

### Example 2: Register a new collection with a preview

As you can see below, setting the initial preview state is possible. In the example below, we are setting `isPreview` and `previewMessage`.

```tsx
__experimentalRegisterProductCollection({
  name: "your-plugin-name/product-collection/my-custom-collection-with-preview",
  title: "My Custom Collection with Preview",
  icon: "games",
  description: "This is a custom collection with preview.",
  keywords: ["My Custom Collection with Preview", "product collection"],
  preview: {
    initialPreviewState: {
      isPreview: true,
      previewMessage:
        "This is a preview message for my custom collection with preview.",
    },
  },
  attributes: {
    query: {
      perPage: 5,
      featured: true,
    },
    displayLayout: {
      type: "grid",
      columns: 3,
      shrinkColumns: true,
    },
	hideControls: [ "created", "stock-status" ]
  },
});
```

Here is how it will look like:
![image](https://github.com/woocommerce/woocommerce/assets/16707866/5fc1aa20-552a-4e09-b811-08babab46665)

### Example 3: Advanced usage of preview

As you can see below, it's also possible to use `setPreviewState` to set the preview state. In the example below, we are setting `initialPreviewState` and using `setPreviewState` to change the preview state after 5 seconds.

**This example shows:**

- How to access current attributes and location in the preview state
- How to use async operations
   	- We are using `setTimeout` to change the preview state after 5 seconds. You can use any async operation, like fetching data from an API, to decide the preview state.
- How to use the cleanup function as a return value
   	- We are returning a cleanup function that will clear the timeout after the component is unmounted. You can use this to clean up any resources you have used in `setPreviewState`.

```tsx
__experimentalRegisterProductCollection({
  name: "your-plugin-name/product-collection/my-custom-collection-with-advanced-preview",
  title: "My Custom Collection with Advanced Preview",
  icon: "games",
  description: "This is a custom collection with advanced preview.",
  keywords: [
    "My Custom Collection with Advanced Preview",
    "product collection",
  ],
  preview: {
    setPreviewState: ({
      setState,
      attributes: currentAttributes,
      location,
    }) => {
      // setPreviewState has access to the current attributes and location.
      // console.log( currentAttributes, location );

      const timeoutID = setTimeout(() => {
        setState({
          isPreview: false,
          previewMessage: "",
        });
      }, 5000);

      return () => clearTimeout(timeoutID);
    },
    initialPreviewState: {
      isPreview: true,
      previewMessage:
        "This is a preview message for my custom collection with advanced preview.",
    },
  },
});
```

### Example 4: Collection with inner blocks

As you can see below, it's also possible to define inner blocks for the collection. In the example below, we are defining inner blocks for the collection.

```tsx
__experimentalRegisterProductCollection({
  name: "your-plugin-name/product-collection/my-custom-collection-with-inner-blocks",
  title: "My Custom Collection with Inner Blocks",
  icon: "games",
  description: "This is a custom collection with inner blocks.",
  keywords: ["My Custom Collection with Inner Blocks", "product collection"],
  innerBlocks: [
    [
      "core/heading",
      {
        textAlign: "center",
        level: 2,
        content: "Title of the collection",
      },
    ],
    [
      "woocommerce/product-template",
      {},
      [
        ["woocommerce/product-image"],
        [
          "woocommerce/product-price",
          {
            textAlign: "center",
            fontSize: "small",
          },
        ],
      ],
    ],
  ],
});
```

This will create a collection with a heading, product image, and product price. Here is how it will look like:

![image](https://github.com/woocommerce/woocommerce/assets/16707866/3d92c084-91e9-4872-a898-080b4b93afca)

> [!TIP]
> You can learn more about inner blocks template in the [Inner Blocks](https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/nested-blocks-inner-blocks/#template) documentation.

### Example 5: Collection with `usesReference` argument

When a collection requires a reference to work properly, you can specify it using the `usesReference` argument. In the example below, we are defining a collection that requires a `product` reference.

```tsx
__experimentalRegisterProductCollection({
  name: "your-plugin-name/product-collection/my-custom-collection",
  title: "My Custom Collection",
  icon: "games",
  description: "This is a custom collection.",
  keywords: ["custom collection", "product collection"],
  usesReference: ["product"],
});
```

This will create a collection that requires a `product` reference. If the required reference isn't available on the Editor side but will be available in the Frontend, we will show a preview label.

When a collection need one of the multiple references, you can specify it using the `usesReference` argument. In the example below, we are defining a collection that requires either a `product` or `order` reference.

```tsx
__experimentalRegisterProductCollection({
  name: "your-plugin-name/product-collection/my-custom-collection",
  title: "My Custom Collection",
  icon: "games",
  description: "This is a custom collection.",
  keywords: ["custom collection", "product collection"],
  usesReference: ["product", "order"],
});
```

---

> [!TIP]
> You can also take a look at how we are defining our core collections at `plugins/woocommerce-blocks/assets/js/blocks/product-collection/collections` directory. Our core collections will also evolve over time.
