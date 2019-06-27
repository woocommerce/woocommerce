/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';

const updateProductStock = operations => async ( product, newStock ) => {
	const { addNotice } = dispatch( 'wc-admin' );
	const oldStockQuantity = product.stock_quantity;
	const resourceName = getResourceName( 'items-query-products-item', product.id );

	// Optimistically update product stock
	operations.updateLocally( [ resourceName ], {
		[ resourceName ]: { ...product, stock_quantity: newStock },
	} );

	const result = await operations.update( [ resourceName ], {
		[ resourceName ]: {
			id: product.id,
			type: product.type,
			parent_id: product.parent_id,
			stock_quantity: newStock,
		},
	} );
	const response = result[ 0 ][ resourceName ];
	if ( response && response.data ) {
		addNotice( {
			status: 'success',
			message: sprintf( __( '%s stock updated.', 'woocommerce-admin' ), product.name ),
		} );
	}
	if ( response && response.error ) {
		addNotice( {
			status: 'error',
			message: sprintf( __( '%s stock could not be updated.', 'woocommerce-admin' ), product.name ),
		} );
		// Revert local changes if the operation failed in the server
		operations.updateLocally( [ resourceName ], {
			[ resourceName ]: { ...product, stock_quantity: oldStockQuantity },
		} );
	}
};

export default {
	updateProductStock,
};
