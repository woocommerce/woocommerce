/**
 * The default layout built from the default template.
 */
export const DEFAULT_PRODUCT_LIST_LAYOUT = [
	[ 'woocommerce/product-image' ],
	[ 'woocommerce/product-title' ],
	[ 'woocommerce/product-price' ],
	[ 'woocommerce/product-rating' ],
	[ 'woocommerce/product-button' ],
];

/**
 * Converts innerblocks to a list of layout configs.
 *
 * @param {Object[]} innerBlocks Inner block components.
 */
export const getProductLayoutConfig = ( innerBlocks ) => {
	if ( ! innerBlocks || innerBlocks.length === 0 ) {
		return [];
	}

	return innerBlocks.map( ( block ) => {
		return [
			block.name,
			{
				...block.attributes,
				product: undefined,
				children:
					block.innerBlocks.length > 0
						? getProductLayoutConfig( block.innerBlocks )
						: [],
			},
		];
	} );
};
