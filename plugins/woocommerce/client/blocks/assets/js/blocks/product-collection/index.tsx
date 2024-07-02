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
import icon from './icon';
import registerProductSummaryVariation from './variations/elements/product-summary';
import registerProductTitleVariation from './variations/elements/product-title';
import registerCollections from './collections';
import { addProductCollectionToQueryPaginationParentOrAncestor } from './utils';

registerBlockType( metadata, {
	icon,
	edit,
	save,
} );
registerProductSummaryVariation();
registerProductTitleVariation();
registerCollections();
addProductCollectionToQueryPaginationParentOrAncestor();
