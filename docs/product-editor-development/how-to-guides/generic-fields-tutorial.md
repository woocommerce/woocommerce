# Extending the product form with generic fields

We have large list of generic fields that a plugin can use to extend the new product form. You can find the full list [here](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/product-editor/src/blocks/generic/README.md). Each field contains documentation for what attributes the field supports.

## Using a generic block

Using a generic block is pretty easy. We have created an template API that allows you to add new fields, the API refers to them as `blocks`. There are a couple actions that allow us to interact with these templates. There is the `woocommerce_layout_template_after_instantiation` that is triggered when a new template is registered. There are also other actions triggered when a specific field/block is added ( see [block addition and removal](https://github.com/woocommerce/woocommerce/blob/trunk/docs/product-editor-development/block-template-lifecycle.md#block-addition-and-removal) ).

Let's say we want to add something to the basic details section, we can do so by making use of the above mentioned hook:

This will add a number field called **Animal age** to each template that has a `basic-details` section.

```php
add_action(
	'woocommerce_layout_template_after_instantiation',
	function( $layout_template_id, $layout_template_area, $layout_template ) {
	    $basic_details = $layout_template->get_section_by_id( 'basic-details' );

        if ( $basic_details ) {
	        $basic_details->add_block(
        		[
        			'id' 	     => 'example-tutorial-animal-age',
                    // This orders the field, core fields are seperated by sums of 10.
	            	'order'	     => 40,
	            	'blockName'  => 'woocommerce/product-number-field',
	            	'attributes' => [
                        // Attributes specific for the product-number-field.
	            		'label' => 'Animal age',
	            		'property' => 'meta_data.animal_age',
	            		'suffix' => 'Yrs',
	            		'placeholder' => 'Age of animal',
	            		'required' => true,
	            		'min' => 1,
	            		'max' => 20
	            	],
                ]
            );
        }
	},
	10,
	3
);
```

### Dynamically hiding or showing the generic field

It is also possible to dynamically hide or show your field if data on the product form changes.
We can do this by adding a `hideCondition` ( plural ). For example if we wanted to hide our field if the product price is higher than 20, we can do so by adding this expression:

```php
'hideConditions' => array(
	array(
		'expression' => 'editedProduct.regular_price >= 20',
	),
),
```

The `hideConditions` also support targeting meta data by using dot notation. You can do so by writing an expression like this: `! editedProduct.meta_data.animal_type` that will hide a field if the `animal_type` meta data value doesn't exist.
