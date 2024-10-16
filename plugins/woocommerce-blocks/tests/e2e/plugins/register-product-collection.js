/* eslint-disable @typescript-eslint/no-unused-vars, no-undef */
const { __experimentalRegisterProductCollection } = wc.wcBlocksRegistry;

/**
 * Basic usage of `__experimentalRegisterProductCollection`.
 */
// Register a new collection.
__experimentalRegisterProductCollection( {
	name: 'woocommerce/product-collection/my-custom-collection',
	title: 'My Custom Collection',
	description: 'This is a custom collection.',
	keywords: [ 'custom collection', 'product collection' ],
	attributes: {
		query: {
			perPage: 5,
		},
		hideControls: [ 'keyword', 'on-sale' ],
	},
	scope: [ 'block' ],
} );

// Register a new collection with a preview.
__experimentalRegisterProductCollection( {
	name: 'woocommerce/product-collection/my-custom-collection-with-preview',
	title: 'My Custom Collection with Preview',
	description: 'This is a custom collection with preview.',
	keywords: [ 'custom collection', 'product collection' ],
	preview: {
		initialPreviewState: {
			isPreview: true,
			previewMessage:
				'This is a preview message for my custom collection with preview.',
		},
	},
	scope: [ 'block' ],
} );

// Advanced usage of preview.
__experimentalRegisterProductCollection( {
	name: 'woocommerce/product-collection/my-custom-collection-with-advanced-preview',
	title: 'My Custom Collection with Advanced Preview',
	description: 'This is a custom collection with advanced preview.',
	keywords: [ 'custom collection', 'product collection' ],
	preview: {
		setPreviewState: ( {
			setState,
			attributes: currentAttributes,
			location,
		} ) => {
			// You can access the current attributes and location.
			// console.log( 'Current attributes:', currentAttributes );
			// console.log( 'Location:', location );

			window.__removePreview = () => {
				setState( {
					isPreview: false,
					previewMessage: '',
				} );
			};
		},
		initialPreviewState: {
			isPreview: true,
			previewMessage:
				'This is a preview message for my custom collection with advanced preview.',
		},
	},
	scope: [ 'block' ],
} );

/**
 * Register product collections with different `usesReference` values.
 */
// Product context
__experimentalRegisterProductCollection( {
	name: 'woocommerce/product-collection/my-custom-collection-product-context',
	title: 'My Custom Collection - Product Context',
	description: 'This is a custom collection with product context.',
	usesReference: [ 'product' ],
	scope: [ 'block' ],
} );

// Cart context
__experimentalRegisterProductCollection( {
	name: 'woocommerce/product-collection/my-custom-collection-cart-context',
	title: 'My Custom Collection - Cart Context',
	description: 'This is a custom collection with cart context.',
	usesReference: [ 'cart' ],
	scope: [ 'block' ],
} );

// Order context
__experimentalRegisterProductCollection( {
	name: 'woocommerce/product-collection/my-custom-collection-order-context',
	title: 'My Custom Collection - Order Context',
	description: 'This is a custom collection with order context.',
	usesReference: [ 'order' ],
	scope: [ 'block' ],
} );

// Archive context
__experimentalRegisterProductCollection( {
	name: 'woocommerce/product-collection/my-custom-collection-archive-context',
	title: 'My Custom Collection - Archive Context',
	description: 'This is a custom collection with archive context.',
	usesReference: [ 'archive' ],
	scope: [ 'block' ],
} );

// Multiple contexts
__experimentalRegisterProductCollection( {
	name: 'woocommerce/product-collection/my-custom-collection-multiple-contexts',
	title: 'My Custom Collection - Multiple Contexts',
	description: 'This is a custom collection with multiple contexts.',
	usesReference: [ 'product', 'order' ],
	scope: [ 'block' ],
} );
