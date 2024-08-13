# Extending the product form with custom fields

Aside from extending the product form using generic fields it is also possible to use custom fields. This does require knowledge of JavaScript and React.
If you are already familiar with writing blocks for the WordPress site editor this will feel very similar.

## Getting started

To get started we would recommend reading through the [fundamentals of block development docs](https://developer.wordpress.org/block-editor/getting-started/fundamentals/) in WordPress. This gives a good overview of working with blocks, the block structure, and the [JavaScript build process](https://developer.wordpress.org/block-editor/getting-started/fundamentals/javascript-in-the-block-editor/).

This tutorial will use vanilla JavaScript to render a new field in the product form for those that already have a plugin and may not have a JavaScript build process set up yet.
If you want to create a plugin from scratch with the necessary build tools, we recommend using the `@wordpress/create-block` script. We also have a specific template for the product form: [README](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/create-product-editor-block/README.md).

## Creating a custom field

### Adding and registering our custom field

Adding and registering our custom field is very similar as creating a brand new block.

Inside a new folder within your plugin, let's create a `block.json` file with the contents below. The only main difference between this `block.json` and a `block.json` for the site editor is that we will set `supports.inserter` to false, so it doesn't show up there. We will also be registering this slightly different.

```json
{
	"$schema": "https://schemas.wp.org/trunk/block.json",
	"apiVersion": 3,
	"name": "tutorial/new-product-form-field",
	"title": "Product form field",
	"category": "woocommerce",
	"description": "A sample field for the product form",
	"keywords": [ "products" ],
	"attributes": {},
	"supports": {
		"html": false,
		"multiple": true,
		// Setting inserter to false is important so that it doesn't show in the site editor.
		"inserter": false
	},
	"textdomain": "woocommerce",
	"editorScript": "file:./index.js"
}
```

In the same directory, create a `index.js` file, which we can keep simple by just outputting a hello world.
In this case the `edit` function is the part that will get rendered in the form. We are wrapping it with the `createElement` function to keep support for React.

```javascript
( function ( wp ) {
	var el = wp.element.createElement;

	wp.blocks.registerBlockType( 'tutorial/new-product-form-field', {
		title: 'Product form field',
		attributes: {},
		edit: function () {
			return el( 'p', {}, 'Hello World (from the editor).' );
		},
	} );
} )( window.wp );
```

In React:

```jsx
import { registerBlockType } from '@wordpress/blocks';

function Edit() {
	return <p>Hello World (from the editor).</p>;
}

registerBlockType( 'tutorial/new-product-form-field', {
	title: 'Product form field',
	attributes: {},
	edit: Edit,
} );
```

Lastly, in order to make this work the block registration needs to know about the JavaScript dependencies, we can do so by adding a `index.asset.php` file with the below contents:

```php
<?php return array('dependencies' => array('react', 'wc-product-editor', 'wp-blocks' ) );
```

Now that we have all the for the field we need to register it and add it to the template.

Registering can be done on `init` by calling `BlockRegistry::get_instance()->register_block_type_from_metadata` like so:

```php
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\BlockRegistry;

function example_custom_product_form_init() {
	if ( isset( $_GET['page'] ) && $_GET['page'] === 'wc-admin' ) {
        // This points to the directory that contains your block.json.
		BlockRegistry::get_instance()->register_block_type_from_metadata( __DIR__ . '/js/sample-block' );
	}
}
add_action( 'init', 'example_custom_product_form_init' );
```

We can add it to the product form by hooking into the `woocommerce_layout_template_after_instantiation` action ( see [block addition and removal](https://github.com/woocommerce/woocommerce/blob/trunk/docs/product-editor-development/block-template-lifecycle.md#block-addition-and-removal) ).

What we did was the following ( see [here](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Admin/Features/ProductBlockEditor/ProductTemplates/README.md#usage) for more related functions ):

-   Get a group by the `general` id, this is the General tab.
-   Create a new section on the general tab called `Tutorial Section`
-   Add our custom field to the `Tutorial Section`

```php
add_action(
    'woocommerce_layout_template_after_instantiation',
    function( $layout_template_id, $layout_template_area, $layout_template ) {
        $general = $layout_template->get_group_by_id( 'general' );

        if ( $general ) {
            // Creating a new section, this is optional.
            $tutorial_section = $general->add_section(
				array(
					'id'         => 'tutorial-section',
					'order'      => 15,
					'attributes' => array(
						'title'       => __( 'Tutorial Section', 'woocommerce' ),
						'description' => __( 'Fields related to the tutorial', 'woocommerce' ),
					),
				)
			);
            $tutorial_section->add_block(
                [
                    'id' 	     => 'example-new-product-form-field',
                    'blockName'  => 'tutorial/new-product-form-field',
                    'attributes' => [],
                ]
            );
        }
    },
    10,
    3
);
```

### Turn field into a dropdown

We recommend using components from `@wordpress/components` as this will also keep the styling consistent. We will use the [ComboboxControl](https://wordpress.github.io/gutenberg/?path=/docs/components-comboboxcontrol--docs) core component in this field.

We can add it to our `edit` function pretty easily by making use of `wp.components`. We will also add a constant for the filter options.
**Note:** I also added the `blockProps` to the top element, we still recommend using this as some of these props are being used in the product form. When we add the block props we need to also let the form know it is an interactive element. We do this by adding at-least one attribute with the `__experimentalRole` set to `content`.
So lets add this to our `index.js` attributes:

```javascript
 attributes: {
    "message": {
        "type": "string",
        "__experimentalRole": "content",
        "source": "text",
        "selector": "div"
    }
},
```

Dropdown options, these can live outside of the `edit` function:

```javascript
const DROPDOWN_OPTIONS = [
	{
		value: 'small',
		label: 'Small',
	},
	{
		value: 'normal',
		label: 'Normal',
	},
	{
		value: 'large',
		label: 'Large',
	},
];
```

The updated `edit` function:

```javascript
// edit function.
function ( { attributes } ) {
    // useState is a React specific function.
    const [ value, setValue ] = wp.element.useState();
    const [ filteredOptions, setFilteredOptions ] = wp.element.useState( DROPDOWN_OPTIONS );

    const blockProps = window.wc.blockTemplates.useWooBlockProps( attributes );

	return el( 'div', { ...blockProps }, [
        el( wp.components.ComboboxControl, {
            label: "Example dropdown",
            value: value,
            onChange: setValue,
            options: filteredOptions,
            onFilterValueChange: function( inputValue ) {
                setFilteredOptions(
                    DROPDOWN_OPTIONS.filter( ( option ) =>
                        option.label
                        .toLowerCase()
                        .startsWith( inputValue.toLowerCase() )
                    )
                )
            }
        } )
    ] );
},
```

In React:

```jsx
import { createElement, useState } from '@wordpress/element';
import { ComboboxControl } from '@wordpress/components';
import { useWooBlockProps } from '@woocommerce/block-templates';

function Edit( { attributes } ) {
	const [ value, setValue ] = useState();
	const [ filteredOptions, setFilteredOptions ] =
		useState( DROPDOWN_OPTIONS );

	const blockProps = useWooBlockProps( attributes );
	return (
		<div { ...blockProps }>
			<ComboboxControl
				label="Example dropdown"
				value={ value }
				onChange={ setValue }
				options={ filteredOptions }
				onFilterValueChange={ ( inputValue ) =>
					setFilteredOptions(
						DROPDOWN_OPTIONS.filter( ( option ) =>
							option.label
								.toLowerCase()
								.startsWith( inputValue.toLowerCase() )
						)
					)
				}
			/>
		</div>
	);
}
```

### Save field data to the product data

We can make use of the `__experimentalUseProductEntityProp` for saving the field input to the product.
The function does rely on `postType`, we can hardcode this to `product`, but the `postType` is also exposed through a context. We can do so by adding `"usesContext": [ "postType" ],` to the `block.json` and getting it from the `context` passed into the `edit` function props.

So the top part of the edit function will look like this, where we also replace the `value, setValue` `useState` line:

```javascript
// edit function.
function ( { attributes, context } ) {
    const [ value, setValue ] = window.wc.productEditor.__experimentalUseProductEntityProp(
		'meta_data.animal_type',
		{
			postType: context.postType,
			fallbackValue: '',
		}
	);
    // .... Rest of edit function
```

Now if you select small, medium, or large from the dropdown and save your product, the value should persist correctly.

Note, the above function supports the use of `meta_data` by dot notation, but you can also target other fields like `regular_price` or `summary`.
