/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit, { Save as save } from './edit';
import { blockName, attributes } from './attributes';
import './inner-blocks';
import { metadata } from './metadata';

registerBlockType( blockName, {
	...metadata,
	attributes,
	edit,
	save,
} );
