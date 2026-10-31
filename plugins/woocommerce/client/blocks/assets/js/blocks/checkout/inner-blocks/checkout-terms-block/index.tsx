/**
 * External dependencies
 */
import { Icon, customPostType } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import metadata from './block.json';
import './style.scss';

/**
 * Deprecated since 11.4.0: terms are settings on the Actions block. Existing checkouts keep this block, and its
 * settings offer a switch to the Actions block terms.
 */
registerBlockType( metadata, {
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
