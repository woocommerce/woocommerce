/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { getSetting } from '@woocommerce/settings';
import { Icon, tag } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { Edit } from './edit';
import './editor.scss';

/**
 * Register and run the "Products by Tag" block.
 */
registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ tag }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	attributes: {
		...metadata.attributes,
		columns: {
			type: 'number',
			default: getSetting( 'defaultColumns', 3 ),
		},
		rows: {
			type: 'number',
			default: getSetting( 'defaultRows', 3 ),
		},
		tags: {
			type: 'array',
			default: [],
		},
		stockStatus: {
			type: 'array',
			default: Object.keys( getSetting( 'stockStatusOptions', [] ) ),
		},
	},

	edit: Edit,

	save: () => {
		return null;
	},
} );
