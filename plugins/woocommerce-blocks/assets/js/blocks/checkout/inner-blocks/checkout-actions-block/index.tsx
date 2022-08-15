/**
 * External dependencies
 */
import { Icon, button } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import attributes from './attributes';
import { Edit, Save } from './edit';

registerBlockType( 'woocommerce/checkout-actions-block', {
	icon: {
		src: (
			<Icon
				icon={ button }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	attributes,
	edit: Edit,
	save: Save,
} );
