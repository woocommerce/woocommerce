/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { registerWooBlockType } from '../../utils';
import blockConfiguration from './block.json';
import { Edit } from './edit';
import { SectionBlockAttributes } from './types';

const { name, ...metadata } =
	blockConfiguration as BlockConfiguration< SectionBlockAttributes >;

export { metadata, name };

export const settings: Partial< BlockConfiguration< SectionBlockAttributes > > =
	{
		example: {},
		edit: Edit,
	};

export function init() {
	return registerWooBlockType( { name, metadata, settings } );
}
