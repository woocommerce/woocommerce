/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import save from './save';
import icon from './icon';
import registerProductSummaryVariation from './variations/elements/product-summary';
import registerProductTitleVariation from './variations/elements/product-title';
import registerCollections from './collections';
import { addProductCollectionToQueryPaginationParentOrAncestor } from './utils';
import { lazyEdit } from '../shared/lazy-edit';

const edit = lazyEdit( () =>
	import( /* webpackChunkName: "product-collection-edit" */ './edit' )
);

registerBlockType( metadata, {
	icon,
	edit,
	save,
} );
registerProductSummaryVariation();
registerProductTitleVariation();
registerCollections();
addProductCollectionToQueryPaginationParentOrAncestor();
