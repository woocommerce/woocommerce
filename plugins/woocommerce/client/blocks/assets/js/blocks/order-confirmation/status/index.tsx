/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';
// eslint-disable-next-line import/named
import { Icon, info } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit, { Save as save } from './edit';
import { attributes } from './attributes';
import { registerOrderStatusBlocks } from './inner-blocks';
import '../../cart-checkout-shared/view-switcher';

registerOrderStatusBlocks();

registerBlockType( metadata.name, {
	...metadata,
	icon: {
		src: (
			<Icon
				icon={ info }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	attributes: {
		...metadata.attributes,
		...attributes,
	},
	edit,
	save,
} as unknown as BlockConfiguration );
