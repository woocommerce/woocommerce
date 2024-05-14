/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import save from './save';
import deprecated from './deprecated';
import icon from './icon';
import registerProductSummaryVariation from './variations/elements/product-summary';
import registerProductTitleVariation from './variations/elements/product-title';
import registerCollections from './collections';
import { addProductCollectionBlockToParentOfPaginationBlock } from './utils';

registerBlockType( metadata, {
	icon,
	edit,
	save,
	deprecated,
} );
registerProductSummaryVariation();
registerProductTitleVariation();
registerCollections();
addProductCollectionBlockToParentOfPaginationBlock();
