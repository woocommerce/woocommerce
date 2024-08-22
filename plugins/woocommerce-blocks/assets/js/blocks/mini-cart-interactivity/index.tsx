/**
 * External dependencies
 */
import { Icon } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';
import { miniCartAlt } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import edit from './edit';

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore -- TypeScript expects some required properties which we already
// registered in PHP.
registerBlockType( 'woocommerce/mini-cart-interactivity', {
	icon: {
		src: (
			<Icon
				icon={ miniCartAlt }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit,
	save() {
		return null;
	},
} );
