/**
 * External dependencies
 */
import { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { initBlock } from '../../utils/init-block';
import blockConfiguration from './block.json';
import { Edit, NoticeBlockAttributes } from './edit';

const { name, ...metadata } =
	blockConfiguration as BlockConfiguration< NoticeBlockAttributes >;

export { metadata, name };

export const settings: Partial< BlockConfiguration< NoticeBlockAttributes > > =
	{
		example: {},
		edit: Edit,
	};

export function init() {
	initBlock( { name, metadata, settings } );
}
