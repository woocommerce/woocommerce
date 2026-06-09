/**
 * External dependencies
 */
import { type BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import initBlock from '../utils/init-block';
import metadata from './block.json';
import edit, { type CategoryTitleAttributes } from './edit';

const { name } = metadata;

export { metadata, name };

export const settings: Partial<
	BlockConfiguration< CategoryTitleAttributes >
> = {
	edit: edit as unknown as BlockConfiguration< CategoryTitleAttributes >[ 'edit' ],
	icon: 'heading',
	save: () => null,
};

export const init = () => initBlock( { name, metadata, settings } );
