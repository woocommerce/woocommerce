/**
 * External dependencies
 */
import { BlockConfiguration } from '@wordpress/blocks';
import { registerExperimentalBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import save from './save';
import icon from './icon';

const blockConfig: BlockConfiguration = {
	...metadata,
	icon,
	edit,
	save,
};

registerExperimentalBlockType( metadata.name, blockConfig );
