/**
 * External dependencies
 */
import { Icon } from '@wordpress/components';
import { heading } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { registerElementVariation } from './utils';
import {
	PRODUCT_TITLE_VARIATION_NAME,
	PRODUCT_TITLE_NAMESPACES,
} from './constants';

export const CORE_NAME = 'core/post-title';

const registerProductTitle = () => {
	registerElementVariation( CORE_NAME, {
		blockDescription: __(
			'Display the title of a product.',
			'woocommerce'
		),
		blockIcon: <Icon icon={ heading } />,
		blockTitle: __( 'Product Title', 'woocommerce' ),
		variationName: PRODUCT_TITLE_VARIATION_NAME,
		namespaces: PRODUCT_TITLE_NAMESPACES,

		scope: [ 'block', 'inserter' ],
	} );
};

export default registerProductTitle;
