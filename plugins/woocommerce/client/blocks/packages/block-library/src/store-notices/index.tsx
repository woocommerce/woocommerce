/**
 * External dependencies
 */
import { BlockConfiguration, registerBlockType } from '@wordpress/blocks';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import icon from './icon';

type Attributes = {
	align?: string;
};

registerBlockType( metadata as BlockConfiguration< Attributes >, {
	icon: {
		src: (
			<Icon
				icon={ icon }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit,
	save() {
		return null;
	},
} );
