/**
 * External dependencies
 */
import { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { registerProductEditorBlockType } from '../../../utils';

/**
 * Internal dependencies
 */
import blockConfiguration from './block.json';
import { Edit } from './edit';
import { NoticeBlockAttributes } from './types';

const { name, ...metadata } =
	blockConfiguration as BlockConfiguration< NoticeBlockAttributes >;

export { metadata, name };

export const settings = {
	example: {},
	edit: Edit,
};

export const init = () =>
	registerProductEditorBlockType( {
		name,
		metadata: metadata as never,
		settings: settings as never,
	} );
