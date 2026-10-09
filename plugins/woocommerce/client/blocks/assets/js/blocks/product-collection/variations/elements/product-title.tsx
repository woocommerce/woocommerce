/**
 * External dependencies
 */
import { Icon } from '@wordpress/components';
import { heading } from '@wordpress/icons';
import { getBlockType } from '@wordpress/blocks';
import domReady from '@wordpress/dom-ready';
import { __, _x } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { registerElementVariation } from './utils';
import blockJson from '../../block.json';
import titleMetadata from '../../../product-elements-blocks/title/block.json';

export const CORE_NAME = 'core/post-title';
export const VARIATION_NAME = `${ blockJson.name }/product-title`;

const registerProductTitle = () => {
	// The Product Title block registers after Product Collection and has server-translated metadata.
	domReady( () => {
		const productTitle = getBlockType( titleMetadata.name );
		registerElementVariation( CORE_NAME, {
			blockDescription:
				productTitle?.description ??
				_x(
					'Display the title of a product.',
					'block description',
					'woocommerce'
				),
			blockIcon: <Icon icon={ heading } />,
			blockTitle:
				productTitle?.title ?? __( 'Product Title', 'woocommerce' ),
			variationName: VARIATION_NAME,
			scope: [ 'block', 'inserter' ],
		} );
	} );
};

export default registerProductTitle;
