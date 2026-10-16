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
import blockJson from '../../block.json';

export const CORE_NAME = 'core/post-title';
export const VARIATION_NAME = `${ blockJson.name }/product-title`;

const registerProductTitle = () => {
	registerElementVariation( CORE_NAME, {
		blockDescription: __(
			'Display the title of a product.',
			'woocommerce'
		),
		blockIcon: <Icon icon={ heading } />,
		blockTitle: __( 'Product Title', 'woocommerce' ),
		variationName: VARIATION_NAME,
		scope: [ 'block', 'inserter' ],
	} );
};

export default registerProductTitle;
