/**
 * External dependencies
 */
import { Icon, button } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import attributes from './attributes';
import { Edit, Save } from './edit';
import './style.scss';
import metadata from './block.json';

const blockConfig: BlockConfiguration = {
	icon: {
		src: (
			<Icon
				icon={ button }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	attributes: { ...attributes, ...metadata.attributes },
	save: Save,
	edit: Edit,
};

registerBlockType( 'woocommerce/checkout-actions-block', blockConfig );
