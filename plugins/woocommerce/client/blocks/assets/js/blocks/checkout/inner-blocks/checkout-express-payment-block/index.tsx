/**
 * External dependencies
 */
import { Icon } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import expressIcon from '../../../cart-checkout-shared/icon';
import { Edit, Save } from './edit';

registerBlockType( 'woocommerce/checkout-express-payment-block', {
	icon: {
		src: (
			<Icon
				style={ { fill: 'none' } } // this is needed for this particular svg
				icon={ expressIcon }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit: Edit,
	save: Save,
} );
