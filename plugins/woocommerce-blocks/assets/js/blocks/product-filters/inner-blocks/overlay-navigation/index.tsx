/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, button } from '@wordpress/icons';
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { Edit } from './edit';
import { Save } from './save';
import { blockVariations } from './block-variations';
import './style.scss';

if ( isExperimentalBlocksEnabled() ) {
	registerBlockType( metadata, {
		edit: Edit,
		save: Save,
		icon: <Icon icon={ button } />,
		variations: blockVariations,
	} );
}
