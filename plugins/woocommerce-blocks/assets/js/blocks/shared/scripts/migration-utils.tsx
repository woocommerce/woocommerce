/**
 * External dependencies
 */
import { getSettingWithCoercion } from '@woocommerce/settings';
import { type BlockInstance } from '@wordpress/blocks';
import { select } from '@wordpress/data';
import { isBoolean } from '@woocommerce/types';

type GetBlocksClientIds = ( blocks: BlockInstance[] ) => string[];
export type IsBlockType = ( block: BlockInstance ) => boolean;
export type TransformBlock = (
	block: BlockInstance,
	innerBlock: BlockInstance[]
) => BlockInstance;
export type ProductGridLayoutTypes = 'flex' | 'list';
export type PostTemplateLayoutTypes = 'grid' | 'default';

export type ProductGridLayout = {
	type: ProductGridLayoutTypes;
	columns: number;
};

export type PostTemplateLayout = {
	type: PostTemplateLayoutTypes;
	columnCount: number;
};

const isProductsBlock: IsBlockType = ( block ) =>
	block.name === 'core/query' &&
	block.attributes.namespace === 'woocommerce/product-query';

const isProductCollectionBlock: IsBlockType = ( block ) =>
	block.name === 'woocommerce/product-collection';

const getBlockClientIdsByPredicate = (
	blocks: BlockInstance[],
	predicate: ( block: BlockInstance ) => boolean
): string[] => {
	let clientIds: string[] = [];
	blocks.forEach( ( block ) => {
		if ( predicate( block ) ) {
			clientIds = [ ...clientIds, block.clientId ];
		}
		clientIds = [
			...clientIds,
			...getBlockClientIdsByPredicate( block.innerBlocks, predicate ),
		];
	} );
	return clientIds;
};

const getProductsBlockClientIds: GetBlocksClientIds = ( blocks ) =>
	getBlockClientIdsByPredicate( blocks, isProductsBlock );

const getProductCollectionBlockClientIds: GetBlocksClientIds = ( blocks ) =>
	getBlockClientIdsByPredicate( blocks, isProductCollectionBlock );

const checkIfBlockCanBeInserted = (
	clientId: string,
	blockToBeInserted: string
) => {
	// We need to duplicate checks that are happening within replaceBlocks method
	// as replacement is initially blocked and there's no information returned
	// that would determine if replacement happened or not.
	// https://github.com/WordPress/gutenberg/issues/46740
	const rootClientId =
		select( 'core/block-editor' ).getBlockRootClientId( clientId ) ||
		undefined;
	return select( 'core/block-editor' ).canInsertBlockType(
		blockToBeInserted,
		rootClientId
	);
};

const postTemplateHasSupportForGridView = getSettingWithCoercion(
	'postTemplateHasSupportForGridView',
	false,
	isBoolean
);

export {
	getProductsBlockClientIds,
	getProductCollectionBlockClientIds,
	checkIfBlockCanBeInserted,
	postTemplateHasSupportForGridView,
};
