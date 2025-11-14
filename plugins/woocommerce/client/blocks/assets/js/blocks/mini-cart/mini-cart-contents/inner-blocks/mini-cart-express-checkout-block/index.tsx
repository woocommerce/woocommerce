/**
 * External dependencies
 */
import { Icon, payment } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore -- TypeScript expects some required properties which we already
// registered in PHP.
registerBlockType( 'woocommerce/mini-cart-express-checkout-block', {
	icon: (
		<Icon
			icon={ payment }
			className="wc-block-editor-components-block-icon"
		/>
	),
	edit: Edit,
	save: Save,
} );
