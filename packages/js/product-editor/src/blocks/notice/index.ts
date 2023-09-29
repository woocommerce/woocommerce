/**
 * External dependencies
 */
import { BlockConfiguration } from '@wordpress/blocks';
import { registerWooBlockType } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
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
	registerWooBlockType( { name, metadata, settings } );
}
