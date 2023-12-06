/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { Modal } from '@wordpress/components';
import {
	store as blockEditorStore,
	__experimentalBlockPatternsList as BlockPatternsList,
} from '@wordpress/block-editor';
import { type BlockInstance, cloneBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { ProductCollectionQuery } from '../../types';

const blockName = 'woocommerce/product-collection';

const DisplayLayoutControl = ( props: {
	clientId: string;
	query: ProductCollectionQuery;
	closePatternSelectionModal: () => void;
} ) => {
	const { clientId, query } = props;
	const { replaceBlock, selectBlock } = useDispatch( blockEditorStore );

	const transformBlock = ( block: BlockInstance ): BlockInstance => {
		const newInnerBlocks = block.innerBlocks.map( transformBlock );
		if ( block.name === blockName ) {
			const { perPage, offset, pages } = block.attributes.query;
			const newQuery = {
				...query,
				perPage,
				offset,
				pages,
			};
			return cloneBlock( block, { query: newQuery }, newInnerBlocks );
		}
		return cloneBlock( block, {}, newInnerBlocks );
	};

	const blockPatterns = useSelect(
		( select ) => {
			const { getBlockRootClientId, getPatternsByBlockTypes } =
				select( blockEditorStore );
			const rootClientId = getBlockRootClientId( clientId );
			return getPatternsByBlockTypes( blockName, rootClientId );
		},
		[ blockName, clientId ]
	);

	const onClickPattern = ( pattern, blocks: BlockInstance[] ) => {
		const newBlocks = blocks.map( transformBlock );

		replaceBlock( clientId, newBlocks );
		selectBlock( newBlocks[ 0 ].clientId );
	};

	return (
		<Modal
			overlayClassName="wc-blocks-product-collection__selection-modal"
			title={ __( 'Choose a pattern', 'woo-gutenberg-products-block' ) }
			onRequestClose={ props.closePatternSelectionModal }
			isFullScreen={ true }
		>
			<div className="wc-blocks-product-collection__selection-content">
				<BlockPatternsList
					blockPatterns={ blockPatterns }
					shownPatterns={ blockPatterns }
					onClickPattern={ onClickPattern }
				/>
			</div>
		</Modal>
	);
};

export default DisplayLayoutControl;
