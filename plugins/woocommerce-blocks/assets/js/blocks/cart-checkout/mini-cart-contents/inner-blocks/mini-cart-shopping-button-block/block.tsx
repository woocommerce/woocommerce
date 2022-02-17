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
		<div className="wc-block-mini-cart__shopping-button">
			<a href={ SHOP_URL }>
				{ __( 'Start shopping', 'woo-gutenberg-products-block' ) }
			</a>
		</div>
	);
};

export default Block;
