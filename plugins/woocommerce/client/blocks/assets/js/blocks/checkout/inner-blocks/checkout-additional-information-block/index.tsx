/**
 * External dependencies
 */
import { Icon, customPostType } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import attributes from './attributes';
import metadata from './block.json';

registerBlockType( 'woocommerce/checkout-additional-information-block', {
	...metadata,
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
