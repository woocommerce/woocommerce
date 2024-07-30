/* eslint-disable @typescript-eslint/no-unused-vars, no-undef */
const { __experimentalRegisterProductCollection } = wc.wcBlocksRegistry;

// Example 1: Register a new collection.
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
} );

// Example 2: Register a new collection with a preview.
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
} );

// Example 3: Advanced usage of preview.
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

			const timeoutID = setTimeout( () => {
				setState( {
					isPreview: false,
					previewMessage: '',
				} );
			}, 1000 );

			return () => clearTimeout( timeoutID );
		},
		initialPreviewState: {
			isPreview: true,
			previewMessage:
				'This is a preview message for my custom collection with advanced preview.',
		},
	},
} );
