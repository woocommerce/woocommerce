/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { useGetLocation } from '@woocommerce/blocks/product-template/utils';
import { Spinner, Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	ProductCollectionEditComponentProps,
	ProductCollectionUIStatesInEditor,
} from '../types';
import ProductCollectionPlaceholder from './product-collection-placeholder';
import ProductCollectionContent from './product-collection-content';
import CollectionSelectionModal from './collection-selection-modal';
import './editor.scss';
import { useProductCollectionUIState } from '../utils';
import ProductPicker from './ProductPicker';

const Edit = ( props: ProductCollectionEditComponentProps ) => {
	const { clientId, attributes } = props;
	const location = useGetLocation( props.context, props.clientId );

	const [ isSelectionModalOpen, setIsSelectionModalOpen ] = useState( false );
	const hasInnerBlocks = useSelect(
		( select ) =>
			!! select( blockEditorStore ).getBlocks( clientId ).length,
		[ clientId ]
	);

	const { productCollectionUIStateInEditor, isLoading } =
		useProductCollectionUIState( {
			location,
			attributes,
			hasInnerBlocks,
			...( props.usesReference && {
				usesReference: props.usesReference,
			} ),
		} );

	// Show spinner while calculating Editor UI state.
	if ( isLoading ) {
		return (
			<Flex justify="center" align="center">
				<Spinner />
			</Flex>
		);
	}

	/**
	 * Component to render based on the UI state.
	 */
	let Component,
		isUsingReferencePreviewMode = false;
	switch ( productCollectionUIStateInEditor ) {
		case ProductCollectionUIStatesInEditor.COLLECTION_PICKER:
			Component = ProductCollectionPlaceholder;
			break;
		case ProductCollectionUIStatesInEditor.PRODUCT_REFERENCE_PICKER:
		case ProductCollectionUIStatesInEditor.DELETED_PRODUCT_REFERENCE:
			Component = ProductPicker;
			break;
		case ProductCollectionUIStatesInEditor.VALID:
			Component = ProductCollectionContent;
			break;
		case ProductCollectionUIStatesInEditor.VALID_WITH_PREVIEW:
			Component = ProductCollectionContent;
			isUsingReferencePreviewMode = true;
			break;
		default:
			// By default showing collection chooser.
			Component = ProductCollectionPlaceholder;
	}

	return (
		<>
			<Component
				{ ...props }
				openCollectionSelectionModal={ () =>
					setIsSelectionModalOpen( true )
				}
				isUsingReferencePreviewMode={ isUsingReferencePreviewMode }
				location={ location }
				usesReference={ props.usesReference }
				isDeletedProductReference={
					productCollectionUIStateInEditor ===
					ProductCollectionUIStatesInEditor.DELETED_PRODUCT_REFERENCE
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
