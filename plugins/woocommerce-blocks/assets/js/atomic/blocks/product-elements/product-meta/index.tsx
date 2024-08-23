/**
 * Internal dependencies
 */
import metadata from './block.json';
import { ProductMetaBlockSettings } from './settings';

// @ts-expect-error: `metadata` currently does not have a type definition in WordPress core.
registerBlockType( metadata, ProductMetaBlockSettings );
