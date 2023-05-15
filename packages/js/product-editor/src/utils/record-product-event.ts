/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

export function recordProductEvent( eventName: string, product: Product ) {
	const { downloadable, id, manage_stock, type, virtual } = product;

	recordEvent( eventName, {
		new_product_page: true,
		product_id: id,
		product_type: type,
		is_downloadable: downloadable,
		is_virtual: virtual,
		manage_stock,
	} );
}
