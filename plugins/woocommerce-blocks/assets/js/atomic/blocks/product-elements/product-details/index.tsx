/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import './style.scss';
import { ProductDetailsBlockSettings } from './settings';

// @ts-expect-error: `metadata` currently does not have a type definition in WordPress core
registerBlockType( metadata, ProductDetailsBlockSettings );
