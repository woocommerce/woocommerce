/**
 * External dependencies
 */
import { BlockConfiguration, registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Edit from './edit';
import Save from './save';
import metadata from './block.json';

type Attributes = {
	emailType?: string;
	postId?: number;
};

registerBlockType( metadata as BlockConfiguration< Attributes >, {
	edit: Edit,
	save: Save,
} );
