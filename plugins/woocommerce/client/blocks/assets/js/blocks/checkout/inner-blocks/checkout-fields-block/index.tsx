/**
 * External dependencies
 */
import { Icon, column } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import './style.scss';
import metadata from './block.json';

registerBlockType( 'woocommerce/checkout-fields-block', {
	...metadata,
	icon: {
		src: (
			<Icon
				icon={ column }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit: Edit,
	save: Save,
} );
