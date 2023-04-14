/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { AUTO_DRAFT_NAME, HEADER_TITLE_LIMIT } from './constants';

/**
 * Returns a truncated title if too long.
 *
 * @param  title The title.
 * @return Truncated title
 */
export const getTruncatedTitle = ( title: string ) => {
	if ( title.length > HEADER_TITLE_LIMIT ) {
		return title.substring( 0, HEADER_TITLE_LIMIT ) + '…';
	}

	return title;
};

/**
 * Get the header title using the product name.
 *
 * @param  editedProductName  Name value entered for the product.
 * @param  initialProductName Name already persisted to the database.
 * @return The new title
 */
export const getHeaderTitle = (
	editedProductName: string,
	initialProductName: string
): string => {
	const isProductNameNotEmpty = Boolean( editedProductName );
	const isProductNameDirty = editedProductName !== initialProductName;
	const isCreating = initialProductName === AUTO_DRAFT_NAME;

	if ( isProductNameNotEmpty && isProductNameDirty ) {
		return getTruncatedTitle( editedProductName );
	}

	if ( isCreating ) {
		return __( 'Add new product', 'woocommerce' );
	}

	return getTruncatedTitle( initialProductName );
};
