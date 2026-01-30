/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { useState, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { useGetLocation } from '@woocommerce/blocks/product-template/utils';
import { Spinner, Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	CoreCollectionNames,
	ProductCollectionContentProps,
	ProductCollectionEditComponentProps,
	ProductCollectionUIStatesInEditor,
} from '../types';
import ProductCollectionPlaceholder from './product-collection-placeholder';
import ProductCollectionContent from './product-collection-content';
import CollectionSelectionModal from './collection-selection-modal';
import { useProductCollectionUIState } from '../utils';
import SingleProductPicker from './single-product-picker';
import MultiProductPicker from './multi-product-picker';
import { useTracksLocation } from '../tracks-utils';
import { useRegisterEmailCollections } from '../hooks/use-register-email-collections';

const Edit = ( props: ProductCollectionEditComponentProps ) => {
	const { clientId, attributes, context } = props;
	const location = useGetLocation( context, clientId );
	const tracksLocation = useTracksLocation( context.templateSlug );

	// Register email-only collections when in email editor
	useRegisterEmailCollections();

	const [ isSelectionModalOpen, setIsSelectionModalOpen ] = useState( false );

	// Track if the hand-picked products picker is active.
	// This allows multi-select before clicking "Done".
	const [ isHandPickedPickerActive, setIsHandPickedPickerActive ] =
		useState( false );

	const isHandPickedCollection =
		attributes.collection === CoreCollectionNames.HAND_PICKED;
	const hasHandPickedProducts =
		( attributes.query?.woocommerceHandPickedProducts?.length ?? 0 ) > 0;

	// Activate the picker when Hand-Picked collection is selected with no products
	useEffect( () => {
		if ( isHandPickedCollection && ! hasHandPickedProducts ) {
			setIsHandPickedPickerActive( true );
		}
	}, [ isHandPickedCollection, hasHandPickedProducts ] );

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
		// Show the hand-picked products picker if it's active (local state).
		// This allows multi-select before clicking "Done".
		// The inspector controls (HandPickedProductsControlField) are inside
		// ProductCollectionContent, so they're automatically hidden while
		// the picker is shown.
		if ( isHandPickedCollection && isHandPickedPickerActive ) {
			return (
				<MultiProductPicker
					{ ...props }
					onDone={ () => setIsHandPickedPickerActive( false ) }
				/>
			);
		}

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
					<SingleProductPicker
						{ ...props }
						isDeletedProductReference={ false }
					/>
				);
			case ProductCollectionUIStatesInEditor.DELETED_PRODUCT_REFERENCE:
				return (
					<SingleProductPicker
						{ ...props }
						isDeletedProductReference={ true }
					/>
				);
			case ProductCollectionUIStatesInEditor.HAND_PICKED_PRODUCTS_PICKER:
				// This case is hit when no products are selected
				// and the picker was previously dismissed but products were removed
				return (
					<MultiProductPicker
						{ ...props }
						onDone={ () => setIsHandPickedPickerActive( false ) }
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
