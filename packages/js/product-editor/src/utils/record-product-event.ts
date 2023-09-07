/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

export function recordProductEvent( eventName: string, product: Product ) {
	const {
		attributes,
		categories,
		cross_sell_ids,
		description,
		dimensions,
		downloadable,
		id,
		images,
		manage_stock,
		menu_order,
		purchase_note,
		reviews_allowed,
		sale_price,
		short_description,
		tags,
		type,
		upsell_ids,
		virtual,
		weight,
	} = product;

	const product_type_options = {
		virtual,
		downloadable,
	} as { [ key: string ]: boolean };

	recordEvent( eventName, {
		attributes: attributes.length ? 'yes' : 'no',
		categories: categories.length ? 'yes' : 'no',
		cross_sells: cross_sell_ids.length ? 'yes' : 'no',
		description: description.length ? 'yes' : 'no',
		dimensions:
			dimensions.length.length ||
			dimensions.width.length ||
			dimensions.height.length
				? 'yes'
				: 'no',
		enable_reviews: reviews_allowed ? 'yes' : 'no',
		is_downloadable: downloadable ? 'yes' : 'no',
		is_virtual: virtual ? 'yes' : 'no',
		manage_stock: manage_stock ? 'yes' : 'no',
		menu_order: menu_order ? 'yes' : 'no',
		product_id: id,
		product_gallery: images.length > 1 ? 'yes' : 'no',
		product_image: images.length ? 'yes' : 'no',
		product_type: type,
		product_type_options: Object.keys( product_type_options )
			.filter( ( key ) => product_type_options[ key ] )
			.join( ',' ),
		purchase_note: purchase_note.length ? 'yes' : 'no',
		sale_price: sale_price.length ? 'yes' : 'no',
		short_description: short_description.length ? 'yes' : 'no',
		source: 'product-blocks-editor-v1',
		tags: tags.length ? 'yes' : 'no',
		upsells: upsell_ids.length ? 'yes' : 'no',
		weight: weight.length ? 'yes' : 'no',
	} );
}
