/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SHOP_URL } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */

const Block = (): JSX.Element | null => {
	if ( ! SHOP_URL ) {
		return null;
	}

	return (
		<div className="wp-block-button aligncenter is-style-outline">
			<a className="wp-block-button__link" href={ SHOP_URL }>
				{ __( 'Start shopping', 'woo-gutenberg-products-block' ) }
			</a>
		</div>
	);
};

export default Block;
