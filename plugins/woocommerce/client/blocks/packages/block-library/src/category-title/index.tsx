/**
 * External dependencies
 */
import { heading } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import initBlock from '../utils/init-block';
import metadata from './block.json';
import edit from './edit';

const { name } = metadata;

export { metadata, name };

export const settings = {
	edit: edit,
	icon: heading,
	save: () => null,
};

initBlock( { metadata, settings } );
