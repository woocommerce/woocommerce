/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

const trackableProductValueKeyMap: Record< string, string > = {
	cross_sell_ids: 'cross_sells',
	reviews_allowed: 'enable_reviews',
	downloadable: 'is_downloadable',
	virtual: 'is_virtual',
	images: 'product_gallery',
	upsell_ids: 'upsells',
};
const potentialTrackableProductValueKeys = [
	'attributes',
	'categories',
	'description',
	'manage_stock',
	'menu_order',
	'note',
	'purchase_note',
	'sale_price',
	'short_description',
	'tags',
	'weight',
	'cross_sell_ids',
	'reviews_allowed',
	'downloadable',
	'virtual',
	'images',
	'upsell_ids',
];

export function recordProductEvent(
	eventName: string,
	product: Pick< Product, 'id' | 'type' > & Partial< Product >
) {
	const { id, type } = product;

	const eventProps: Record< string, string | number > = {
		product_id: id,
		source: 'product-blocks-editor-v1',
		product_type: type,
	};

	if ( product.parent_id > 0 ) {
		product.note = product.description;
		delete product.description;
	}

	for ( const productValueKey of Object.keys( product ) ) {
		if ( potentialTrackableProductValueKeys.includes( productValueKey ) ) {
			const eventPropKey =
				trackableProductValueKeyMap[ productValueKey ] ||
				productValueKey;
			if (
				Array.isArray( product[ productValueKey ] ) ||
				typeof product[ productValueKey ] === 'string'
			) {
				eventProps[ eventPropKey ] = product[ productValueKey ].length
					? 'yes'
					: 'no';
			} else {
				eventProps[ eventPropKey ] = product[ productValueKey ]
					? 'yes'
					: 'no';
			}
		}
	}
	if ( product.downloadable || product.virtual ) {
		const { downloadable, virtual } = product;
		const product_type_options = {
			virtual,
			downloadable,
		} as { [ key: string ]: boolean };
		eventProps.product_type_options = Object.keys( product_type_options )
			.filter( ( key ) => product_type_options[ key ] )
			.join( ',' );
	}

	if ( 'images' in product ) {
		eventProps.product_image = product.images.length ? 'yes' : 'no';
	}

	if ( product.dimensions ) {
		eventProps.dimensions =
			product.dimensions.length.length ||
			product.dimensions.width.length ||
			product.dimensions.height.length
				? 'yes'
				: 'no';
	}

	recordEvent( eventName, eventProps );
}
