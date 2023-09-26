/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { AUTO_DRAFT_NAME } from './constants';

/**
 * Get the product title for use in the header.
 *
 * @param name          Name value entered for the product.
 * @param type          Product type.
 * @param persistedName Name already persisted to the database.
 * @return string
 */
export const getProductTitle = (
	name: string | undefined,
	type: string | undefined,
	persistedName: string | undefined
) => {
	if ( name?.length ) {
		return name;
	}

	if ( persistedName && persistedName !== AUTO_DRAFT_NAME ) {
		return persistedName;
	}

	switch ( type ) {
		case 'simple':
			return __( 'New standard product', 'woocommerce' );
		default:
			return __( 'New product', 'woocommerce' );
	}
};
