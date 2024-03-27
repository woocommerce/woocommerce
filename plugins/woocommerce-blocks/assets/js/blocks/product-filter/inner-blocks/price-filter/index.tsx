/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, currencyDollar } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './style.scss';
import metadata from './block.json';
import Edit from './edit';

registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ currencyDollar }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit: Edit,
} );
