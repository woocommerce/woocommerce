/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { BlockIcon } from '@wordpress/block-editor';
import { BlockConfiguration } from '@wordpress/blocks';
import { box } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { initBlock } from '../../utils/init-blocks';
import blockConfiguration from './block.json';
import { Edit } from './edit';
import { SectionBlockAttributes } from './types';

const { name, ...metadata } =
	blockConfiguration as BlockConfiguration< SectionBlockAttributes >;

export { metadata, name };

export const settings: Partial< BlockConfiguration< SectionBlockAttributes > > =
	{
		example: {},
		icon: <BlockIcon icon={ box } />,
		edit: Edit,
	};

export function init() {
	return initBlock( { name, metadata, settings } );
}
