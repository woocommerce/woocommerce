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
	ProductCollectionContentProps,
	ProductCollectionEditComponentProps,
	ProductCollectionUIStatesInEditor,
} from '../types';
import ProductCollectionPlaceholder from './product-collection-placeholder';
import ProductCollectionContent from './product-collection-content';
import CollectionSelectionModal from './collection-selection-modal';
import { useProductCollectionUIState } from '../utils';
import ProductPicker from './ProductPicker';
import { useTracksLocation } from '../tracks-utils';

const Edit = ( props: ProductCollectionEditComponentProps ) => {
	const { clientId, attributes, context } = props;
	const location = useGetLocation( context, clientId );
	const tracksLocation = useTracksLocation( context.templateSlug );

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
			usesReference: props.usesReference,
		} );

	// Show spinner while calculating Editor UI state.
	if ( isLoading ) {
		return (
			<Flex justify="center" align="center">
				<Spinner />
			</Flex>
		);
	}

	const productCollectionContentProps: ProductCollectionContentProps = {
		...props,
		openCollectionSelectionModal: () => setIsSelectionModalOpen( true ),
		location,
		isUsingReferencePreviewMode:
			productCollectionUIStateInEditor ===
			ProductCollectionUIStatesInEditor.VALID_WITH_PREVIEW,
	};

	const renderComponent = () => {
		switch ( productCollectionUIStateInEditor ) {
			case ProductCollectionUIStatesInEditor.COLLECTION_PICKER:
				return (
					<ProductCollectionPlaceholder
						{ ...props }
						tracksLocation={ tracksLocation }
					/>
				);
			case ProductCollectionUIStatesInEditor.PRODUCT_REFERENCE_PICKER:
				return (
					<ProductPicker
						{ ...props }
						isDeletedProductReference={ false }
					/>
				);
			case ProductCollectionUIStatesInEditor.DELETED_PRODUCT_REFERENCE:
				return (
					<ProductPicker
						{ ...props }
						isDeletedProductReference={ true }
					/>
				);
			case ProductCollectionUIStatesInEditor.VALID:
			case ProductCollectionUIStatesInEditor.VALID_WITH_PREVIEW:
				return (
					<ProductCollectionContent
						{ ...productCollectionContentProps }
					/>
				);
			default:
				return (
					<ProductCollectionPlaceholder
						{ ...props }
						tracksLocation={ tracksLocation }
					/>
				);
		}
	};

	return (
		<>
			{ renderComponent() }
			{ isSelectionModalOpen && (
				<CollectionSelectionModal
					clientId={ clientId }
					attributes={ attributes }
					tracksLocation={ tracksLocation }
					closePatternSelectionModal={ () =>
						setIsSelectionModalOpen( false )
					}
				/>
			) }
		</>
	);
};

export default Edit;
