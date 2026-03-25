<?php
// This file is generated. Do not modify it manually.
return array(
	'accordion-group' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/accordion-group',
		'title' => 'Accordion Group',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'description' => 'A group of headers and associated expandable content.',
		'example' => array(
			
		),
		'__experimental' => true,
		'supports' => array(
			'html' => false,
			'align' => array(
				'wide',
				'full'
			),
			'background' => array(
				'backgroundImage' => true,
				'backgroundSize' => true,
				'__experimentalDefaultControls' => array(
					'backgroundImage' => true
				)
			),
			'color' => array(
				'background' => true,
				'gradient' => true
			),
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'style' => true,
				'width' => true,
				'__experimentalDefaultControls' => array(
					'color' => true,
					'radius' => true,
					'style' => true,
					'width' => true
				)
			),
			'spacing' => array(
				'padding' => true,
				'margin' => array(
					'top',
					'bottom'
				),
				'blockGap' => true
			),
			'shadow' => true,
			'layout' => true,
			'interactivity' => true
		),
		'attributes' => array(
			'iconPosition' => array(
				'type' => 'string',
				'default' => 'right'
			),
			'autoclose' => array(
				'type' => 'boolean',
				'default' => false
			),
			'allowedBlocks' => array(
				'type' => 'array'
			)
		),
		'allowedBlocks' => array(
			'woocommerce/accordion-item'
		),
		'textdomain' => 'woocommerce',
		'viewScriptModule' => 'woocommerce/accordion-group',
		'style' => 'file:../woocommerce/accordion-group-style.css'
	),
	'accordion-header' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/accordion-header',
		'title' => 'Accordion Header',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'description' => 'Accordion header.',
		'example' => array(
			
		),
		'__experimental' => true,
		'parent' => array(
			'woocommerce/accordion-item'
		),
		'supports' => array(
			'anchor' => true,
			'color' => array(
				'background' => true,
				'gradient' => true
			),
			'align' => false,
			'border' => true,
			'interactivity' => true,
			'spacing' => array(
				'padding' => true,
				'margin' => array(
					'top',
					'bottom'
				),
				'__experimentalDefaultControls' => array(
					'padding' => true,
					'margin' => true
				)
			),
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'style' => true,
				'width' => true,
				'__experimentalDefaultControls' => array(
					'color' => true,
					'radius' => true,
					'style' => true,
					'width' => true
				)
			),
			'typography' => array(
				'textAlign' => true,
				'fontSize' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true,
					'fontFamily' => true
				)
			),
			'shadow' => true,
			'layout' => true
		),
		'attributes' => array(
			'openByDefault' => array(
				'type' => 'boolean',
				'default' => false
			),
			'title' => array(
				'type' => 'rich-text',
				'source' => 'rich-text',
				'selector' => 'span'
			),
			'level' => array(
				'type' => 'number',
				'default' => 3
			),
			'levelOptions' => array(
				'type' => 'array'
			),
			'textAlignment' => array(
				'type' => 'string',
				'default' => 'left'
			),
			'icon' => array(
				'type' => array(
					'string',
					'boolean'
				),
				'enum' => array(
					'plus',
					'chevron',
					'chevronRight',
					'caret',
					'circlePlus',
					false
				),
				'default' => 'plus'
			),
			'iconPosition' => array(
				'type' => 'string',
				'enum' => array(
					'left',
					'right'
				),
				'default' => 'right'
			)
		),
		'textdomain' => 'woocommerce'
	),
	'accordion-item' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/accordion-item',
		'title' => 'Accordion',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'description' => 'A single accordion that displays a header and expandable content.',
		'example' => array(
			
		),
		'__experimental' => true,
		'parent' => array(
			'woocommerce/accordion-group'
		),
		'allowedBlocks' => array(
			'woocommerce/accordion-header',
			'woocommerce/accordion-panel'
		),
		'supports' => array(
			'align' => array(
				'wide',
				'full'
			),
			'color' => array(
				'background' => true,
				'gradient' => true
			),
			'interactivity' => true,
			'spacing' => array(
				'margin' => array(
					'top',
					'bottom'
				),
				'blockGap' => true
			),
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'style' => true,
				'width' => true,
				'__experimentalDefaultControls' => array(
					'color' => true,
					'radius' => true,
					'style' => true,
					'width' => true
				)
			),
			'shadow' => true,
			'layout' => true
		),
		'attributes' => array(
			'openByDefault' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'textdomain' => 'woocommerce'
	),
	'accordion-panel' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/accordion-panel',
		'title' => 'Accordion Panel',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'description' => 'Accordion Panel',
		'example' => array(
			
		),
		'__experimental' => true,
		'parent' => array(
			'woocommerce/accordion-item'
		),
		'supports' => array(
			'color' => array(
				'background' => true,
				'gradient' => true
			),
			'border' => true,
			'interactivity' => true,
			'spacing' => array(
				'padding' => true,
				'margin' => array(
					'top',
					'bottom'
				),
				'blockGap' => true,
				'__experimentalDefaultControls' => array(
					'padding' => true,
					'blockGap' => true
				)
			),
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'style' => true,
				'width' => true,
				'__experimentalDefaultControls' => array(
					'color' => true,
					'radius' => true,
					'style' => true,
					'width' => true
				)
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'shadow' => true,
			'layout' => true
		),
		'attributes' => array(
			'allowedBlocks' => array(
				'type' => 'array'
			),
			'templateLock' => array(
				'type' => array(
					'string',
					'boolean'
				),
				'enum' => array(
					'all',
					'insert',
					'contentOnly',
					false
				),
				'default' => false
			),
			'openByDefault' => array(
				'type' => 'boolean',
				'default' => false
			),
			'isSelected' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'textdomain' => 'woocommerce'
	),
	'active-filters' => array(
		'name' => 'woocommerce/active-filters',
		'title' => 'Active Filters Controls',
		'description' => 'Display the currently active filters.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => false
			),
			'html' => false,
			'multiple' => false,
			'inserter' => false,
			'color' => array(
				'text' => true,
				'background' => false
			),
			'lock' => false
		),
		'attributes' => array(
			'displayStyle' => array(
				'type' => 'string',
				'default' => 'list'
			),
			'headingLevel' => array(
				'type' => 'number',
				'default' => 3
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'add-to-cart-form' => array(
		'name' => 'woocommerce/add-to-cart-form',
		'title' => 'Add to Cart with Options',
		'description' => 'Display a button that lets customers add a product to their cart. Use the added options to optimize for different product types.',
		'category' => 'woocommerce-product-elements',
		'attributes' => array(
			'quantitySelectorStyle' => array(
				'type' => 'string',
				'enum' => array(
					'input',
					'stepper'
				),
				'default' => 'input'
			)
		),
		'keywords' => array(
			'WooCommerce'
		),
		'usesContext' => array(
			'postId'
		),
		'textdomain' => 'woocommerce',
		'supports' => array(
			'interactivity' => true
		),
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'viewScriptModule' => 'woocommerce/add-to-cart-form',
		'style' => 'file:../woocommerce/add-to-cart-form-style.css',
		'editorStyle' => 'file:../woocommerce/add-to-cart-form-editor.css'
	),
	'add-to-cart-with-options' => array(
		'name' => 'woocommerce/add-to-cart-with-options',
		'title' => 'Add to Cart + Options (Beta)',
		'description' => 'Use blocks to create an "Add to cart" area that\'s customized for different product types, such as variable and grouped. ',
		'category' => 'woocommerce-product-elements',
		'attributes' => array(
			'isDescendantOfAddToCartWithOptions' => array(
				'type' => 'boolean',
				'default' => true
			)
		),
		'usesContext' => array(
			'postId'
		),
		'providesContext' => array(
			'woocommerce/isDescendantOfAddToCartWithOptions' => 'isDescendantOfAddToCartWithOptions'
		),
		'textdomain' => 'woocommerce',
		'supports' => array(
			'interactivity' => true
		),
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'style' => 'file:../woocommerce/add-to-cart-with-options-style.css',
		'editorStyle' => 'file:../woocommerce/add-to-cart-with-options-editor.css'
	),
	'add-to-cart-with-options-grouped-product-item' => array(
		'name' => 'woocommerce/add-to-cart-with-options-grouped-product-item',
		'title' => 'Grouped Product: Template (Beta)',
		'description' => 'A list item template that represents a child product within the Grouped Product Selector block.',
		'category' => 'woocommerce-product-elements',
		'keywords' => array(
			'WooCommerce'
		),
		'usesContext' => array(
			'postId'
		),
		'ancestor' => array(
			'woocommerce/add-to-cart-with-options-grouped-product-selector'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'supports' => array(
			'inserter' => false,
			'interactivity' => true
		),
		'style' => 'file:../woocommerce/add-to-cart-with-options-grouped-product-item-style.css'
	),
	'add-to-cart-with-options-grouped-product-item-label' => array(
		'name' => 'woocommerce/add-to-cart-with-options-grouped-product-item-label',
		'title' => 'Grouped Product: Item Label (Beta)',
		'description' => 'Display the product title as a label or paragraph.',
		'category' => 'woocommerce-product-elements',
		'keywords' => array(
			'WooCommerce'
		),
		'ancestor' => array(
			'woocommerce/add-to-cart-with-options-grouped-product-item'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'supports' => array(
			'color' => array(
				'text' => true,
				'background' => true,
				'gradients' => true,
				'__experimentalDefaultControls' => array(
					'background' => true,
					'text' => true
				)
			),
			'html' => false,
			'layout' => array(
				'selfStretch' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true,
				'blockGap' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'textAlign' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true,
					'fontWeight' => true,
					'fontStyle' => true
				)
			)
		),
		'usesContext' => array(
			'postId',
			'postType'
		)
	),
	'add-to-cart-with-options-grouped-product-item-selector' => array(
		'name' => 'woocommerce/add-to-cart-with-options-grouped-product-item-selector',
		'title' => 'Grouped Product: Item Selector (Beta)',
		'description' => 'Add a way of selecting a child product within the Grouped Product block. Depending on the type of product and its properties, this might be a button, a checkbox, or a link.',
		'category' => 'woocommerce-product-elements',
		'keywords' => array(
			'WooCommerce'
		),
		'usesContext' => array(
			'postId'
		),
		'ancestor' => array(
			'woocommerce/add-to-cart-with-options-grouped-product-item'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'supports' => array(
			'inserter' => false,
			'interactivity' => true
		),
		'style' => 'file:../woocommerce/add-to-cart-with-options-grouped-product-item-selector-style.css'
	),
	'add-to-cart-with-options-grouped-product-selector' => array(
		'name' => 'woocommerce/add-to-cart-with-options-grouped-product-selector',
		'title' => 'Grouped Product Selector (Beta)',
		'description' => 'Display a group of products that can be added to the cart.',
		'category' => 'woocommerce-product-elements',
		'keywords' => array(
			'WooCommerce'
		),
		'usesContext' => array(
			'postId'
		),
		'ancestor' => array(
			'woocommerce/add-to-cart-with-options'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'supports' => array(
			'interactivity' => true
		),
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'viewScriptModule' => 'woocommerce/add-to-cart-with-options-grouped-product-selector'
	),
	'add-to-cart-with-options-quantity-selector' => array(
		'name' => 'woocommerce/add-to-cart-with-options-quantity-selector',
		'title' => 'Product Quantity (Beta)',
		'description' => 'Display an input field customers can use to select the number of products to add to their cart. ',
		'category' => 'woocommerce-product-elements',
		'keywords' => array(
			'WooCommerce'
		),
		'usesContext' => array(
			'postId'
		),
		'ancestor' => array(
			'woocommerce/add-to-cart-with-options'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'supports' => array(
			'interactivity' => true
		),
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'style' => 'file:../woocommerce/add-to-cart-with-options-quantity-selector-style.css',
		'viewScriptModule' => 'woocommerce/add-to-cart-with-options-quantity-selector'
	),
	'add-to-cart-with-options-variation-description' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/add-to-cart-with-options-variation-description',
		'title' => 'Variation Description (Beta)',
		'description' => 'Displays the description of the selected variation.',
		'category' => 'woocommerce',
		'textdomain' => 'woocommerce',
		'ancestor' => array(
			'woocommerce/add-to-cart-with-options'
		),
		'supports' => array(
			'interactivity' => true,
			'html' => false,
			'dimensions' => array(
				'minHeight' => true
			),
			'spacing' => array(
				'padding' => true,
				'margin' => true
			),
			'color' => array(
				'gradients' => true,
				'link' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true
			),
			'__experimentalBorder' => array(
				'radius' => true,
				'color' => true,
				'width' => true,
				'style' => true
			)
		),
		'viewScriptModule' => 'woocommerce/product-elements'
	),
	'add-to-cart-with-options-variation-selector' => array(
		'name' => 'woocommerce/add-to-cart-with-options-variation-selector',
		'title' => 'Variation Selector (Beta)',
		'description' => 'Display any product variations available to select from and add to cart.',
		'category' => 'woocommerce-product-elements',
		'keywords' => array(
			'WooCommerce'
		),
		'usesContext' => array(
			'postId'
		),
		'ancestor' => array(
			'woocommerce/add-to-cart-with-options'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'supports' => array(
			'interactivity' => true
		),
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'viewScriptModule' => 'woocommerce/add-to-cart-with-options-variation-selector'
	),
	'add-to-cart-with-options-variation-selector-attribute' => array(
		'name' => 'woocommerce/add-to-cart-with-options-variation-selector-attribute',
		'title' => 'Variation Selector: Template (Beta)',
		'description' => 'A template for attribute name and options that will be applied to all variable products with attributes.',
		'category' => 'woocommerce-product-elements',
		'keywords' => array(
			'WooCommerce'
		),
		'usesContext' => array(
			'postId'
		),
		'ancestor' => array(
			'woocommerce/add-to-cart-with-options-variation-selector'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'supports' => array(
			'inserter' => false,
			'interactivity' => true
		)
	),
	'add-to-cart-with-options-variation-selector-attribute-name' => array(
		'name' => 'woocommerce/add-to-cart-with-options-variation-selector-attribute-name',
		'title' => 'Variation Selector: Attribute Name (Beta)',
		'description' => 'Format the name of an attribute associated with a variable product.',
		'category' => 'woocommerce-product-elements',
		'keywords' => array(
			'WooCommerce'
		),
		'ancestor' => array(
			'woocommerce/add-to-cart-with-options-variation-selector-attribute'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'supports' => array(
			'inserter' => false,
			'interactivity' => true,
			'align' => false,
			'alignWide' => false,
			'color' => array(
				'__experimentalSkipSerialization' => true,
				'gradients' => true,
				'__experimentalDefaultControls' => array(
					'background' => true,
					'text' => true
				)
			),
			'typography' => array(
				'__experimentalSkipSerialization' => array(
					'fontSize',
					'lineHeight',
					'fontFamily',
					'fontWeight',
					'fontStyle',
					'textTransform',
					'textDecoration',
					'letterSpacing'
				),
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalWritingMode' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'spacing' => array(
				'__experimentalSkipSerialization' => true,
				'padding' => array(
					'horizontal',
					'vertical'
				),
				'__experimentalDefaultControls' => array(
					'padding' => true
				)
			)
		),
		'usesContext' => array(
			'woocommerce/attributeId',
			'woocommerce/attributeName',
			'woocommerce/attributeTerms'
		),
		'style' => 'file:../woocommerce/add-to-cart-with-options-variation-selector-attribute-name-style.css'
	),
	'add-to-cart-with-options-variation-selector-attribute-options' => array(
		'name' => 'woocommerce/add-to-cart-with-options-variation-selector-attribute-options',
		'title' => 'Variation Selector: Attribute Options (Beta)',
		'description' => 'Display the attribute options associated with a variable product.',
		'category' => 'woocommerce-product-elements',
		'keywords' => array(
			'WooCommerce'
		),
		'ancestor' => array(
			'woocommerce/add-to-cart-with-options-variation-selector-attribute'
		),
		'attributes' => array(
			'optionStyle' => array(
				'type' => 'string',
				'enum' => array(
					'pills',
					'dropdown'
				)
			),
			'autoselect' => array(
				'type' => 'boolean',
				'default' => false
			),
			'disabledAttributesAction' => array(
				'type' => 'string',
				'enum' => array(
					'disable',
					'hide'
				),
				'default' => 'disable'
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'supports' => array(
			'inserter' => false,
			'interactivity' => true
		),
		'usesContext' => array(
			'woocommerce/attributeId',
			'woocommerce/attributeName',
			'woocommerce/attributeTerms'
		),
		'style' => 'file:../woocommerce/add-to-cart-with-options-variation-selector-attribute-options-style.css'
	),
	'all-products' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'textdomain' => 'woocommerce',
		'name' => 'woocommerce/all-products',
		'title' => 'All Products',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'description' => 'Display products from your store in a grid layout.',
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => false
			),
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'multiple' => false,
			'inserter' => false
		),
		'attributes' => array(
			'columns' => array(
				'type' => 'number'
			),
			'rows' => array(
				'type' => 'number'
			),
			'alignButtons' => array(
				'type' => 'boolean'
			),
			'contentVisibility' => array(
				'type' => 'object'
			),
			'orderby' => array(
				'type' => 'string'
			),
			'layoutConfig' => array(
				'type' => 'array'
			),
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			)
		)
	),
	'all-reviews' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/all-reviews',
		'title' => 'All Reviews',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'description' => 'Show a list of all product reviews.',
		'textdomain' => 'woocommerce',
		'supports' => array(
			'html' => false,
			'interactivity' => array(
				'clientNavigation' => true
			),
			'color' => array(
				'background' => false
			),
			'typography' => array(
				'fontSize' => true
			)
		)
	),
	'attribute-filter' => array(
		'name' => 'woocommerce/attribute-filter',
		'title' => 'Filter by Attribute Controls',
		'description' => 'Enable customers to filter the product grid by selecting one or more attributes, such as color.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'html' => false,
			'color' => array(
				'text' => true,
				'background' => false
			),
			'inserter' => false,
			'lock' => false,
			'interactivity' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'attributeId' => array(
				'type' => 'number',
				'default' => 0
			),
			'showCounts' => array(
				'type' => 'boolean',
				'default' => false
			),
			'queryType' => array(
				'type' => 'string',
				'default' => 'or'
			),
			'headingLevel' => array(
				'type' => 'number',
				'default' => 3
			),
			'displayStyle' => array(
				'type' => 'string',
				'default' => 'list'
			),
			'showFilterButton' => array(
				'type' => 'boolean',
				'default' => false
			),
			'selectType' => array(
				'type' => 'string',
				'default' => 'multiple'
			),
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'breadcrumbs' => array(
		'name' => 'woocommerce/breadcrumbs',
		'title' => 'Store Breadcrumbs',
		'description' => 'Enable customers to keep track of their location within the store and navigate back to parent pages.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'textdomain' => 'woocommerce',
		'attributes' => array(
			'contentJustification' => array(
				'type' => 'string'
			),
			'fontSize' => array(
				'type' => 'string',
				'default' => 'small'
			),
			'align' => array(
				'type' => 'string',
				'default' => 'wide'
			)
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'align' => array(
				'wide',
				'full'
			),
			'color' => array(
				'background' => false,
				'link' => true
			),
			'html' => false,
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontStyle' => true,
				'__experimentalFontWeight' => true,
				'__experimentalTextTransform' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			)
		),
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'cart-link' => array(
		'name' => 'woocommerce/cart-link',
		'title' => 'Cart Link',
		'icon' => 'cart',
		'description' => 'Display a link to the cart.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'textdomain' => 'woocommerce',
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'html' => false,
			'multiple' => false,
			'typography' => array(
				'fontSize' => true
			),
			'color' => array(
				'text' => false,
				'link' => true
			),
			'spacing' => array(
				'padding' => true
			)
		),
		'example' => array(
			'attributes' => array(
				'isPreview' => true,
				'cartIcon' => 'cart',
				'content' => 'Cart'
			)
		),
		'attributes' => array(
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			),
			'cartIcon' => array(
				'type' => 'string',
				'default' => 'cart'
			),
			'content' => array(
				'type' => 'string',
				'default' => null
			)
		),
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'catalog-sorting' => array(
		'name' => 'woocommerce/catalog-sorting',
		'title' => 'Catalog Sorting',
		'description' => 'Enable customers to change the sorting order of the products.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'color' => array(
				'text' => true,
				'background' => false
			),
			'typography' => array(
				'fontSize' => true
			)
		),
		'attributes' => array(
			'fontSize' => array(
				'type' => 'string',
				'default' => 'small'
			),
			'useLabel' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'category-description' => array(
		'name' => 'woocommerce/category-description',
		'title' => 'Product Category Description',
		'description' => 'Displays the current category description.',
		'category' => 'woocommerce',
		'apiVersion' => 3,
		'textdomain' => 'woocommerce',
		'attributes' => array(
			'textAlign' => array(
				'type' => 'string'
			)
		),
		'supports' => array(
			'align' => false,
			'color' => array(
				'background' => true,
				'text' => true
			),
			'html' => false,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => true
		),
		'usesContext' => array(
			'termId',
			'termTaxonomy'
		)
	),
	'category-title' => array(
		'name' => 'woocommerce/category-title',
		'title' => 'Product Category Title',
		'description' => 'Displays the current category title and lets permitted users edit it.',
		'category' => 'woocommerce',
		'apiVersion' => 3,
		'textdomain' => 'woocommerce',
		'attributes' => array(
			'isLink' => array(
				'type' => 'boolean',
				'default' => false
			),
			'level' => array(
				'type' => 'number',
				'default' => 2
			),
			'linkTarget' => array(
				'type' => 'string',
				'default' => '_self'
			),
			'rel' => array(
				'type' => 'string',
				'default' => ''
			),
			'textAlign' => array(
				'type' => 'string'
			)
		),
		'supports' => array(
			'align' => false,
			'color' => array(
				'background' => true,
				'text' => true
			),
			'html' => false,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => true
		),
		'usesContext' => array(
			'termId',
			'termTaxonomy'
		)
	),
	'checkout' => array(
		'name' => 'woocommerce/checkout',
		'version' => '1.0.0',
		'title' => 'Checkout',
		'description' => 'Display a checkout form so your customers can submit orders.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'align' => array(
				'wide'
			),
			'html' => false,
			'multiple' => false
		),
		'example' => array(
			'attributes' => array(
				'isPreview' => true
			),
			'viewportWidth' => 800
		),
		'attributes' => array(
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false,
				'save' => false
			),
			'align' => array(
				'type' => 'string',
				'default' => 'wide'
			),
			'showFormStepNumbers' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'classic-shortcode' => array(
		'name' => 'woocommerce/classic-shortcode',
		'title' => 'Classic Shortcode',
		'description' => 'Renders classic WooCommerce shortcodes.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => false
			),
			'align' => true,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => true
		),
		'attributes' => array(
			'shortcode' => array(
				'type' => 'string',
				'default' => 'cart',
				'enum' => array(
					'cart',
					'checkout'
				)
			),
			'align' => array(
				'type' => 'string',
				'default' => 'wide'
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'coming-soon' => array(
		'name' => 'woocommerce/coming-soon',
		'category' => 'woocommerce',
		'title' => 'Coming Soon',
		'attributes' => array(
			'color' => array(
				'type' => 'string'
			),
			'storeOnly' => array(
				'type' => 'boolean',
				'default' => false
			),
			'comingSoonPatternId' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'supports' => array(
			'color' => array(
				'background' => true,
				'text' => true
			),
			'inserter' => false
		)
	),
	'coupon-code' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/coupon-code',
		'version' => '1.0.0',
		'title' => 'Coupon Code',
		'category' => 'woocommerce',
		'description' => 'Include a coupon code to entice customers to make a purchase.',
		'supports' => array(
			'email' => true,
			'html' => false,
			'align' => true,
			'color' => array(
				'text' => true,
				'background' => true
			),
			'typography' => array(
				'fontSize' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'style' => true,
				'width' => true
			)
		),
		'attributes' => array(
			'couponCode' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'textdomain' => 'woocommerce'
	),
	'customer-account' => array(
		'name' => 'woocommerce/customer-account',
		'title' => 'Customer account',
		'description' => 'A block that allows your customers to log in and out of their accounts in your store.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce',
			'My Account'
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'align' => true,
			'color' => array(
				'text' => true
			),
			'typography' => array(
				'fontSize' => true,
				'__experimentalFontFamily' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'attributes' => array(
			'displayStyle' => array(
				'type' => 'string',
				'default' => 'icon_and_text'
			),
			'iconStyle' => array(
				'type' => 'string',
				'default' => 'default'
			),
			'iconClass' => array(
				'type' => 'string',
				'default' => 'icon'
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'email-content' => array(
		'name' => 'woocommerce/email-content',
		'title' => 'Email Content',
		'description' => 'A placeholder block for email content.',
		'category' => 'woocommerce',
		'textdomain' => 'woocommerce',
		'supports' => array(
			'inserter' => false,
			'email' => true
		),
		'attributes' => array(
			'emailType' => array(
				'type' => 'string'
			),
			'postId' => array(
				'type' => 'integer'
			)
		),
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'featured-category' => array(
		'name' => 'woocommerce/featured-category',
		'title' => 'Featured Category',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'description' => 'Visually highlight a product category and encourage prompt action.',
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'align' => array(
				'wide',
				'full'
			),
			'ariaLabel' => true,
			'color' => array(
				'background' => true,
				'text' => true
			),
			'html' => false,
			'filter' => array(
				'duotone' => true
			),
			'spacing' => array(
				'padding' => true,
				'__experimentalDefaultControls' => array(
					'padding' => true
				),
				'__experimentalSkipSerialization' => true
			),
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'width' => true,
				'__experimentalDefaultControls' => array(
					'color' => true,
					'radius' => true,
					'width' => true
				),
				'__experimentalSkipSerialization' => true
			)
		),
		'attributes' => array(
			'alt' => array(
				'type' => 'string',
				'default' => ''
			),
			'contentAlign' => array(
				'type' => 'string',
				'default' => 'center'
			),
			'dimRatio' => array(
				'type' => 'number',
				'default' => 50
			),
			'focalPoint' => array(
				'type' => 'object',
				'default' => array(
					'x' => 0.5,
					'y' => 0.5
				)
			),
			'imageFit' => array(
				'type' => 'string',
				'default' => 'none'
			),
			'hasParallax' => array(
				'type' => 'boolean',
				'default' => false
			),
			'isRepeated' => array(
				'type' => 'boolean',
				'default' => false
			),
			'mediaId' => array(
				'type' => 'number',
				'default' => 0
			),
			'mediaSrc' => array(
				'type' => 'string',
				'default' => ''
			),
			'minHeight' => array(
				'type' => 'number',
				'default' => 500
			),
			'linkText' => array(
				'default' => 'Shop now',
				'type' => 'string'
			),
			'categoryId' => array(
				'type' => 'number'
			),
			'overlayColor' => array(
				'type' => 'string',
				'default' => '#000000'
			),
			'overlayGradient' => array(
				'type' => 'string'
			),
			'previewCategory' => array(
				'type' => 'object',
				'default' => null
			)
		),
		'selectors' => array(
			'filter' => array(
				'duotone' => '.wp-block-woocommerce-featured-category .wc-block-featured-category__background-image'
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'usesContext' => array(
			'termId',
			'termTaxonomy'
		),
		'providesContext' => array(
			'termId' => 'categoryId',
			'termTaxonomy' => 'termTaxonomy'
		)
	),
	'featured-product' => array(
		'name' => 'woocommerce/featured-product',
		'title' => 'Featured Product',
		'description' => 'Highlight a product or variation.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'align' => array(
				'wide',
				'full'
			),
			'ariaLabel' => true,
			'html' => false,
			'filter' => array(
				'duotone' => true
			),
			'color' => array(
				'background' => true,
				'text' => true
			),
			'spacing' => array(
				'padding' => true,
				'__experimentalDefaultControls' => array(
					'padding' => true
				),
				'__experimentalSkipSerialization' => true
			),
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'width' => true,
				'__experimentalDefaultControls' => array(
					'color' => true,
					'radius' => true,
					'width' => true
				),
				'__experimentalSkipSerialization' => true
			),
			'multiple' => true
		),
		'attributes' => array(
			'alt' => array(
				'type' => 'string',
				'default' => ''
			),
			'contentAlign' => array(
				'type' => 'string',
				'default' => 'center'
			),
			'dimRatio' => array(
				'type' => 'number',
				'default' => 50
			),
			'focalPoint' => array(
				'type' => 'object',
				'default' => array(
					'x' => 0.5,
					'y' => 0.5
				)
			),
			'imageFit' => array(
				'type' => 'string',
				'default' => 'none'
			),
			'hasParallax' => array(
				'type' => 'boolean',
				'default' => false
			),
			'isRepeated' => array(
				'type' => 'boolean',
				'default' => false
			),
			'mediaId' => array(
				'type' => 'number',
				'default' => 0
			),
			'mediaSrc' => array(
				'type' => 'string',
				'default' => ''
			),
			'minHeight' => array(
				'type' => 'number',
				'default' => 500
			),
			'linkText' => array(
				'type' => 'string',
				'default' => 'Shop now'
			),
			'overlayColor' => array(
				'type' => 'string',
				'default' => '#000000'
			),
			'overlayGradient' => array(
				'type' => 'string'
			),
			'productId' => array(
				'type' => 'number'
			),
			'previewProduct' => array(
				'type' => 'object',
				'default' => null
			)
		),
		'selectors' => array(
			'filter' => array(
				'duotone' => '.wp-block-woocommerce-featured-product .wc-block-featured-product__background-image'
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'filter-wrapper' => array(
		'name' => 'woocommerce/filter-wrapper',
		'title' => 'Filter Block',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => false
			),
			'inserter' => false
		),
		'attributes' => array(
			'filterType' => array(
				'type' => 'string'
			),
			'heading' => array(
				'type' => 'string'
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'handpicked-products' => array(
		'name' => 'woocommerce/handpicked-products',
		'title' => 'Hand-picked Products',
		'category' => 'woocommerce',
		'keywords' => array(
			'Handpicked Products',
			'WooCommerce'
		),
		'description' => 'Display a selection of hand-picked products in a grid.',
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => false
			),
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'inserter' => false
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string'
			),
			'columns' => array(
				'type' => 'number',
				'default' => 3
			),
			'contentVisibility' => array(
				'type' => 'object',
				'default' => array(
					'image' => true,
					'title' => true,
					'price' => true,
					'rating' => true,
					'button' => true
				),
				'properties' => array(
					'image' => array(
						'type' => 'boolean',
						'image' => true
					),
					'title' => array(
						'type' => 'boolean',
						'title' => true
					),
					'price' => array(
						'type' => 'boolean',
						'price' => true
					),
					'rating' => array(
						'type' => 'boolean',
						'rating' => true
					),
					'button' => array(
						'type' => 'boolean',
						'button' => true
					)
				)
			),
			'orderby' => array(
				'type' => 'string',
				'enum' => array(
					'date',
					'popularity',
					'price_asc',
					'price_desc',
					'rating',
					'title',
					'menu_order'
				),
				'default' => 'date'
			),
			'products' => array(
				'type' => 'array',
				'default' => array(
					
				)
			),
			'alignButtons' => array(
				'type' => 'boolean',
				'default' => false
			),
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'mini-cart' => array(
		'name' => 'woocommerce/mini-cart',
		'version' => '1.0.0',
		'title' => 'Mini-Cart',
		'icon' => 'miniCartAlt',
		'description' => 'Display a button for shoppers to quickly view their cart.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'textdomain' => 'woocommerce',
		'supports' => array(
			'html' => false,
			'multiple' => false,
			'typography' => array(
				'fontSize' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'example' => array(
			'attributes' => array(
				'isPreview' => true,
				'className' => 'wc-block-mini-cart--preview'
			)
		),
		'attributes' => array(
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			),
			'miniCartIcon' => array(
				'type' => 'string',
				'default' => 'cart'
			),
			'addToCartBehaviour' => array(
				'type' => 'string',
				'default' => 'none'
			),
			'onCartClickBehaviour' => array(
				'type' => 'string',
				'default' => 'open_drawer'
			),
			'hasHiddenPrice' => array(
				'type' => 'boolean',
				'default' => true
			),
			'cartAndCheckoutRenderStyle' => array(
				'type' => 'string',
				'default' => 'hidden'
			),
			'priceColor' => array(
				'type' => 'object'
			),
			'priceColorValue' => array(
				'type' => 'string'
			),
			'iconColor' => array(
				'type' => 'object'
			),
			'iconColorValue' => array(
				'type' => 'string'
			),
			'productCountColor' => array(
				'type' => 'object'
			),
			'productCountColorValue' => array(
				'type' => 'string'
			),
			'productCountVisibility' => array(
				'type' => 'string',
				'default' => 'greater_than_zero'
			)
		),
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'mini-cart-contents' => array(
		'name' => 'woocommerce/mini-cart-contents',
		'version' => '1.0.0',
		'title' => 'Mini-Cart Contents',
		'description' => 'Display a Mini-Cart widget.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'color' => array(
				'text' => true,
				'background' => true,
				'link' => true
			),
			'lock' => false,
			'__experimentalBorder' => array(
				'color' => true,
				'width' => true
			)
		),
		'attributes' => array(
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			),
			'width' => array(
				'type' => 'string',
				'default' => '480px'
			)
		),
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'order-confirmation-additional-fields' => array(
		'name' => 'woocommerce/order-confirmation-additional-fields',
		'version' => '1.0.0',
		'title' => 'Additional Field List',
		'description' => 'Display the list of additional field values from the current order.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'multiple' => false,
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'width' => true,
				'style' => true,
				'__experimentalDefaultControls' => array(
					'width' => true,
					'color' => true
				)
			),
			'spacing' => array(
				'padding' => true,
				'margin' => true,
				'__experimentalDefaultControls' => array(
					'margin' => false,
					'padding' => false
				)
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'wide'
			),
			'className' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'order-confirmation-additional-fields-wrapper' => array(
		'name' => 'woocommerce/order-confirmation-additional-fields-wrapper',
		'version' => '1.0.0',
		'title' => 'Additional Fields',
		'description' => 'Display additional checkout fields from the \'contact\' and \'order\' locations.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'attributes' => array(
			'heading' => array(
				'type' => 'string'
			)
		),
		'supports' => array(
			'multiple' => false,
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'spacing' => array(
				'padding' => true,
				'margin' => true,
				'__experimentalDefaultControls' => array(
					'margin' => false,
					'padding' => false
				)
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'order-confirmation-additional-information' => array(
		'name' => 'woocommerce/order-confirmation-additional-information',
		'version' => '1.0.0',
		'title' => 'Additional Information',
		'description' => 'Displays additional information provided by third-party extensions for the current order.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'multiple' => false,
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'width' => true,
				'style' => true,
				'__experimentalDefaultControls' => array(
					'width' => true,
					'color' => true
				)
			),
			'spacing' => array(
				'padding' => true,
				'margin' => true,
				'__experimentalDefaultControls' => array(
					'margin' => false,
					'padding' => false
				)
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'wide'
			),
			'className' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'order-confirmation-billing-address' => array(
		'name' => 'woocommerce/order-confirmation-billing-address',
		'version' => '1.0.0',
		'title' => 'Billing Address',
		'description' => 'Display the order confirmation billing address.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'multiple' => false,
			'align' => array(
				'wide',
				'full'
			),
			'inserter' => false,
			'html' => false,
			'color' => array(
				'text' => true,
				'background' => true,
				'__experimentalDefaultControls' => array(
					'background' => true,
					'text' => true
				)
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'width' => true,
				'style' => true,
				'__experimentalDefaultControls' => array(
					'width' => true,
					'color' => true
				)
			),
			'spacing' => array(
				'padding' => true,
				'margin' => true,
				'__experimentalDefaultControls' => array(
					'margin' => false,
					'padding' => false
				)
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'wide'
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'order-confirmation-billing-wrapper' => array(
		'name' => 'woocommerce/order-confirmation-billing-wrapper',
		'version' => '1.0.0',
		'title' => 'Billing Address Section',
		'description' => 'Display the order confirmation billing section.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'attributes' => array(
			'heading' => array(
				'type' => 'string'
			)
		),
		'supports' => array(
			'multiple' => false,
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'spacing' => array(
				'padding' => true,
				'margin' => true,
				'__experimentalDefaultControls' => array(
					'margin' => false,
					'padding' => false
				)
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'order-confirmation-create-account' => array(
		'name' => 'woocommerce/order-confirmation-create-account',
		'version' => '1.0.0',
		'title' => 'Account Creation',
		'description' => 'Allow customers to create an account after their purchase.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'attributes' => array(
			'customerEmail' => array(
				'type' => 'string',
				'default' => ''
			),
			'nonceToken' => array(
				'type' => 'string',
				'default' => ''
			),
			'align' => array(
				'type' => 'string',
				'default' => 'wide'
			),
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'hasDarkControls' => array(
				'type' => 'boolean',
				'default' => false
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true
				)
			)
		),
		'supports' => array(
			'multiple' => false,
			'inserter' => false,
			'html' => false,
			'lock' => false,
			'align' => array(
				'wide',
				'full'
			),
			'color' => array(
				'background' => true,
				'text' => true,
				'button' => true
			),
			'spacing' => array(
				'padding' => true,
				'margin' => true,
				'__experimentalDefaultControls' => array(
					'margin' => false,
					'padding' => false
				)
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'order-confirmation-downloads' => array(
		'name' => 'woocommerce/order-confirmation-downloads',
		'version' => '1.0.0',
		'title' => 'Order Downloads',
		'description' => 'Display links to purchased downloads.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'multiple' => false,
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalFontStyle' => true,
				'__experimentalFontWeight' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalTextTransform' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'color' => array(
				'background' => true,
				'text' => true,
				'link' => true,
				'gradients' => true,
				'__experimentalDefaultControls' => array(
					'background' => true,
					'text' => true
				)
			),
			'spacing' => array(
				'padding' => true,
				'margin' => true,
				'__experimentalDefaultControls' => array(
					'margin' => false,
					'padding' => false
				)
			),
			'__experimentalBorder' => array(
				'color' => true,
				'style' => true,
				'width' => true,
				'__experimentalDefaultControls' => array(
					'color' => true,
					'style' => true,
					'width' => true
				)
			),
			'__experimentalSelector' => '.wp-block-woocommerce-order-confirmation-totals table'
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'wide'
			),
			'className' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'order-confirmation-downloads-wrapper' => array(
		'name' => 'woocommerce/order-confirmation-downloads-wrapper',
		'version' => '1.0.0',
		'title' => 'Downloads Section',
		'description' => 'Display the downloadable products section.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'attributes' => array(
			'heading' => array(
				'type' => 'string'
			)
		),
		'supports' => array(
			'multiple' => false,
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'spacing' => array(
				'padding' => true,
				'margin' => true,
				'__experimentalDefaultControls' => array(
					'margin' => false,
					'padding' => false
				)
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'order-confirmation-shipping-address' => array(
		'name' => 'woocommerce/order-confirmation-shipping-address',
		'version' => '1.0.0',
		'title' => 'Shipping Address',
		'description' => 'Display the order confirmation shipping address.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'multiple' => false,
			'align' => array(
				'wide',
				'full'
			),
			'inserter' => false,
			'html' => false,
			'color' => array(
				'text' => true,
				'background' => true,
				'__experimentalDefaultControls' => array(
					'background' => true,
					'text' => true
				)
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'width' => true,
				'style' => true,
				'__experimentalDefaultControls' => array(
					'width' => true,
					'color' => true
				)
			),
			'spacing' => array(
				'padding' => true,
				'margin' => true,
				'__experimentalDefaultControls' => array(
					'margin' => false,
					'padding' => false
				)
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'wide'
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'order-confirmation-shipping-wrapper' => array(
		'name' => 'woocommerce/order-confirmation-shipping-wrapper',
		'version' => '1.0.0',
		'title' => 'Shipping Address Section',
		'description' => 'Display the order confirmation shipping section.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'attributes' => array(
			'heading' => array(
				'type' => 'string',
				'default' => 'Shipping'
			)
		),
		'supports' => array(
			'multiple' => false,
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'spacing' => array(
				'padding' => true,
				'margin' => true,
				'__experimentalDefaultControls' => array(
					'margin' => false,
					'padding' => false
				)
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'order-confirmation-status' => array(
		'name' => 'woocommerce/order-confirmation-status',
		'version' => '1.0.0',
		'title' => 'Order Status',
		'description' => 'Display a "thank you" message, or a sentence regarding the current order status.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'multiple' => false,
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalFontStyle' => true,
				'__experimentalFontWeight' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalTextTransform' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'color' => array(
				'background' => true,
				'text' => true,
				'gradients' => true,
				'__experimentalDefaultControls' => array(
					'background' => true,
					'text' => true
				)
			),
			'spacing' => array(
				'padding' => true,
				'margin' => true,
				'__experimentalDefaultControls' => array(
					'margin' => false,
					'padding' => false
				)
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'wide'
			),
			'className' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'order-confirmation-summary' => array(
		'name' => 'woocommerce/order-confirmation-summary',
		'version' => '1.0.0',
		'title' => 'Order Summary',
		'description' => 'Display the order summary on the order confirmation page.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'multiple' => false,
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalFontStyle' => true,
				'__experimentalFontWeight' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalTextTransform' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'color' => array(
				'background' => true,
				'text' => true,
				'gradients' => true,
				'__experimentalDefaultControls' => array(
					'background' => true,
					'text' => true
				)
			),
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'width' => true,
				'__experimentalDefaultControls' => array(
					'width' => true,
					'color' => true
				)
			),
			'spacing' => array(
				'padding' => true,
				'margin' => true,
				'__experimentalDefaultControls' => array(
					'margin' => false,
					'padding' => false
				)
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'wide'
			),
			'className' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'order-confirmation-totals' => array(
		'name' => 'woocommerce/order-confirmation-totals',
		'version' => '1.0.0',
		'title' => 'Order Totals',
		'description' => 'Display the items purchased and order totals.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'multiple' => false,
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalFontStyle' => true,
				'__experimentalFontWeight' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalTextTransform' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'color' => array(
				'background' => true,
				'text' => true,
				'link' => true,
				'gradients' => true,
				'__experimentalDefaultControls' => array(
					'background' => true,
					'text' => true
				)
			),
			'spacing' => array(
				'padding' => true,
				'margin' => true,
				'__experimentalDefaultControls' => array(
					'margin' => false,
					'padding' => false
				)
			),
			'__experimentalBorder' => array(
				'color' => true,
				'style' => true,
				'width' => true,
				'__experimentalDefaultControls' => array(
					'color' => true,
					'style' => true,
					'width' => true
				)
			),
			'__experimentalSelector' => '.wp-block-woocommerce-order-confirmation-totals table'
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'wide'
			),
			'className' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'order-confirmation-totals-wrapper' => array(
		'name' => 'woocommerce/order-confirmation-totals-wrapper',
		'version' => '1.0.0',
		'title' => 'Order Totals Section',
		'description' => 'Display the order details section.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'attributes' => array(
			'heading' => array(
				'type' => 'string'
			)
		),
		'supports' => array(
			'multiple' => false,
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'spacing' => array(
				'padding' => true,
				'margin' => true,
				'__experimentalDefaultControls' => array(
					'margin' => false,
					'padding' => false
				)
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'page-content-wrapper' => array(
		'name' => 'woocommerce/page-content-wrapper',
		'title' => 'WooCommerce Page',
		'description' => 'Displays WooCommerce page content.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'textdomain' => 'woocommerce',
		'supports' => array(
			'html' => false,
			'multiple' => false,
			'inserter' => false
		),
		'attributes' => array(
			'page' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'providesContext' => array(
			'postId' => 'postId',
			'postType' => 'postType'
		),
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'payment-method-icons' => array(
		'name' => 'woocommerce/payment-method-icons',
		'version' => '1.0.0',
		'title' => 'Payment Method Icons',
		'description' => 'Display icons for available payment methods.',
		'category' => 'woocommerce',
		'keywords' => array(
			'woocommerce',
			'payments',
			'payment methods'
		),
		'textdomain' => 'woocommerce',
		'attributes' => array(
			'numberOfIcons' => array(
				'type' => 'number',
				'default' => 0
			)
		),
		'supports' => array(
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'price-filter' => array(
		'name' => 'woocommerce/price-filter',
		'title' => 'Filter by Price Controls',
		'description' => 'Enable customers to filter the product grid by choosing a price range.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => false
			),
			'html' => false,
			'multiple' => false,
			'color' => array(
				'text' => true,
				'background' => false
			),
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'showInputFields' => array(
				'type' => 'boolean',
				'default' => true
			),
			'inlineInput' => array(
				'type' => 'boolean',
				'default' => false
			),
			'showFilterButton' => array(
				'type' => 'boolean',
				'default' => false
			),
			'headingLevel' => array(
				'type' => 'number',
				'default' => 3
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'product-average-rating' => array(
		'name' => 'woocommerce/product-average-rating',
		'title' => 'Product Average Rating (Beta)',
		'description' => 'Display the average rating of a product',
		'apiVersion' => 3,
		'category' => 'woocommerce-product-elements',
		'attributes' => array(
			'textAlign' => array(
				'type' => 'string'
			)
		),
		'keywords' => array(
			'WooCommerce'
		),
		'ancestor' => array(
			'woocommerce/single-product'
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'color' => array(
				'text' => true,
				'background' => true,
				'__experimentalSkipSerialization' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true,
				'__experimentalSkipSerialization' => true
			),
			'typography' => array(
				'fontSize' => true,
				'__experimentalFontWeight' => true,
				'__experimentalSkipSerialization' => true
			),
			'__experimentalSelector' => '.wc-block-components-product-average-rating'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'product-best-sellers' => array(
		'name' => 'woocommerce/product-best-sellers',
		'title' => 'Best Selling Products',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'description' => 'Display a grid of your all-time best selling products.',
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => false
			),
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'inserter' => false
		),
		'attributes' => array(
			'columns' => array(
				'type' => 'number',
				'default' => 3
			),
			'rows' => array(
				'type' => 'number',
				'default' => 3
			),
			'alignButtons' => array(
				'type' => 'boolean',
				'default' => false
			),
			'contentVisibility' => array(
				'type' => 'object',
				'default' => array(
					'image' => true,
					'title' => true,
					'price' => true,
					'rating' => true,
					'button' => true
				),
				'properties' => array(
					'image' => array(
						'type' => 'boolean',
						'default' => true
					),
					'title' => array(
						'type' => 'boolean',
						'default' => true
					),
					'price' => array(
						'type' => 'boolean',
						'default' => true
					),
					'rating' => array(
						'type' => 'boolean',
						'default' => true
					),
					'button' => array(
						'type' => 'boolean',
						'default' => true
					)
				)
			),
			'categories' => array(
				'type' => 'array',
				'default' => array(
					
				)
			),
			'catOperator' => array(
				'type' => 'string',
				'default' => 'any'
			),
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			),
			'stockStatus' => array(
				'type' => 'array'
			),
			'editMode' => array(
				'type' => 'boolean',
				'default' => true
			),
			'orderby' => array(
				'type' => 'string',
				'enum' => array(
					'date',
					'popularity',
					'price_asc',
					'price_desc',
					'rating',
					'title',
					'menu_order'
				),
				'default' => 'popularity'
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'product-button' => array(
		'name' => 'woocommerce/product-button',
		'title' => 'Add to Cart Button',
		'description' => 'Display a call to action button which either adds the product to the cart, or links to the product page.',
		'category' => 'woocommerce-product-elements',
		'keywords' => array(
			'WooCommerce'
		),
		'usesContext' => array(
			'query',
			'queryId',
			'postId',
			'collection',
			'woocommerce/isDescendantOfAddToCartWithOptions'
		),
		'textdomain' => 'woocommerce',
		'attributes' => array(
			'productId' => array(
				'type' => 'number',
				'default' => 0
			),
			'textAlign' => array(
				'type' => 'string',
				'default' => ''
			),
			'width' => array(
				'type' => 'number'
			),
			'isDescendentOfSingleProductBlock' => array(
				'type' => 'boolean',
				'default' => false
			),
			'isDescendentOfQueryLoop' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'supports' => array(
			'align' => array(
				'wide',
				'full'
			),
			'color' => array(
				'text' => true,
				'background' => true,
				'link' => false,
				'__experimentalSkipSerialization' => true
			),
			'interactivity' => true,
			'html' => false,
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'__experimentalBorder' => array(
				'radius' => true,
				'__experimentalSkipSerialization' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true,
				'__experimentalSkipSerialization' => true
			),
			'email' => true,
			'__experimentalSelector' => '.wp-block-button.wc-block-components-product-button .wc-block-components-product-button__button'
		),
		'ancestor' => array(
			'woocommerce/all-products',
			'woocommerce/single-product',
			'core/post-template',
			'woocommerce/product-template'
		),
		'styles' => array(
			array(
				'name' => 'fill',
				'label' => 'Fill',
				'isDefault' => true
			),
			array(
				'name' => 'outline',
				'label' => 'Outline'
			)
		),
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'viewScriptModule' => 'woocommerce/product-button',
		'style' => 'file:../woocommerce/product-button-style.css'
	),
	'product-categories' => array(
		'name' => 'woocommerce/product-categories',
		'title' => 'Product Categories List',
		'category' => 'woocommerce',
		'description' => 'Show all product categories as a list or dropdown.',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'color' => array(
				'background' => false,
				'link' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string'
			),
			'hasCount' => array(
				'type' => 'boolean',
				'default' => true
			),
			'hasImage' => array(
				'type' => 'boolean',
				'default' => false
			),
			'hasEmpty' => array(
				'type' => 'boolean',
				'default' => false
			),
			'isDropdown' => array(
				'type' => 'boolean',
				'default' => false
			),
			'isHierarchical' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showChildrenOnly' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'example' => array(
			'attributes' => array(
				'hasCount' => true,
				'hasImage' => false
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'product-category' => array(
		'name' => 'woocommerce/product-category',
		'title' => 'Products by Category',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'description' => 'Display a grid of products from your selected categories.',
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => false
			),
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'inserter' => false
		),
		'attributes' => array(
			'columns' => array(
				'type' => 'number',
				'default' => 3
			),
			'rows' => array(
				'type' => 'number',
				'default' => 3
			),
			'alignButtons' => array(
				'type' => 'boolean',
				'default' => false
			),
			'contentVisibility' => array(
				'type' => 'object',
				'default' => array(
					'image' => true,
					'title' => true,
					'price' => true,
					'rating' => true,
					'button' => true
				),
				'properties' => array(
					'image' => array(
						'type' => 'boolean',
						'default' => true
					),
					'title' => array(
						'type' => 'boolean',
						'default' => true
					),
					'price' => array(
						'type' => 'boolean',
						'default' => true
					),
					'rating' => array(
						'type' => 'boolean',
						'default' => true
					),
					'button' => array(
						'type' => 'boolean',
						'default' => true
					)
				)
			),
			'categories' => array(
				'type' => 'array',
				'default' => array(
					
				)
			),
			'catOperator' => array(
				'type' => 'string',
				'default' => 'any'
			),
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			),
			'stockStatus' => array(
				'type' => 'array'
			),
			'editMode' => array(
				'type' => 'boolean',
				'default' => true
			),
			'orderby' => array(
				'type' => 'string',
				'enum' => array(
					'date',
					'popularity',
					'price_asc',
					'price_desc',
					'rating',
					'title',
					'menu_order'
				),
				'default' => 'date'
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'product-collection' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-collection',
		'title' => 'Product Collection',
		'description' => 'Display a collection of products from your store.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce',
			'Products (Beta)',
			'all products',
			'by attribute',
			'by category',
			'by tag'
		),
		'textdomain' => 'woocommerce',
		'attributes' => array(
			'queryId' => array(
				'type' => 'number'
			),
			'query' => array(
				'type' => 'object'
			),
			'tagName' => array(
				'type' => 'string'
			),
			'displayLayout' => array(
				'type' => 'object',
				'properties' => array(
					'type' => array(
						'type' => 'string',
						'enum' => array(
							'flex',
							'list',
							'carousel'
						)
					),
					'columns' => array(
						'type' => 'number'
					),
					'shrinkColumns' => array(
						'type' => 'boolean'
					)
				)
			),
			'dimensions' => array(
				'type' => 'object'
			),
			'convertedFromProducts' => array(
				'type' => 'boolean',
				'default' => false
			),
			'collection' => array(
				'type' => 'string'
			),
			'hideControls' => array(
				'default' => array(
					
				),
				'type' => 'array'
			),
			'queryContextIncludes' => array(
				'type' => 'array'
			),
			'forcePageReload' => array(
				'type' => 'boolean',
				'default' => false
			),
			'__privatePreviewState' => array(
				'type' => 'object'
			)
		),
		'providesContext' => array(
			'queryId' => 'queryId',
			'query' => 'query',
			'displayLayout' => 'displayLayout',
			'dimensions' => 'dimensions',
			'queryContextIncludes' => 'queryContextIncludes',
			'collection' => 'collection',
			'__privateProductCollectionPreviewState' => '__privatePreviewState'
		),
		'usesContext' => array(
			'templateSlug',
			'postId'
		),
		'supports' => array(
			'align' => array(
				'wide',
				'full'
			),
			'anchor' => true,
			'html' => false,
			'__experimentalLayout' => true,
			'interactivity' => true,
			'email' => true
		),
		'editorStyle' => 'file:../woocommerce/product-collection-editor.css',
		'style' => 'file:../woocommerce/product-collection-style.css'
	),
	'product-collection-no-results' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-collection-no-results',
		'title' => 'No results',
		'category' => 'woocommerce',
		'description' => 'The contents of this block will display when there are no products found.',
		'textdomain' => 'woocommerce',
		'keywords' => array(
			'Product Collection'
		),
		'usesContext' => array(
			'queryId',
			'query'
		),
		'ancestor' => array(
			'woocommerce/product-collection'
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'align' => true,
			'reusable' => false,
			'html' => false,
			'color' => array(
				'gradients' => true,
				'link' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'email' => true
		)
	),
	'product-description' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-description',
		'title' => 'Product Description',
		'description' => 'Displays the description of the product.',
		'category' => 'woocommerce',
		'textdomain' => 'woocommerce',
		'usesContext' => array(
			'postId',
			'postType',
			'queryId'
		),
		'ancestor' => array(
			'woocommerce/single-product',
			'woocommerce/product-template',
			'core/post-template'
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'layout' => true,
			'background' => array(
				'backgroundImage' => true,
				'backgroundSize' => true,
				'__experimentalDefaultControls' => array(
					'backgroundImage' => true
				)
			),
			'dimensions' => array(
				'minHeight' => true
			),
			'spacing' => array(
				'blockGap' => true,
				'padding' => true,
				'margin' => true,
				'__experimentalDefaultControls' => array(
					'margin' => false,
					'padding' => false
				)
			),
			'color' => array(
				'gradients' => true,
				'heading' => true,
				'link' => true,
				'__experimentalDefaultControls' => array(
					'background' => false,
					'text' => false
				)
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'__experimentalBorder' => array(
				'radius' => true,
				'color' => true,
				'width' => true,
				'style' => true,
				'__experimentalDefaultControls' => array(
					'radius' => true,
					'color' => true,
					'width' => true,
					'style' => true
				)
			)
		)
	),
	'product-details' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-details',
		'title' => 'Product Details',
		'description' => 'Display a product\'s description, attributes, and reviews',
		'category' => 'woocommerce',
		'textdomain' => 'woocommerce',
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'align' => array(
				'wide',
				'full'
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'wide'
			),
			'hideTabTitle' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'usesContext' => array(
			'query',
			'queryId',
			'postId',
			'postType'
		)
	),
	'product-filter-active' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'name' => 'woocommerce/product-filter-active',
		'title' => 'Active Filters',
		'description' => 'Display the currently active filters.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'ancestor' => array(
			'woocommerce/product-filters'
		),
		'supports' => array(
			'interactivity' => true,
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'style' => true,
				'width' => true,
				'__experimentalDefaultControls' => array(
					'color' => false,
					'radius' => false,
					'style' => false,
					'width' => false
				)
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true,
				'blockGap' => false,
				'__experimentalDefaultControls' => array(
					'margin' => false,
					'padding' => false,
					'blockGap' => false
				)
			)
		),
		'usesContext' => array(
			'activeFilters'
		),
		'viewScriptModule' => 'woocommerce/product-filter-active'
	),
	'product-filter-attribute' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'name' => 'woocommerce/product-filter-attribute',
		'title' => 'Attribute Filter',
		'description' => 'Enable customers to filter the product grid by selecting one or more attributes, such as color.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'ancestor' => array(
			'woocommerce/product-filters'
		),
		'supports' => array(
			'interactivity' => true,
			'color' => array(
				'text' => true,
				'background' => false,
				'__experimentalDefaultControls' => array(
					'text' => false
				)
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => false
				)
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true,
				'blockGap' => true,
				'__experimentalDefaultControls' => array(
					'margin' => false,
					'padding' => false,
					'blockGap' => false
				)
			),
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'style' => true,
				'width' => true,
				'__experimentalDefaultControls' => array(
					'color' => false,
					'radius' => false,
					'style' => false,
					'width' => false
				)
			)
		),
		'usesContext' => array(
			'query',
			'filterParams'
		),
		'attributes' => array(
			'attributeId' => array(
				'type' => 'number',
				'default' => 0
			),
			'showCounts' => array(
				'type' => 'boolean',
				'default' => false
			),
			'queryType' => array(
				'type' => 'string',
				'default' => 'or'
			),
			'displayStyle' => array(
				'type' => 'string',
				'default' => 'woocommerce/product-filter-checkbox-list'
			),
			'selectType' => array(
				'type' => 'string',
				'default' => 'multiple'
			),
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			),
			'sortOrder' => array(
				'type' => 'string',
				'default' => 'count-desc'
			),
			'hideEmpty' => array(
				'type' => 'boolean',
				'default' => true
			)
		),
		'example' => array(
			'attributes' => array(
				'isPreview' => true
			)
		),
		'style' => 'woocommerce/product-filter-attribute-view-style'
	),
	'product-filter-checkbox-list' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'name' => 'woocommerce/product-filter-checkbox-list',
		'title' => 'List',
		'description' => 'Display a list of filter options.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'ancestor' => array(
			'woocommerce/product-filter-attribute',
			'woocommerce/product-filter-status',
			'woocommerce/product-filter-taxonomy',
			'woocommerce/product-filter-rating'
		),
		'supports' => array(
			'interactivity' => true
		),
		'usesContext' => array(
			'filterData'
		),
		'attributes' => array(
			'optionElementBorder' => array(
				'type' => 'string',
				'default' => ''
			),
			'customOptionElementBorder' => array(
				'type' => 'string',
				'default' => ''
			),
			'optionElementSelected' => array(
				'type' => 'string',
				'default' => ''
			),
			'customOptionElementSelected' => array(
				'type' => 'string',
				'default' => ''
			),
			'optionElement' => array(
				'type' => 'string',
				'default' => ''
			),
			'customOptionElement' => array(
				'type' => 'string',
				'default' => ''
			),
			'labelElement' => array(
				'type' => 'string',
				'default' => ''
			),
			'customLabelElement' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'viewScriptModule' => 'woocommerce/product-filter-checkbox-list',
		'style' => 'file:../woocommerce/product-filter-checkbox-list-style.css',
		'editorStyle' => 'file:../woocommerce/product-filter-checkbox-list-editor.css'
	),
	'product-filter-chips' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'name' => 'woocommerce/product-filter-chips',
		'title' => 'Chips',
		'description' => 'Display filter options as chips.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'ancestor' => array(
			'woocommerce/product-filter-attribute',
			'woocommerce/product-filter-taxonomy',
			'woocommerce/product-filter-status'
		),
		'supports' => array(
			'interactivity' => true
		),
		'usesContext' => array(
			'filterData'
		),
		'attributes' => array(
			'chipText' => array(
				'type' => 'string'
			),
			'customChipText' => array(
				'type' => 'string'
			),
			'chipBackground' => array(
				'type' => 'string'
			),
			'customChipBackground' => array(
				'type' => 'string'
			),
			'chipBorder' => array(
				'type' => 'string'
			),
			'customChipBorder' => array(
				'type' => 'string'
			),
			'selectedChipText' => array(
				'type' => 'string'
			),
			'customSelectedChipText' => array(
				'type' => 'string'
			),
			'selectedChipBackground' => array(
				'type' => 'string'
			),
			'customSelectedChipBackground' => array(
				'type' => 'string'
			),
			'selectedChipBorder' => array(
				'type' => 'string'
			),
			'customSelectedChipBorder' => array(
				'type' => 'string'
			)
		),
		'viewScriptModule' => 'woocommerce/product-filter-chips',
		'style' => 'file:../woocommerce/product-filter-chips-style.css',
		'editorStyle' => 'file:../woocommerce/product-filter-chips-editor.css'
	),
	'product-filter-clear-button' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'name' => 'woocommerce/product-filter-clear-button',
		'title' => 'Clear filters',
		'description' => 'Allows shoppers to clear active filters.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce',
			'clear filters'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'ancestor' => array(
			'woocommerce/product-filter-active'
		),
		'usesContext' => array(
			'filterData'
		),
		'supports' => array(
			'interactivity' => true,
			'inserter' => true
		)
	),
	'product-filter-price' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'name' => 'woocommerce/product-filter-price',
		'title' => 'Price Filter',
		'description' => 'Let shoppers filter products by choosing a price range.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'ancestor' => array(
			'woocommerce/product-filters'
		),
		'supports' => array(
			'interactivity' => true,
			'html' => false
		),
		'usesContext' => array(
			'query',
			'filterParams'
		),
		'viewScriptModule' => 'woocommerce/product-filter-price'
	),
	'product-filter-price-slider' => array(
		'name' => 'woocommerce/product-filter-price-slider',
		'title' => 'Price Slider',
		'description' => 'A slider helps shopper choose a price range.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'html' => false,
			'color' => array(
				'enableContrastChecker' => false,
				'background' => false,
				'text' => false
			),
			'interactivity' => true
		),
		'attributes' => array(
			'showInputFields' => array(
				'type' => 'boolean',
				'default' => true
			),
			'inlineInput' => array(
				'type' => 'boolean',
				'default' => false
			),
			'sliderHandle' => array(
				'type' => 'string',
				'default' => ''
			),
			'customSliderHandle' => array(
				'type' => 'string',
				'default' => ''
			),
			'sliderHandleBorder' => array(
				'type' => 'string',
				'default' => ''
			),
			'customSliderHandleBorder' => array(
				'type' => 'string',
				'default' => ''
			),
			'slider' => array(
				'type' => 'string',
				'default' => ''
			),
			'customSlider' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'ancestor' => array(
			'woocommerce/product-filter-price'
		),
		'usesContext' => array(
			'filterData'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'viewScriptModule' => 'woocommerce/product-filter-price-slider',
		'style' => 'file:../woocommerce/product-filter-price-slider-style.css'
	),
	'product-filter-rating' => array(
		'name' => 'woocommerce/product-filter-rating',
		'title' => 'Rating Filter',
		'description' => 'Enable customers to filter the product collection by rating.',
		'category' => 'woocommerce',
		'keywords' => array(
			
		),
		'supports' => array(
			'interactivity' => true,
			'color' => array(
				'background' => false,
				'text' => true
			)
		),
		'ancestor' => array(
			'woocommerce/product-filters'
		),
		'usesContext' => array(
			'query',
			'filterParams'
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'showCounts' => array(
				'type' => 'boolean',
				'default' => false
			),
			'minRating' => array(
				'type' => 'string',
				'default' => '0'
			),
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'product-filter-removable-chips' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'name' => 'woocommerce/product-filter-removable-chips',
		'title' => 'Chips',
		'description' => 'Display removable active filters as chips.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'ancestor' => array(
			'woocommerce/product-filter-active'
		),
		'supports' => array(
			'layout' => array(
				'allowSwitching' => false,
				'allowInheriting' => false,
				'allowVerticalAlignment' => false,
				'default' => array(
					'type' => 'flex'
				)
			),
			'interactivity' => true
		),
		'usesContext' => array(
			'queryId',
			'filterData'
		),
		'attributes' => array(
			'chipText' => array(
				'type' => 'string'
			),
			'customChipText' => array(
				'type' => 'string'
			),
			'chipBackground' => array(
				'type' => 'string'
			),
			'customChipBackground' => array(
				'type' => 'string'
			),
			'chipBorder' => array(
				'type' => 'string'
			),
			'customChipBorder' => array(
				'type' => 'string'
			)
		),
		'style' => 'file:../woocommerce/product-filter-removable-chips-style.css'
	),
	'product-filter-status' => array(
		'name' => 'woocommerce/product-filter-status',
		'title' => 'Status Filter',
		'description' => 'Let shoppers filter products by choosing stock status.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'ancestor' => array(
			'woocommerce/product-filters'
		),
		'supports' => array(
			'interactivity' => true,
			'html' => false,
			'color' => array(
				'text' => true,
				'background' => false,
				'__experimentalDefaultControls' => array(
					'text' => false
				)
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => false
				)
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true,
				'blockGap' => true,
				'__experimentalDefaultControls' => array(
					'margin' => false,
					'padding' => false,
					'blockGap' => false
				)
			),
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'style' => true,
				'width' => true,
				'__experimentalDefaultControls' => array(
					'color' => false,
					'radius' => false,
					'style' => false,
					'width' => false
				)
			)
		),
		'attributes' => array(
			'showCounts' => array(
				'type' => 'boolean',
				'default' => false
			),
			'displayStyle' => array(
				'type' => 'string',
				'default' => 'woocommerce/product-filter-checkbox-list'
			),
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			),
			'hideEmpty' => array(
				'type' => 'boolean',
				'default' => true
			)
		),
		'usesContext' => array(
			'query',
			'filterParams'
		),
		'example' => array(
			'attributes' => array(
				'isPreview' => true
			)
		)
	),
	'product-filter-taxonomy' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'name' => 'woocommerce/product-filter-taxonomy',
		'title' => 'Taxonomy Filter',
		'description' => 'Enable customers to filter the product collection by selecting one or more taxonomy terms, such as categories, brands, or tags.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'ancestor' => array(
			'woocommerce/product-filters'
		),
		'supports' => array(
			'interactivity' => true,
			'color' => array(
				'text' => true,
				'background' => false,
				'__experimentalDefaultControls' => array(
					'text' => false
				)
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => false
				)
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true,
				'blockGap' => true,
				'__experimentalDefaultControls' => array(
					'margin' => false,
					'padding' => false,
					'blockGap' => false
				)
			),
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'style' => true,
				'width' => true,
				'__experimentalDefaultControls' => array(
					'color' => false,
					'radius' => false,
					'style' => false,
					'width' => false
				)
			)
		),
		'usesContext' => array(
			'query',
			'filterParams'
		),
		'attributes' => array(
			'taxonomy' => array(
				'type' => 'string',
				'default' => 'product_cat'
			),
			'showCounts' => array(
				'type' => 'boolean',
				'default' => false
			),
			'displayStyle' => array(
				'type' => 'string',
				'default' => 'woocommerce/product-filter-checkbox-list'
			),
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			),
			'sortOrder' => array(
				'type' => 'string',
				'default' => 'count-desc'
			),
			'hideEmpty' => array(
				'type' => 'boolean',
				'default' => true
			)
		),
		'example' => array(
			'attributes' => array(
				'isPreview' => true
			)
		)
	),
	'product-filters' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-filters',
		'title' => 'Product Filters',
		'description' => 'Let shoppers filter products displayed on the page.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'align' => true,
			'color' => array(
				'background' => true,
				'text' => true,
				'heading' => true,
				'enableContrastChecker' => false,
				'button' => true
			),
			'multiple' => true,
			'inserter' => true,
			'interactivity' => true,
			'typography' => array(
				'fontSize' => true
			),
			'layout' => array(
				'default' => array(
					'type' => 'flex',
					'orientation' => 'vertical',
					'flexWrap' => 'nowrap',
					'justifyContent' => 'stretch'
				),
				'allowEditing' => false
			),
			'spacing' => array(
				'blockGap' => true
			)
		),
		'textdomain' => 'woocommerce',
		'usesContext' => array(
			'postId',
			'query',
			'queryId'
		),
		'attributes' => array(
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'example' => array(
			'attributes' => array(
				'isPreview' => true
			)
		),
		'viewScriptModule' => 'woocommerce/product-filters',
		'style' => 'file:../woocommerce/product-filters-style.css',
		'editorStyle' => 'file:../woocommerce/product-filters-editor.css'
	),
	'product-gallery' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-gallery',
		'title' => 'Product Gallery',
		'description' => 'Showcase your products relevant images and media.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'align' => true,
			'interactivity' => true,
			'layout' => array(
				'default' => array(
					'type' => 'flex',
					'flexWrap' => 'nowrap',
					'orientation' => 'horizontal'
				),
				'allowOrientation' => true,
				'allowEditing' => true,
				'allowJustification' => false
			),
			'email' => true
		),
		'textdomain' => 'woocommerce',
		'usesContext' => array(
			'postId'
		),
		'providesContext' => array(
			'hoverZoom' => 'hoverZoom',
			'fullScreenOnClick' => 'fullScreenOnClick'
		),
		'ancestor' => array(
			'woocommerce/single-product'
		),
		'attributes' => array(
			'hoverZoom' => array(
				'type' => 'boolean',
				'default' => true
			),
			'fullScreenOnClick' => array(
				'type' => 'boolean',
				'default' => true
			)
		),
		'viewScript' => 'wc-product-gallery-frontend',
		'example' => array(
			
		),
		'viewScriptModule' => 'woocommerce/product-gallery',
		'style' => 'file:../woocommerce/product-gallery-style.css'
	),
	'product-gallery-large-image' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-gallery-large-image',
		'title' => 'Viewer',
		'description' => 'Container for the current gallery image, navigation buttons, zoom functionality and more.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'usesContext' => array(
			'postId',
			'hoverZoom',
			'fullScreenOnClick'
		),
		'supports' => array(
			'interactivity' => true
		),
		'textdomain' => 'woocommerce',
		'ancestor' => array(
			'woocommerce/product-gallery'
		),
		'viewScriptModule' => 'woocommerce/product-gallery-large-image',
		'editorStyle' => 'file:../woocommerce/product-gallery-large-image-editor.css'
	),
	'product-gallery-large-image-next-previous' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-gallery-large-image-next-previous',
		'title' => 'Next/Previous Buttons',
		'description' => 'Display next and previous buttons.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'usesContext' => array(
			'iapi/provider'
		),
		'textdomain' => 'woocommerce',
		'supports' => array(
			'interactivity' => true,
			'color' => array(
				'background' => true,
				'text' => true,
				'__experimentalSkipSerialization' => true
			),
			'align' => true,
			'layout' => array(
				'default' => array(
					'type' => 'flex',
					'flexWrap' => 'nowrap',
					'verticalAlignment' => 'center'
				),
				'allowVerticalAlignment' => true,
				'allowOrientation' => false,
				'allowJustification' => false
			),
			'shadow' => true,
			'spacing' => array(
				'margin' => true,
				'__experimentalSkipSerialization' => true
			),
			'__experimentalBorder' => array(
				'radius' => true,
				'__experimentalSkipSerialization' => true
			),
			'__experimentalSelector' => '.wc-block-next-previous-buttons__button'
		),
		'ancestor' => array(
			'woocommerce/product-gallery-large-image',
			'woocommerce/product-collection'
		),
		'style' => 'file:../woocommerce/product-gallery-large-image-next-previous-style.css',
		'editorStyle' => 'file:../woocommerce/product-gallery-large-image-next-previous-editor.css'
	),
	'product-gallery-thumbnails' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-gallery-thumbnails',
		'title' => 'Thumbnails',
		'description' => 'Display the Thumbnails of a product.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'usesContext' => array(
			'postId'
		),
		'textdomain' => 'woocommerce',
		'ancestor' => array(
			'woocommerce/product-gallery'
		),
		'attributes' => array(
			'thumbnailSize' => array(
				'type' => 'string',
				'default' => '25%'
			),
			'aspectRatio' => array(
				'type' => 'string',
				'default' => '1'
			),
			'activeThumbnailStyle' => array(
				'type' => 'string',
				'default' => 'overlay'
			)
		),
		'supports' => array(
			'spacing' => array(
				'margin' => true
			),
			'interactivity' => true
		),
		'editorStyle' => 'file:../woocommerce/product-gallery-thumbnails-editor.css'
	),
	'product-image' => array(
		'name' => 'woocommerce/product-image',
		'title' => 'Product Image',
		'description' => 'Display the main product image.',
		'category' => 'woocommerce-product-elements',
		'attributes' => array(
			'showProductLink' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showSaleBadge' => array(
				'type' => 'boolean',
				'default' => true
			),
			'saleBadgeAlign' => array(
				'type' => 'string',
				'default' => 'right'
			),
			'imageSizing' => array(
				'type' => 'string',
				'default' => 'single'
			),
			'productId' => array(
				'type' => 'number',
				'default' => 0
			),
			'isDescendentOfQueryLoop' => array(
				'type' => 'boolean',
				'default' => false
			),
			'isDescendentOfSingleProductBlock' => array(
				'type' => 'boolean',
				'default' => false
			),
			'width' => array(
				'type' => 'string'
			),
			'height' => array(
				'type' => 'string'
			),
			'scale' => array(
				'type' => 'string',
				'default' => 'cover'
			),
			'aspectRatio' => array(
				'type' => 'string'
			)
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'html' => false,
			'__experimentalBorder' => array(
				'radius' => true,
				'__experimentalSkipSerialization' => true
			),
			'typography' => array(
				'fontSize' => true,
				'__experimentalSkipSerialization' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'dimensions' => array(
				'aspectRatio' => true,
				'__experimentalSkipSerialization' => true
			),
			'email' => true,
			'__experimentalSelector' => '.wc-block-components-product-image'
		),
		'ancestor' => array(
			'woocommerce/all-products',
			'woocommerce/single-product',
			'woocommerce/product-template',
			'core/post-template'
		),
		'usesContext' => array(
			'imageId',
			'postId',
			'query',
			'queryId'
		),
		'keywords' => array(
			'WooCommerce'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'product-image-gallery' => array(
		'name' => 'woocommerce/product-image-gallery',
		'title' => 'Product Image Gallery',
		'icon' => 'gallery',
		'description' => 'Display a product\'s images.',
		'category' => 'woocommerce-product-elements',
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'align' => true,
			'multiple' => false
		),
		'keywords' => array(
			'WooCommerce'
		),
		'usesContext' => array(
			'postId',
			'postType',
			'queryId'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'product-meta' => array(
		'name' => 'woocommerce/product-meta',
		'title' => 'Product Meta',
		'icon' => 'product',
		'description' => 'Display a product’s SKU, categories, tags, and more.',
		'category' => 'woocommerce-product-elements',
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'align' => true,
			'reusable' => false
		),
		'keywords' => array(
			'WooCommerce'
		),
		'usesContext' => array(
			'postId',
			'postType',
			'queryId'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'product-new' => array(
		'name' => 'woocommerce/product-new',
		'title' => 'Newest Products',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'description' => 'Display a grid of your newest products.',
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => false
			),
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'inserter' => false
		),
		'attributes' => array(
			'columns' => array(
				'type' => 'number',
				'default' => 3
			),
			'rows' => array(
				'type' => 'number',
				'default' => 3
			),
			'alignButtons' => array(
				'type' => 'boolean',
				'default' => false
			),
			'contentVisibility' => array(
				'type' => 'object',
				'default' => array(
					'image' => true,
					'title' => true,
					'price' => true,
					'rating' => true,
					'button' => true
				),
				'properties' => array(
					'image' => array(
						'type' => 'boolean',
						'default' => true
					),
					'title' => array(
						'type' => 'boolean',
						'default' => true
					),
					'price' => array(
						'type' => 'boolean',
						'default' => true
					),
					'rating' => array(
						'type' => 'boolean',
						'default' => true
					),
					'button' => array(
						'type' => 'boolean',
						'default' => true
					)
				)
			),
			'categories' => array(
				'type' => 'array',
				'default' => array(
					
				)
			),
			'catOperator' => array(
				'type' => 'string',
				'default' => 'any'
			),
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			),
			'stockStatus' => array(
				'type' => 'array'
			),
			'editMode' => array(
				'type' => 'boolean',
				'default' => true
			),
			'orderby' => array(
				'type' => 'string',
				'enum' => array(
					'date',
					'popularity',
					'price_asc',
					'price_desc',
					'rating',
					'title',
					'menu_order'
				),
				'default' => 'date'
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'product-on-sale' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-on-sale',
		'title' => 'On Sale Products',
		'category' => 'woocommerce',
		'description' => 'Display a grid of products currently on sale.',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => false
			),
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'inserter' => false
		),
		'textdomain' => 'woocommerce',
		'attributes' => array(
			'columns' => array(
				'type' => 'number',
				'default' => 3
			),
			'rows' => array(
				'type' => 'number',
				'default' => 3
			),
			'alignButtons' => array(
				'type' => 'boolean',
				'default' => false
			),
			'categories' => array(
				'type' => 'array',
				'default' => array(
					
				)
			),
			'catOperator' => array(
				'type' => 'string',
				'default' => 'any'
			),
			'contentVisibility' => array(
				'type' => 'object',
				'default' => array(
					'image' => true,
					'title' => true,
					'price' => true,
					'rating' => true,
					'button' => true
				),
				'properties' => array(
					'image' => array(
						'type' => 'boolean',
						'default' => true
					),
					'title' => array(
						'type' => 'boolean',
						'default' => true
					),
					'price' => array(
						'type' => 'boolean',
						'default' => true
					),
					'rating' => array(
						'type' => 'boolean',
						'default' => true
					),
					'button' => array(
						'type' => 'boolean',
						'default' => true
					)
				)
			),
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			),
			'stockStatus' => array(
				'type' => 'array'
			),
			'orderby' => array(
				'type' => 'string',
				'default' => 'date'
			)
		)
	),
	'product-price' => array(
		'name' => 'woocommerce/product-price',
		'title' => 'Product Price',
		'description' => 'Display the price of a product.',
		'category' => 'woocommerce-product-elements',
		'attributes' => array(
			'productId' => array(
				'type' => 'number',
				'default' => 0
			),
			'isDescendentOfQueryLoop' => array(
				'type' => 'boolean',
				'default' => false
			),
			'textAlign' => array(
				'type' => 'string',
				'default' => ''
			),
			'isDescendentOfSingleProductTemplate' => array(
				'type' => 'boolean',
				'default' => false
			),
			'isDescendentOfSingleProductBlock' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'usesContext' => array(
			'query',
			'queryId',
			'postId'
		),
		'keywords' => array(
			'WooCommerce'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'supports' => array(
			'html' => false,
			'interactivity' => true,
			'color' => array(
				'text' => true,
				'background' => true,
				'link' => false
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalLetterSpacing' => true
			),
			'__experimentalSelector' => '.wp-block-woocommerce-product-price .wc-block-components-product-price',
			'email' => true,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'ancestor' => array(
			'woocommerce/all-products',
			'woocommerce/featured-product',
			'woocommerce/single-product',
			'woocommerce/product-template',
			'core/post-template'
		),
		'viewScriptModule' => 'product-price',
		'style' => 'file:../product-price.css',
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'product-rating' => array(
		'name' => 'woocommerce/product-rating',
		'icon' => 'info',
		'title' => 'Product Rating',
		'description' => 'Display the average rating of a product.',
		'category' => 'woocommerce-product-elements',
		'attributes' => array(
			'productId' => array(
				'type' => 'number',
				'default' => 0
			),
			'isDescendentOfQueryLoop' => array(
				'type' => 'boolean',
				'default' => false
			),
			'textAlign' => array(
				'type' => 'string',
				'default' => ''
			),
			'isDescendentOfSingleProductBlock' => array(
				'type' => 'boolean',
				'default' => false
			),
			'isDescendentOfSingleProductTemplate' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'color' => array(
				'text' => true,
				'background' => false,
				'link' => false,
				'__experimentalSkipSerialization' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true,
				'__experimentalSkipSerialization' => true
			),
			'__experimentalSelector' => '.wc-block-components-product-rating'
		),
		'ancestor' => array(
			'woocommerce/all-products',
			'woocommerce/single-product',
			'woocommerce/product-template',
			'core/post-template'
		),
		'usesContext' => array(
			'query',
			'queryId',
			'postId'
		),
		'keywords' => array(
			'WooCommerce'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'product-rating-counter' => array(
		'name' => 'woocommerce/product-rating-counter',
		'title' => 'Product Rating Counter',
		'description' => 'Display the review count of a product',
		'category' => 'woocommerce-product-elements',
		'attributes' => array(
			'productId' => array(
				'type' => 'number',
				'default' => 0
			),
			'isDescendentOfQueryLoop' => array(
				'type' => 'boolean',
				'default' => false
			),
			'textAlign' => array(
				'type' => 'string',
				'default' => ''
			),
			'isDescendentOfSingleProductBlock' => array(
				'type' => 'boolean',
				'default' => false
			),
			'isDescendentOfSingleProductTemplate' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'inserter' => false,
			'color' => array(
				'text' => false,
				'background' => false,
				'link' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true,
				'__experimentalSkipSerialization' => true
			),
			'__experimentalSelector' => '.wc-block-components-product-rating-counter'
		),
		'usesContext' => array(
			'query',
			'queryId',
			'postId'
		),
		'keywords' => array(
			'WooCommerce'
		),
		'ancestor' => array(
			'woocommerce/single-product'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'product-rating-stars' => array(
		'name' => 'woocommerce/product-rating-stars',
		'title' => 'Product Rating Stars',
		'description' => 'Display the average rating of a product with stars',
		'category' => 'woocommerce-product-elements',
		'attributes' => array(
			'productId' => array(
				'type' => 'number',
				'default' => 0
			),
			'isDescendentOfQueryLoop' => array(
				'type' => 'boolean',
				'default' => false
			),
			'textAlign' => array(
				'type' => 'string',
				'default' => ''
			),
			'isDescendentOfSingleProductBlock' => array(
				'type' => 'boolean',
				'default' => false
			),
			'isDescendentOfSingleProductTemplate' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'usesContext' => array(
			'query',
			'queryId',
			'postId'
		),
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'inserter' => false,
			'color' => array(
				'text' => true,
				'background' => false,
				'link' => false,
				'__experimentalSkipSerialization' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true,
				'__experimentalSkipSerialization' => true
			),
			'__experimentalSelector' => '.wc-block-components-product-rating'
		),
		'ancestor' => array(
			'woocommerce/single-product'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'product-results-count' => array(
		'name' => 'woocommerce/product-results-count',
		'title' => 'Product Results Count',
		'description' => 'Display the number of products on the archive page or search result page.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'color' => array(
				'text' => true,
				'background' => false
			),
			'typography' => array(
				'fontSize' => true
			)
		),
		'usesContext' => array(
			'queryId'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'product-review-author-name' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-review-author-name',
		'title' => 'Review Author Name',
		'category' => 'woocommerce',
		'ancestor' => array(
			'woocommerce/product-reviews'
		),
		'description' => 'Displays the name of the author of the review.',
		'textdomain' => 'woocommerce',
		'attributes' => array(
			'isLink' => array(
				'type' => 'boolean',
				'default' => true
			),
			'linkTarget' => array(
				'type' => 'string',
				'default' => '_self'
			),
			'textAlign' => array(
				'type' => 'string'
			)
		),
		'usesContext' => array(
			'commentId'
		),
		'supports' => array(
			'html' => false,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'color' => array(
				'gradients' => true,
				'link' => true,
				'__experimentalDefaultControls' => array(
					'background' => true,
					'text' => true,
					'link' => true
				)
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'interactivity' => array(
				'clientNavigation' => true
			),
			'__experimentalBorder' => array(
				'radius' => true,
				'color' => true,
				'width' => true,
				'style' => true,
				'__experimentalDefaultControls' => array(
					'radius' => true,
					'color' => true,
					'width' => true,
					'style' => true
				)
			)
		)
	),
	'product-review-content' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-review-content',
		'title' => 'Review Content',
		'category' => 'woocommerce',
		'ancestor' => array(
			'woocommerce/product-reviews'
		),
		'description' => 'Displays the contents of a product review.',
		'textdomain' => 'woocommerce',
		'usesContext' => array(
			'commentId'
		),
		'attributes' => array(
			'textAlign' => array(
				'type' => 'string'
			)
		),
		'supports' => array(
			'color' => array(
				'gradients' => true,
				'link' => true,
				'__experimentalDefaultControls' => array(
					'background' => true,
					'text' => true
				)
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'__experimentalBorder' => array(
				'radius' => true,
				'color' => true,
				'width' => true,
				'style' => true,
				'__experimentalDefaultControls' => array(
					'radius' => true,
					'color' => true,
					'width' => true,
					'style' => true
				)
			),
			'spacing' => array(
				'padding' => array(
					'horizontal',
					'vertical'
				),
				'__experimentalDefaultControls' => array(
					'padding' => true
				)
			),
			'html' => false
		)
	),
	'product-review-date' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-review-date',
		'title' => 'Review Date',
		'category' => 'woocommerce',
		'ancestor' => array(
			'woocommerce/product-reviews'
		),
		'description' => 'Displays the date on which the review was posted.',
		'textdomain' => 'woocommerce',
		'attributes' => array(
			'format' => array(
				'type' => 'string'
			),
			'isLink' => array(
				'type' => 'boolean',
				'default' => true
			)
		),
		'usesContext' => array(
			'commentId'
		),
		'supports' => array(
			'html' => false,
			'color' => array(
				'gradients' => true,
				'link' => true,
				'__experimentalDefaultControls' => array(
					'background' => true,
					'text' => true,
					'link' => true
				)
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'interactivity' => array(
				'clientNavigation' => true
			),
			'__experimentalBorder' => array(
				'radius' => true,
				'color' => true,
				'width' => true,
				'style' => true,
				'__experimentalDefaultControls' => array(
					'radius' => true,
					'color' => true,
					'width' => true,
					'style' => true
				)
			)
		)
	),
	'product-review-form' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-review-form',
		'title' => 'Reviews Form',
		'category' => 'woocommerce',
		'description' => 'Display a product\'s reviews form.',
		'textdomain' => 'woocommerce',
		'attributes' => array(
			'textAlign' => array(
				'type' => 'string'
			)
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'supports' => array(
			'interactivity' => true,
			'html' => false,
			'color' => array(
				'gradients' => true,
				'heading' => true,
				'link' => true,
				'__experimentalDefaultControls' => array(
					'background' => true,
					'text' => true
				)
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalFontWeight' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalTextTransform' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'__experimentalBorder' => array(
				'radius' => true,
				'color' => true,
				'width' => true,
				'style' => true,
				'__experimentalDefaultControls' => array(
					'radius' => true,
					'color' => true,
					'width' => true,
					'style' => true
				)
			)
		),
		'example' => array(
			'attributes' => array(
				'textAlign' => 'center'
			)
		),
		'viewScriptModule' => 'woocommerce/product-review-form',
		'style' => 'file:../woocommerce/product-review-form-style.css',
		'editorStyle' => 'file:../woocommerce/product-review-form-editor.css'
	),
	'product-review-rating' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-review-rating',
		'title' => 'Review Rating',
		'category' => 'woocommerce',
		'ancestor' => array(
			'woocommerce/product-reviews'
		),
		'description' => 'Displays the rating of a product review.',
		'textdomain' => 'woocommerce',
		'usesContext' => array(
			'commentId'
		),
		'attributes' => array(
			'textAlign' => array(
				'type' => 'string'
			)
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'color' => array(
				'gradients' => true,
				'__experimentalDefaultControls' => array(
					'background' => true,
					'text' => true
				)
			)
		)
	),
	'product-review-template' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-review-template',
		'title' => 'Reviews Template',
		'category' => 'woocommerce',
		'ancestor' => array(
			'woocommerce/product-reviews'
		),
		'description' => 'Contains the block elements used to display product reviews, like the title, author, date, rating and more.',
		'textdomain' => 'woocommerce',
		'usesContext' => array(
			'postId',
			'postType'
		),
		'supports' => array(
			'align' => true,
			'html' => false,
			'reusable' => false,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'interactivity' => array(
				'clientNavigation' => true
			),
			'__experimentalBorder' => array(
				'radius' => true,
				'color' => true,
				'width' => true,
				'style' => true,
				'__experimentalDefaultControls' => array(
					'radius' => true,
					'color' => true,
					'width' => true,
					'style' => true
				)
			)
		)
	),
	'product-reviews' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-reviews',
		'icon' => 'admin-comments',
		'title' => 'Product Reviews',
		'description' => 'Display a product\'s reviews',
		'category' => 'woocommerce',
		'textdomain' => 'woocommerce',
		'attributes' => array(
			'tagName' => array(
				'type' => 'string',
				'default' => 'div'
			)
		),
		'supports' => array(
			'interactivity' => true,
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'color' => array(
				'gradients' => true,
				'heading' => true,
				'link' => true,
				'__experimentalDefaultControls' => array(
					'background' => true,
					'text' => true,
					'link' => true
				)
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'__experimentalBorder' => array(
				'radius' => true,
				'color' => true,
				'width' => true,
				'style' => true,
				'__experimentalDefaultControls' => array(
					'radius' => true,
					'color' => true,
					'width' => true,
					'style' => true
				)
			)
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'viewScriptModule' => 'woocommerce/product-reviews'
	),
	'product-reviews-pagination' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-reviews-pagination',
		'title' => 'Reviews Pagination',
		'category' => 'woocommerce',
		'ancestor' => array(
			'woocommerce/product-reviews'
		),
		'allowedBlocks' => array(
			'woocommerce/product-reviews-pagination-previous',
			'woocommerce/product-reviews-pagination-numbers',
			'woocommerce/product-reviews-pagination-next'
		),
		'description' => 'Displays a paginated navigation to next/previous set of product reviews, when applicable.',
		'textdomain' => 'woocommerce',
		'attributes' => array(
			'paginationArrow' => array(
				'type' => 'string',
				'default' => 'none'
			)
		),
		'example' => array(
			'attributes' => array(
				'paginationArrow' => 'none'
			)
		),
		'providesContext' => array(
			'reviews/paginationArrow' => 'paginationArrow'
		),
		'supports' => array(
			'align' => true,
			'reusable' => false,
			'html' => false,
			'color' => array(
				'gradients' => true,
				'link' => true,
				'__experimentalDefaultControls' => array(
					'background' => true,
					'text' => true,
					'link' => true
				)
			),
			'layout' => array(
				'allowSwitching' => false,
				'allowInheriting' => false,
				'default' => array(
					'type' => 'flex'
				)
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'interactivity' => array(
				'clientNavigation' => true
			)
		)
	),
	'product-reviews-pagination-next' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-reviews-pagination-next',
		'title' => 'Reviews Next Page',
		'category' => 'woocommerce',
		'ancestor' => array(
			'woocommerce/product-reviews-pagination'
		),
		'description' => 'Displays the next product review\'s page link.',
		'textdomain' => 'woocommerce',
		'attributes' => array(
			'label' => array(
				'type' => 'string'
			)
		),
		'usesContext' => array(
			'postId',
			'reviews/paginationArrow'
		),
		'supports' => array(
			'reusable' => false,
			'html' => false,
			'color' => array(
				'gradients' => true,
				'text' => false,
				'__experimentalDefaultControls' => array(
					'background' => true
				)
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'interactivity' => array(
				'clientNavigation' => true
			)
		)
	),
	'product-reviews-pagination-numbers' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-reviews-pagination-numbers',
		'title' => 'Reviews Page Numbers',
		'category' => 'woocommerce',
		'ancestor' => array(
			'woocommerce/product-reviews-pagination'
		),
		'description' => 'Displays a list of page numbers for product reviews pagination.',
		'textdomain' => 'woocommerce',
		'usesContext' => array(
			'postId'
		),
		'supports' => array(
			'reusable' => false,
			'html' => false,
			'color' => array(
				'gradients' => true,
				'text' => false,
				'__experimentalDefaultControls' => array(
					'background' => true
				)
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'interactivity' => array(
				'clientNavigation' => true
			)
		)
	),
	'product-reviews-pagination-previous' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-reviews-pagination-previous',
		'title' => 'Reviews Previous Page',
		'category' => 'woocommerce',
		'ancestor' => array(
			'woocommerce/product-reviews-pagination'
		),
		'description' => 'Displays the previous product review\'s page link.',
		'textdomain' => 'woocommerce',
		'attributes' => array(
			'label' => array(
				'type' => 'string'
			)
		),
		'usesContext' => array(
			'postId',
			'reviews/paginationArrow'
		),
		'supports' => array(
			'reusable' => false,
			'html' => false,
			'color' => array(
				'gradients' => true,
				'text' => false,
				'__experimentalDefaultControls' => array(
					'background' => true
				)
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'interactivity' => array(
				'clientNavigation' => true
			)
		)
	),
	'product-reviews-title' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-reviews-title',
		'title' => 'Reviews Title',
		'category' => 'woocommerce',
		'ancestor' => array(
			'woocommerce/product-reviews'
		),
		'description' => 'Displays a title with the number of reviews.',
		'textdomain' => 'woocommerce',
		'usesContext' => array(
			'postId',
			'postType'
		),
		'attributes' => array(
			'textAlign' => array(
				'type' => 'string'
			),
			'showProductTitle' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showReviewsCount' => array(
				'type' => 'boolean',
				'default' => true
			),
			'level' => array(
				'type' => 'number',
				'default' => 2
			),
			'levelOptions' => array(
				'type' => 'array'
			)
		),
		'supports' => array(
			'anchor' => false,
			'align' => true,
			'html' => false,
			'__experimentalBorder' => array(
				'radius' => true,
				'color' => true,
				'width' => true,
				'style' => true
			),
			'color' => array(
				'gradients' => true,
				'__experimentalDefaultControls' => array(
					'background' => true,
					'text' => true
				)
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true,
					'__experimentalFontFamily' => true,
					'__experimentalFontStyle' => true,
					'__experimentalFontWeight' => true
				)
			),
			'interactivity' => array(
				'clientNavigation' => true
			)
		)
	),
	'product-sale-badge' => array(
		'name' => 'woocommerce/product-sale-badge',
		'title' => 'On-Sale Badge',
		'description' => 'Displays an on-sale badge if the product is on-sale.',
		'category' => 'woocommerce-product-elements',
		'attributes' => array(
			'productId' => array(
				'type' => 'number',
				'default' => 0
			),
			'isDescendentOfQueryLoop' => array(
				'type' => 'boolean',
				'default' => false
			),
			'isDescendentOfSingleProductBlock' => array(
				'type' => 'boolean',
				'default' => false
			),
			'isDescendentOfSingleProductTemplate' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'html' => false,
			'align' => true,
			'color' => array(
				'gradients' => true,
				'background' => true,
				'link' => false,
				'__experimentalSkipSerialization' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalSkipSerialization' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true
			),
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'width' => true,
				'__experimentalSkipSerialization' => true
			),
			'spacing' => array(
				'margin' => true
			),
			'email' => true,
			'__experimentalSelector' => '.wc-block-components-product-sale-badge'
		),
		'ancestor' => array(
			'woocommerce/single-product',
			'woocommerce/product-template',
			'core/post-template',
			'woocommerce/product-gallery'
		),
		'usesContext' => array(
			'query',
			'queryId',
			'postId'
		),
		'keywords' => array(
			'WooCommerce'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'example' => array(
			
		),
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'product-sku' => array(
		'name' => 'woocommerce/product-sku',
		'title' => 'Product SKU',
		'description' => 'Displays the SKU of a product.',
		'category' => 'woocommerce-product-elements',
		'attributes' => array(
			'productId' => array(
				'type' => 'number',
				'default' => 0
			),
			'isDescendantOfAllProducts' => array(
				'type' => 'boolean',
				'default' => false
			),
			'showProductSelector' => array(
				'type' => 'boolean',
				'default' => false
			),
			'prefix' => array(
				'type' => 'string',
				'default' => 'SKU:'
			),
			'suffix' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'usesContext' => array(
			'query',
			'queryId',
			'postId'
		),
		'ancestor' => array(
			'woocommerce/product-meta',
			'woocommerce/all-products',
			'woocommerce/single-product',
			'woocommerce/product-template',
			'core/post-template'
		),
		'supports' => array(
			'html' => false,
			'interactivity' => array(
				'clientNavigation' => true
			),
			'color' => array(
				'text' => true,
				'background' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'keywords' => array(
			'WooCommerce'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'product-specifications' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-specifications',
		'version' => '1.0.0',
		'title' => 'Product Specifications',
		'description' => 'Display product weight, dimensions, and attributes.',
		'category' => 'woocommerce',
		'keywords' => array(
			'attributes',
			'weight',
			'dimensions',
			'additional information'
		),
		'textdomain' => 'woocommerce',
		'ancestor' => array(
			'woocommerce/single-product',
			'woocommerce/product-template',
			'core/post-template'
		),
		'attributes' => array(
			'showWeight' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showDimensions' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showAttributes' => array(
				'type' => 'boolean',
				'default' => true
			)
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'supports' => array(
			'html' => false,
			'align' => array(
				'wide',
				'full'
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true
			)
		)
	),
	'product-stock-indicator' => array(
		'name' => 'woocommerce/product-stock-indicator',
		'icon' => 'info',
		'title' => 'Product Stock Indicator',
		'description' => 'Let shoppers know when products are out of stock or on backorder. This block is hidden when products are in stock.',
		'category' => 'woocommerce-product-elements',
		'attributes' => array(
			'isDescendantOfAllProducts' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'supports' => array(
			'html' => false,
			'interactivity' => true,
			'color' => array(
				'text' => true,
				'background' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'ancestor' => array(
			'woocommerce/all-products',
			'woocommerce/single-product',
			'woocommerce/product-template',
			'core/post-template'
		),
		'usesContext' => array(
			'query',
			'queryId',
			'postId'
		),
		'keywords' => array(
			'WooCommerce'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'product-summary' => array(
		'name' => 'woocommerce/product-summary',
		'icon' => 'page',
		'title' => 'Product Summary',
		'description' => 'Display a short description about a product.',
		'category' => 'woocommerce-product-elements',
		'attributes' => array(
			'productId' => array(
				'type' => 'number',
				'default' => 0
			),
			'isDescendentOfQueryLoop' => array(
				'type' => 'boolean',
				'default' => false
			),
			'isDescendentOfSingleProductTemplate' => array(
				'type' => 'boolean',
				'default' => false
			),
			'isDescendentOfSingleProductBlock' => array(
				'type' => 'boolean',
				'default' => false
			),
			'isDescendantOfAllProducts' => array(
				'type' => 'boolean',
				'default' => false
			),
			'showDescriptionIfEmpty' => array(
				'type' => 'boolean',
				'default' => false
			),
			'showLink' => array(
				'type' => 'boolean',
				'default' => false
			),
			'summaryLength' => array(
				'type' => 'number',
				'default' => 0
			),
			'linkText' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'color' => array(
				'text' => true,
				'background' => true,
				'link' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'textAlign' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'__experimentalSelector' => '.wc-block-components-product-summary'
		),
		'ancestor' => array(
			'woocommerce/all-products',
			'woocommerce/featured-product',
			'woocommerce/single-product',
			'woocommerce/product-template',
			'core/post-template'
		),
		'usesContext' => array(
			'query',
			'queryId',
			'postId'
		),
		'keywords' => array(
			'WooCommerce'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'product-tag' => array(
		'name' => 'woocommerce/product-tag',
		'title' => 'Products by Tag',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'description' => 'Display a grid of products with selected tags.',
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => false
			),
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'inserter' => false
		),
		'attributes' => array(
			'columns' => array(
				'type' => 'number',
				'default' => 3
			),
			'rows' => array(
				'type' => 'number',
				'default' => 3
			),
			'alignButtons' => array(
				'type' => 'boolean',
				'default' => false
			),
			'contentVisibility' => array(
				'type' => 'object',
				'default' => array(
					'image' => true,
					'title' => true,
					'price' => true,
					'rating' => true,
					'button' => true
				),
				'properties' => array(
					'image' => array(
						'type' => 'boolean',
						'default' => true
					),
					'title' => array(
						'type' => 'boolean',
						'default' => true
					),
					'price' => array(
						'type' => 'boolean',
						'default' => true
					),
					'rating' => array(
						'type' => 'boolean',
						'default' => true
					),
					'button' => array(
						'type' => 'boolean',
						'default' => true
					)
				)
			),
			'tags' => array(
				'type' => 'array',
				'default' => array(
					
				)
			),
			'tagOperator' => array(
				'type' => 'string',
				'default' => 'any'
			),
			'orderby' => array(
				'type' => 'string',
				'default' => 'date'
			),
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			),
			'stockStatus' => array(
				'type' => 'array'
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'product-template' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-template',
		'title' => 'Product Template',
		'category' => 'woocommerce',
		'description' => 'Contains the block elements used to render a product.',
		'keywords' => array(
			'WooCommerce'
		),
		'textdomain' => 'woocommerce',
		'usesContext' => array(
			'queryId',
			'query',
			'queryContext',
			'displayLayout',
			'templateSlug',
			'postId',
			'queryContextIncludes',
			'collection',
			'__privateProductCollectionPreviewState'
		),
		'supports' => array(
			'interactivity' => true,
			'inserter' => false,
			'reusable' => false,
			'html' => false,
			'align' => array(
				'wide',
				'full'
			),
			'anchor' => true,
			'color' => array(
				'gradients' => true,
				'link' => true,
				'__experimentalDefaultControls' => array(
					'background' => true,
					'text' => true
				)
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'layout' => array(
				'allowEditing' => false,
				'allowSwitching' => false,
				'allowInheriting' => false,
				'allowSizingOnChildren' => false,
				'allowVerticalAlignment' => false
			),
			'email' => true
		),
		'editorStyle' => 'file:../woocommerce/product-template-editor.css',
		'style' => 'file:../woocommerce/product-template-style.css'
	),
	'product-title' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/product-title',
		'version' => '1.0.0',
		'title' => 'Product Title',
		'category' => 'woocommerce-product-elements',
		'description' => 'Display the title of a product.',
		'supports' => array(
			'html' => false,
			'interactivity' => array(
				'clientNavigation' => false
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontWeight' => true,
				'__experimentalTextTransform' => true,
				'__experimentalFontFamily' => true
			),
			'color' => array(
				'text' => true,
				'background' => true,
				'link' => false,
				'gradients' => true,
				'__experimentalSkipSerialization' => true
			),
			'spacing' => array(
				'margin' => true,
				'__experimentalSkipSerialization' => true
			),
			'__experimentalSelector' => '.wc-block-components-product-title'
		),
		'textdomain' => 'woocommerce',
		'attributes' => array(
			'headingLevel' => array(
				'type' => 'number',
				'default' => 2
			),
			'showProductLink' => array(
				'type' => 'boolean',
				'default' => true
			),
			'linkTarget' => array(
				'type' => 'string'
			),
			'productId' => array(
				'type' => 'number',
				'default' => 0
			),
			'align' => array(
				'type' => 'string'
			)
		),
		'ancestor' => array(
			'woocommerce/all-products'
		)
	),
	'product-top-rated' => array(
		'name' => 'woocommerce/product-top-rated',
		'title' => 'Top Rated Products',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'description' => 'Display a grid of your top rated products.',
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => false
			),
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'inserter' => false
		),
		'attributes' => array(
			'columns' => array(
				'type' => 'number',
				'default' => 3
			),
			'rows' => array(
				'type' => 'number',
				'default' => 3
			),
			'alignButtons' => array(
				'type' => 'boolean',
				'default' => false
			),
			'contentVisibility' => array(
				'type' => 'object',
				'default' => array(
					'image' => true,
					'title' => true,
					'price' => true,
					'rating' => true,
					'button' => true
				),
				'properties' => array(
					'image' => array(
						'type' => 'boolean',
						'default' => true
					),
					'title' => array(
						'type' => 'boolean',
						'default' => true
					),
					'price' => array(
						'type' => 'boolean',
						'default' => true
					),
					'rating' => array(
						'type' => 'boolean',
						'default' => true
					),
					'button' => array(
						'type' => 'boolean',
						'default' => true
					)
				)
			),
			'categories' => array(
				'type' => 'array',
				'default' => array(
					
				)
			),
			'catOperator' => array(
				'type' => 'string',
				'default' => 'any'
			),
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			),
			'stockStatus' => array(
				'type' => 'array'
			),
			'editMode' => array(
				'type' => 'boolean',
				'default' => true
			),
			'orderby' => array(
				'type' => 'string',
				'enum' => array(
					'date',
					'popularity',
					'price_asc',
					'price_desc',
					'rating',
					'title',
					'menu_order'
				),
				'default' => 'rating'
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'products-by-attribute' => array(
		'name' => 'woocommerce/products-by-attribute',
		'title' => 'Products by Attribute',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'description' => 'Display a grid of products with selected attributes.',
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => false
			),
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'inserter' => false
		),
		'attributes' => array(
			'attributes' => array(
				'type' => 'array',
				'default' => array(
					
				)
			),
			'attrOperator' => array(
				'type' => 'string',
				'enum' => array(
					'all',
					'any'
				),
				'default' => 'any'
			),
			'columns' => array(
				'type' => 'number',
				'default' => 3
			),
			'contentVisibility' => array(
				'type' => 'object',
				'default' => array(
					'image' => true,
					'title' => true,
					'price' => true,
					'rating' => true,
					'button' => true
				),
				'properties' => array(
					'image' => array(
						'type' => 'boolean',
						'default' => true
					),
					'title' => array(
						'type' => 'boolean',
						'default' => true
					),
					'price' => array(
						'type' => 'boolean',
						'default' => true
					),
					'rating' => array(
						'type' => 'boolean',
						'default' => true
					),
					'button' => array(
						'type' => 'boolean',
						'default' => true
					)
				)
			),
			'orderby' => array(
				'type' => 'string',
				'enum' => array(
					'date',
					'popularity',
					'price_asc',
					'price_desc',
					'rating',
					'title',
					'menu_order'
				),
				'default' => 'date'
			),
			'rows' => array(
				'type' => 'number',
				'default' => 3
			),
			'alignButtons' => array(
				'type' => 'boolean',
				'default' => false
			),
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			),
			'stockStatus' => array(
				'type' => 'array'
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'rating-filter' => array(
		'name' => 'woocommerce/rating-filter',
		'title' => 'Filter by Rating Controls',
		'description' => 'Enable customers to filter the product grid by rating.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => false
			),
			'html' => false,
			'multiple' => false,
			'color' => array(
				'background' => true,
				'text' => true,
				'button' => true
			),
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'showCounts' => array(
				'type' => 'boolean',
				'default' => false
			),
			'displayStyle' => array(
				'type' => 'string',
				'default' => 'list'
			),
			'showFilterButton' => array(
				'type' => 'boolean',
				'default' => false
			),
			'selectType' => array(
				'type' => 'string',
				'default' => 'multiple'
			),
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'related-products' => array(
		'name' => 'woocommerce/related-products',
		'title' => 'Related Products',
		'icon' => 'product',
		'description' => 'Display related products.',
		'category' => 'woocommerce',
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'align' => true,
			'reusable' => false,
			'inserter' => false
		),
		'keywords' => array(
			'WooCommerce'
		),
		'usesContext' => array(
			'postId',
			'postType',
			'queryId'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'reviews-by-category' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/reviews-by-category',
		'title' => 'Reviews by Category',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'description' => 'Show product reviews from specific categories.',
		'textdomain' => 'woocommerce',
		'supports' => array(
			'html' => false,
			'interactivity' => array(
				'clientNavigation' => true
			),
			'color' => array(
				'background' => false
			),
			'typography' => array(
				'fontSize' => true
			)
		)
	),
	'reviews-by-product' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'woocommerce/reviews-by-product',
		'title' => 'Reviews by Product',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'description' => 'Display reviews for your products.',
		'textdomain' => 'woocommerce',
		'supports' => array(
			'html' => false,
			'interactivity' => array(
				'clientNavigation' => true
			),
			'color' => array(
				'background' => false
			),
			'typography' => array(
				'fontSize' => true
			)
		)
	),
	'single-product' => array(
		'name' => 'woocommerce/single-product',
		'icon' => 'info',
		'title' => 'Product',
		'description' => 'Display a single product of your choice with full control over its presentation.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce',
			'single product'
		),
		'supports' => array(
			'interactivity' => true,
			'align' => array(
				'wide',
				'full'
			)
		),
		'attributes' => array(
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			),
			'productId' => array(
				'type' => 'number'
			)
		),
		'example' => array(
			'attributes' => array(
				'isPreview' => true
			)
		),
		'usesContext' => array(
			'postId',
			'postType',
			'queryId'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'stock-filter' => array(
		'name' => 'woocommerce/stock-filter',
		'title' => 'Filter by Stock Controls',
		'description' => 'Enable customers to filter the product grid by stock status.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => false
			),
			'html' => false,
			'multiple' => false,
			'color' => array(
				'background' => true,
				'text' => true,
				'button' => true
			),
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'headingLevel' => array(
				'type' => 'number',
				'default' => 3
			),
			'showCounts' => array(
				'type' => 'boolean',
				'default' => false
			),
			'showFilterButton' => array(
				'type' => 'boolean',
				'default' => false
			),
			'displayStyle' => array(
				'type' => 'string',
				'default' => 'list'
			),
			'selectType' => array(
				'type' => 'string',
				'default' => 'multiple'
			),
			'isPreview' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'store-notices' => array(
		'name' => 'woocommerce/store-notices',
		'title' => 'Store Notices',
		'description' => 'Display shopper-facing notifications generated by WooCommerce or extensions.',
		'category' => 'woocommerce',
		'keywords' => array(
			'WooCommerce'
		),
		'supports' => array(
			'interactivity' => array(
				'clientNavigation' => true
			),
			'multiple' => false,
			'align' => array(
				'wide',
				'full'
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'wide'
			)
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'cart-accepted-payment-methods-block' => array(
		'name' => 'woocommerce/cart-accepted-payment-methods-block',
		'version' => '1.0.0',
		'title' => 'Accepted Payment Methods',
		'description' => 'Display accepted payment methods.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => true
		),
		'parent' => array(
			'woocommerce/cart-totals-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'cart-cross-sells-block' => array(
		'name' => 'woocommerce/cart-cross-sells-block',
		'version' => '1.0.0',
		'title' => 'Cart Cross-Sells',
		'description' => 'Shows the Cross-Sells block.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false
		),
		'parent' => array(
			'woocommerce/cart-items-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'cart-cross-sells-products-block' => array(
		'name' => 'woocommerce/cart-cross-sells-products-block',
		'version' => '1.0.0',
		'title' => 'Cart Cross-Sells Products',
		'description' => 'Shows the Cross-Sells products.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false,
			'email' => true
		),
		'attributes' => array(
			'columns' => array(
				'type' => 'number',
				'default' => 3
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/cart-cross-sells-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'cart-express-payment-block' => array(
		'name' => 'woocommerce/cart-express-payment-block',
		'version' => '1.0.0',
		'title' => 'Express Checkout',
		'description' => 'Allow customers to breeze through with quick payment options.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'showButtonStyles' => array(
				'type' => 'boolean',
				'default' => false
			),
			'buttonHeight' => array(
				'type' => 'string',
				'default' => '48'
			),
			'buttonBorderRadius' => array(
				'type' => 'string',
				'default' => '4'
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/cart-totals-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'cart-items-block' => array(
		'name' => 'woocommerce/cart-items-block',
		'version' => '1.0.0',
		'title' => 'Cart Items',
		'description' => 'Column containing cart items.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/filled-cart-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'cart-line-items-block' => array(
		'name' => 'woocommerce/cart-line-items-block',
		'version' => '1.0.0',
		'title' => 'Cart Line Items',
		'description' => 'Block containing current line items in Cart.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/cart-items-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'cart-order-summary-block' => array(
		'name' => 'woocommerce/cart-order-summary-block',
		'version' => '1.0.0',
		'title' => 'Order Summary',
		'description' => 'Show customers a summary of their order.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/cart-totals-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'cart-order-summary-coupon-form-block' => array(
		'name' => 'woocommerce/cart-order-summary-coupon-form-block',
		'version' => '1.0.0',
		'title' => 'Coupon Form',
		'description' => 'Shows the apply coupon form.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => false,
					'move' => false
				)
			)
		),
		'parent' => array(
			'woocommerce/cart-order-summary-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'cart-order-summary-discount-block' => array(
		'name' => 'woocommerce/cart-order-summary-discount-block',
		'version' => '1.0.0',
		'title' => 'Discount',
		'description' => 'Shows the cart discount row.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'lock' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/cart-order-summary-totals-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'cart-order-summary-fee-block' => array(
		'name' => 'woocommerce/cart-order-summary-fee-block',
		'version' => '1.0.0',
		'title' => 'Fees',
		'description' => 'Shows the cart fee row.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'lock' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/cart-order-summary-totals-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'cart-order-summary-heading-block' => array(
		'name' => 'woocommerce/cart-order-summary-heading-block',
		'version' => '1.0.0',
		'title' => 'Heading',
		'description' => 'Shows the heading row.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'content' => array(
				'type' => 'string',
				'default' => 'Cart totals'
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => false,
					'move' => false
				)
			)
		),
		'parent' => array(
			'woocommerce/cart-order-summary-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'cart-order-summary-shipping-block' => array(
		'name' => 'woocommerce/cart-order-summary-shipping-block',
		'version' => '1.0.0',
		'title' => 'Shipping',
		'description' => 'Shows the cart shipping row.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'lock' => false
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/cart-order-summary-totals-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'cart-order-summary-subtotal-block' => array(
		'name' => 'woocommerce/cart-order-summary-subtotal-block',
		'version' => '1.0.0',
		'title' => 'Subtotal',
		'description' => 'Shows the cart subtotal row.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'lock' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/cart-order-summary-totals-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'cart-order-summary-taxes-block' => array(
		'name' => 'woocommerce/cart-order-summary-taxes-block',
		'version' => '1.0.0',
		'title' => 'Taxes',
		'description' => 'Shows the cart taxes row.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'lock' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/cart-order-summary-totals-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'cart-order-summary-totals-block' => array(
		'name' => 'woocommerce/cart-order-summary-totals-block',
		'version' => '1.0.0',
		'title' => 'Totals',
		'description' => 'Shows the subtotal, fees, discounts, shipping and taxes.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'lock' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => false
				)
			)
		),
		'parent' => array(
			'woocommerce/cart-order-summary-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'cart-totals-block' => array(
		'name' => 'woocommerce/cart-totals-block',
		'version' => '1.0.0',
		'title' => 'Cart Totals',
		'description' => 'Column containing the cart totals.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'checkbox' => array(
				'type' => 'boolean',
				'default' => false
			),
			'text' => array(
				'type' => 'string',
				'required' => false
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/filled-cart-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-actions-block' => array(
		'name' => 'woocommerce/checkout-actions-block',
		'version' => '1.0.0',
		'title' => 'Actions',
		'description' => 'Allow customers to place their order.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			),
			'cartPageId' => array(
				'type' => 'number',
				'default' => 0
			),
			'showReturnToCart' => array(
				'type' => 'boolean',
				'default' => true
			),
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'priceSeparator' => array(
				'type' => 'string',
				'default' => '·'
			)
		),
		'parent' => array(
			'woocommerce/checkout-fields-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-additional-information-block' => array(
		'name' => 'woocommerce/checkout-additional-information-block',
		'version' => '1.0.0',
		'title' => 'Additional information',
		'description' => 'Render additional fields in the \'Additional information\' location.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => false
				)
			)
		),
		'parent' => array(
			'woocommerce/checkout-fields-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-billing-address-block' => array(
		'name' => 'woocommerce/checkout-billing-address-block',
		'version' => '1.0.0',
		'title' => 'Billing Address',
		'description' => 'Collect your customer\'s billing address.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/checkout-fields-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-contact-information-block' => array(
		'name' => 'woocommerce/checkout-contact-information-block',
		'version' => '1.0.0',
		'title' => 'Contact Information',
		'description' => 'Collect your customer\'s contact information.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/checkout-fields-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-express-payment-block' => array(
		'name' => 'woocommerce/checkout-express-payment-block',
		'version' => '1.0.0',
		'title' => 'Express Checkout',
		'description' => 'Allow customers to breeze through with quick payment options.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'showButtonStyles' => array(
				'type' => 'boolean',
				'default' => false
			),
			'buttonHeight' => array(
				'type' => 'string',
				'default' => '48'
			),
			'buttonBorderRadius' => array(
				'type' => 'string',
				'default' => '4'
			),
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/checkout-fields-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-fields-block' => array(
		'name' => 'woocommerce/checkout-fields-block',
		'version' => '1.0.0',
		'title' => 'Checkout Fields',
		'description' => 'Column containing checkout address fields.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/checkout'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-order-note-block' => array(
		'name' => 'woocommerce/checkout-order-note-block',
		'version' => '1.0.0',
		'title' => 'Order Note',
		'description' => 'Allow customers to add a note to their order.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => false,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/checkout-fields-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-order-summary-block' => array(
		'name' => 'woocommerce/checkout-order-summary-block',
		'version' => '1.0.0',
		'title' => 'Order Summary',
		'description' => 'Show customers a summary of their order.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/checkout-totals-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-order-summary-cart-items-block' => array(
		'name' => 'woocommerce/checkout-order-summary-cart-items-block',
		'version' => '1.0.0',
		'title' => 'Cart Items',
		'description' => 'Shows cart items.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'lock' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'disableProductDescriptions' => array(
				'type' => 'boolean',
				'default' => false
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => false
				)
			)
		),
		'parent' => array(
			'woocommerce/checkout-order-summary-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-order-summary-coupon-form-block' => array(
		'name' => 'woocommerce/checkout-order-summary-coupon-form-block',
		'version' => '1.0.0',
		'title' => 'Coupon Form',
		'description' => 'Shows the apply coupon form.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => false,
					'move' => false
				)
			)
		),
		'parent' => array(
			'woocommerce/checkout-order-summary-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-order-summary-discount-block' => array(
		'name' => 'woocommerce/checkout-order-summary-discount-block',
		'version' => '1.0.0',
		'title' => 'Discount',
		'description' => 'Shows the cart discount row.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'lock' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/checkout-order-summary-totals-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-order-summary-fee-block' => array(
		'name' => 'woocommerce/checkout-order-summary-fee-block',
		'version' => '1.0.0',
		'title' => 'Fees',
		'description' => 'Shows the cart fee row.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'lock' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/checkout-order-summary-totals-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-order-summary-shipping-block' => array(
		'name' => 'woocommerce/checkout-order-summary-shipping-block',
		'version' => '1.0.0',
		'title' => 'Shipping',
		'description' => 'Shows the cart shipping row.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'lock' => false
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/checkout-order-summary-totals-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-order-summary-subtotal-block' => array(
		'name' => 'woocommerce/checkout-order-summary-subtotal-block',
		'version' => '1.0.0',
		'title' => 'Subtotal',
		'description' => 'Shows the cart subtotal row.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'lock' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/checkout-order-summary-totals-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-order-summary-taxes-block' => array(
		'name' => 'woocommerce/checkout-order-summary-taxes-block',
		'version' => '1.0.0',
		'title' => 'Taxes',
		'description' => 'Shows the cart taxes row.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'lock' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/checkout-order-summary-totals-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-order-summary-totals-block' => array(
		'name' => 'woocommerce/checkout-order-summary-totals-block',
		'version' => '1.0.0',
		'title' => 'Totals',
		'description' => 'Shows the subtotal, fees, discounts, shipping and taxes.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'lock' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => false
				)
			)
		),
		'parent' => array(
			'woocommerce/checkout-order-summary-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-payment-block' => array(
		'name' => 'woocommerce/checkout-payment-block',
		'version' => '1.0.0',
		'title' => 'Payment Options',
		'description' => 'Payment options for your store.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/checkout-fields-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-pickup-options-block' => array(
		'name' => 'woocommerce/checkout-pickup-options-block',
		'version' => '1.0.0',
		'title' => 'Pickup Method',
		'description' => 'Shows local pickup locations.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/checkout-fields-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-shipping-address-block' => array(
		'name' => 'woocommerce/checkout-shipping-address-block',
		'version' => '1.0.0',
		'title' => 'Shipping Address',
		'description' => 'Collect your customer\'s shipping address.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/checkout-fields-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-shipping-method-block' => array(
		'name' => 'woocommerce/checkout-shipping-method-block',
		'version' => '1.0.0',
		'title' => 'Delivery',
		'description' => 'Select between shipping or local pickup.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/checkout-fields-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-shipping-methods-block' => array(
		'name' => 'woocommerce/checkout-shipping-methods-block',
		'version' => '1.0.0',
		'title' => 'Shipping Options',
		'description' => 'Display shipping options and rates for your store.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/checkout-fields-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-terms-block' => array(
		'name' => 'woocommerce/checkout-terms-block',
		'version' => '1.0.0',
		'title' => 'Terms and Conditions',
		'description' => 'Ensure that customers agree to your Terms & Conditions and Privacy Policy.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'checkbox' => array(
				'type' => 'boolean',
				'default' => false
			),
			'text' => array(
				'type' => 'string',
				'required' => false
			),
			'showSeparator' => array(
				'type' => 'boolean',
				'default' => true
			)
		),
		'parent' => array(
			'woocommerce/checkout-fields-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'checkout-totals-block' => array(
		'name' => 'woocommerce/checkout-totals-block',
		'version' => '1.0.0',
		'title' => 'Checkout Totals',
		'description' => 'Column containing the checkout totals.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'className' => array(
				'type' => 'string',
				'default' => ''
			),
			'checkbox' => array(
				'type' => 'boolean',
				'default' => false
			),
			'text' => array(
				'type' => 'string',
				'required' => false
			)
		),
		'parent' => array(
			'woocommerce/checkout'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'empty-cart-block' => array(
		'name' => 'woocommerce/empty-cart-block',
		'version' => '1.0.0',
		'title' => 'Empty Cart',
		'description' => 'Contains blocks that are displayed when the cart is empty.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => array(
				'wide'
			),
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/cart'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'empty-mini-cart-contents-block' => array(
		'name' => 'woocommerce/empty-mini-cart-contents-block',
		'version' => '1.0.0',
		'title' => 'Empty Mini-Cart view',
		'description' => 'Blocks that are displayed when the Mini-Cart is empty.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false,
			'interactivity' => true
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/mini-cart-contents'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'filled-cart-block' => array(
		'name' => 'woocommerce/filled-cart-block',
		'version' => '1.0.0',
		'title' => 'Filled Cart',
		'description' => 'Contains blocks that are displayed when the cart contains products.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => array(
				'wide'
			),
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/cart'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	),
	'filled-mini-cart-contents-block' => array(
		'name' => 'woocommerce/filled-mini-cart-contents-block',
		'version' => '1.0.0',
		'title' => 'Filled Mini-Cart view',
		'description' => 'Contains blocks that display the content of the Mini-Cart.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false,
			'interactivity' => true
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/mini-cart-contents'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'mini-cart-cart-button-block' => array(
		'name' => 'woocommerce/mini-cart-cart-button-block',
		'version' => '1.0.0',
		'title' => 'Mini-Cart View Cart Button',
		'description' => 'Block that displays the cart button when the Mini-Cart has products.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => true,
			'color' => array(
				'text' => true,
				'background' => true
			),
			'interactivity' => array(
				'clientNavigation' => true
			)
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => false,
					'move' => false
				)
			),
			'cartButtonLabel' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'styles' => array(
			array(
				'name' => 'fill',
				'label' => 'Fill'
			),
			array(
				'name' => 'outline',
				'label' => 'Outline',
				'isDefault' => true
			)
		),
		'parent' => array(
			'woocommerce/mini-cart-footer-block'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'mini-cart-checkout-button-block' => array(
		'name' => 'woocommerce/mini-cart-checkout-button-block',
		'version' => '1.0.0',
		'title' => 'Mini-Cart Proceed to Checkout Button',
		'description' => 'Block that displays the checkout button when the Mini-Cart has products.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => true,
			'color' => array(
				'text' => true,
				'background' => true
			),
			'interactivity' => array(
				'clientNavigation' => true
			)
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => false,
					'move' => false
				)
			),
			'checkoutButtonLabel' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'styles' => array(
			array(
				'name' => 'fill',
				'label' => 'Fill',
				'isDefault' => true
			),
			array(
				'name' => 'outline',
				'label' => 'Outline'
			)
		),
		'parent' => array(
			'woocommerce/mini-cart-footer-block'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'mini-cart-footer-block' => array(
		'name' => 'woocommerce/mini-cart-footer-block',
		'version' => '1.0.0',
		'title' => 'Mini-Cart Footer',
		'description' => 'Block that displays the footer of the Mini-Cart block.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false,
			'interactivity' => true
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/filled-mini-cart-contents-block'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'mini-cart-items-block' => array(
		'name' => 'woocommerce/mini-cart-items-block',
		'version' => '1.0.0',
		'title' => 'Mini-Cart Items',
		'description' => 'Contains the products table and other custom blocks of filled mini-cart.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false,
			'interactivity' => array(
				'clientNavigation' => true
			)
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/filled-mini-cart-contents-block'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'mini-cart-products-table-block' => array(
		'name' => 'woocommerce/mini-cart-products-table-block',
		'version' => '1.0.0',
		'title' => 'Mini-Cart Products Table',
		'description' => 'Block that displays the products table of the Mini-Cart block.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false,
			'interactivity' => true
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => false
				)
			)
		),
		'parent' => array(
			'woocommerce/mini-cart-items-block'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'mini-cart-shopping-button-block' => array(
		'name' => 'woocommerce/mini-cart-shopping-button-block',
		'version' => '1.0.0',
		'title' => 'Mini-Cart Shopping Button',
		'description' => 'Block that displays the shopping button when the Mini-Cart is empty.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => true,
			'color' => array(
				'text' => true,
				'background' => true
			),
			'interactivity' => array(
				'clientNavigation' => true
			)
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => false,
					'move' => false
				)
			),
			'startShoppingButtonLabel' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'styles' => array(
			array(
				'name' => 'fill',
				'label' => 'Fill',
				'isDefault' => true
			),
			array(
				'name' => 'outline',
				'label' => 'Outline'
			)
		),
		'parent' => array(
			'woocommerce/empty-mini-cart-contents-block'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'mini-cart-title-block' => array(
		'name' => 'woocommerce/mini-cart-title-block',
		'version' => '1.0.0',
		'title' => 'Mini-Cart Title',
		'description' => 'Block that displays the title of the Mini-Cart block.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false,
			'color' => array(
				'text' => true,
				'background' => false
			),
			'typography' => array(
				'fontSize' => true
			),
			'interactivity' => array(
				'clientNavigation' => true
			)
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/filled-mini-cart-contents-block'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'mini-cart-title-items-counter-block' => array(
		'name' => 'woocommerce/mini-cart-title-items-counter-block',
		'version' => '1.0.0',
		'title' => 'Mini-Cart Title Items Counter',
		'description' => 'Block that displays the items counter part of the Mini-Cart Title block.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false,
			'color' => array(
				'text' => true,
				'background' => true
			),
			'typography' => array(
				'fontSize' => true
			),
			'spacing' => array(
				'padding' => true
			),
			'interactivity' => true
		),
		'parent' => array(
			'woocommerce/mini-cart-title-block'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'mini-cart-title-label-block' => array(
		'name' => 'woocommerce/mini-cart-title-label-block',
		'version' => '1.0.0',
		'title' => 'Mini-Cart Title Label',
		'description' => 'Block that displays the \'Your cart\' part of the Mini-Cart Title block.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false,
			'color' => array(
				'text' => true,
				'background' => true
			),
			'typography' => array(
				'fontSize' => true
			),
			'spacing' => array(
				'padding' => true
			),
			'interactivity' => array(
				'clientNavigation' => true
			)
		),
		'attributes' => array(
			'label' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'parent' => array(
			'woocommerce/mini-cart-title-block'
		),
		'textdomain' => 'woocommerce',
		'apiVersion' => 3,
		'$schema' => 'https://schemas.wp.org/trunk/block.json'
	),
	'proceed-to-checkout-block' => array(
		'name' => 'woocommerce/proceed-to-checkout-block',
		'version' => '1.0.0',
		'title' => 'Proceed to Checkout',
		'description' => 'Allow customers proceed to Checkout.',
		'category' => 'woocommerce',
		'supports' => array(
			'align' => false,
			'html' => false,
			'multiple' => false,
			'reusable' => false,
			'inserter' => false,
			'lock' => false
		),
		'attributes' => array(
			'lock' => array(
				'type' => 'object',
				'default' => array(
					'remove' => true,
					'move' => true
				)
			)
		),
		'parent' => array(
			'woocommerce/cart-totals-block'
		),
		'textdomain' => 'woocommerce',
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3
	)
);
