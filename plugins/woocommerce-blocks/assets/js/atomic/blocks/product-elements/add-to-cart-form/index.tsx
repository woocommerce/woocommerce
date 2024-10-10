/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { AddToCartFormBlockSettings } from './settings';
import './style.scss';
import './editor.scss';

// @ts-expect-error: `metadata` currently does not have a type definition in WordPress core.
registerBlockType( metadata.name, AddToCartFormBlockSettings );
