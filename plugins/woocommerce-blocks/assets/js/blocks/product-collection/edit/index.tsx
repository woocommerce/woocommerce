/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import type { ProductCollectionEditComponentProps } from '../types';
import ProductCollectionPlaceholder from './product-collection-placeholder';
import ProductCollectionContent from './product-collection-content';
import CollectionSelectionModal from './collection-selection-modal';
import './editor.scss';
import EditorProductPicker from './EditorProductPicker';

const Edit = ( props: ProductCollectionEditComponentProps ) => {
	const { clientId, attributes } = props;

	const [ isSelectionModalOpen, setIsSelectionModalOpen ] = useState( false );
	const hasInnerBlocks = useSelect(
		( select ) =>
			!! select( blockEditorStore ).getBlocks( clientId ).length,
		[ clientId ]
	);

	// TODO: Replace true with condition to check if product context is required
	// i.e. `usesReference` array should contain `product`
	// i.e. It has dependency on PR #49796
	// https://github.com/woocommerce/woocommerce/pull/49796
	const isProductContextRequired = true;
	const isProductContextSelected =
		( attributes.selectedReference?.id ?? null ) !== null;
	const isCollectionSelected = !! attributes.collection;
	const isShowEditorProductPicker =
		isProductContextRequired &&
		isCollectionSelected &&
		! isProductContextSelected &&
		hasInnerBlocks;

	/**
	 * Determine which component to render based on the state of the block.
	 */
	let Component;
	if ( isShowEditorProductPicker ) {
		Component = EditorProductPicker;
	} else if ( hasInnerBlocks ) {
		Component = ProductCollectionContent;
	} else {
		Component = ProductCollectionPlaceholder;
	}

	return (
		<>
			<Component
				{ ...props }
				openCollectionSelectionModal={ () =>
					setIsSelectionModalOpen( true )
				}
			/>
			{ isSelectionModalOpen && (
				<CollectionSelectionModal
					clientId={ clientId }
					attributes={ attributes }
					closePatternSelectionModal={ () =>
						setIsSelectionModalOpen( false )
					}
				/>
			) }
		</>
	);
};

export default Edit;
