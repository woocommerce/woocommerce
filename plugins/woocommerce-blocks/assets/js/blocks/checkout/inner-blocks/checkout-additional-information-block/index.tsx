/**
 * External dependencies
 */
import { Icon, customPostType } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import './style.scss';
import attributes from './attributes';

registerBlockType( 'woocommerce/checkout-additional-information-block', {
	attributes,
	icon: {
		src: (
			<Icon
				icon={ customPostType }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit: Edit,
	save: Save,
} );
