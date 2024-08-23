/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { ProductRatingBlockSettings } from './settings';

// @ts-expect-error: `metadata` currently does not have a type definition in WordPress core.
registerBlockType( metadata, ProductRatingBlockSettings );
