/**
 * External dependencies
 */
import { BlockConfiguration, registerBlockType } from '@wordpress/blocks';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit, { Attributes } from './edit';
import { queryPaginationIcon } from './icon';

registerBlockType( metadata as BlockConfiguration< Attributes >, {
	icon: {
		src: (
			<Icon
				icon={ queryPaginationIcon }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit,
	save() {
		return null;
	},
} );
