/**
 * External dependencies
 */
import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import deprecated from './deprecated';

registerBlockType( metadata as BlockConfiguration, {
	icon: 'tag',
	edit: Edit,
	save: Save,
	deprecated,
} );
