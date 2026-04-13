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
import TaxonomyPicker, {
	getTaxonomySlugForCollection,
} from './taxonomy-picker';
import { useTracksLocation } from '../tracks-utils';
import { useRegisterEmailCollections } from '../hooks/use-register-email-collections';

const Edit = ( props: ProductCollectionEditComponentProps ) => {
	const { clientId, attributes, context } = props;
	const location = useGetLocation( context, clientId );
	const tracksLocation = useTracksLocation( context.templateSlug );

	// Register email-only collections when in email editor
	useRegisterEmailCollections();

	const [ isSelectionModalOpen, setIsSelectionModalOpen ] = useState( false );

	// Track if the collection-specific picker is active (Hand-Picked or Taxonomy).
	// This allows multi-select before clicking "Done".
	// Only one picker can be active at a time since collections are mutually exclusive.
	const [ isPickerActive, setIsPickerActive ] = useState( false );

	const isHandPickedCollection =
		attributes.collection === CoreCollectionNames.HAND_PICKED;
	const hasHandPickedProducts =
		( attributes.query?.woocommerceHandPickedProducts?.length ?? 0 ) > 0;

	const isTaxonomyCollection =
		attributes.collection === CoreCollectionNames.BY_CATEGORY ||
		attributes.collection === CoreCollectionNames.BY_TAG ||
		attributes.collection === CoreCollectionNames.BY_BRAND;

	const taxonomySlug = getTaxonomySlugForCollection( attributes.collection );
	const hasSelectedTerms = taxonomySlug
		? ( attributes.query?.taxQuery?.[ taxonomySlug ]?.length ?? 0 ) > 0
		: false;

	// Activate the picker when a collection needs initial selection
	useEffect( () => {
		if ( isHandPickedCollection && ! hasHandPickedProducts ) {
			setIsPickerActive( true );
		} else if ( isTaxonomyCollection && ! hasSelectedTerms ) {
			setIsPickerActive( true );
		}
	}, [
		isHandPickedCollection,
		hasHandPickedProducts,
		isTaxonomyCollection,
		hasSelectedTerms,
	] );

	const dismissPicker = () => setIsPickerActive( false );

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
		// Show the collection-specific picker if it's active (local state).
		// This allows multi-select before clicking "Done".
		// The inspector controls are inside ProductCollectionContent,
		// so they're automatically hidden while the picker is shown.
		if ( isPickerActive ) {
			if ( isHandPickedCollection ) {
				return (
					<MultiProductPicker { ...props } onDone={ dismissPicker } />
				);
			}
			if ( isTaxonomyCollection ) {
				return <TaxonomyPicker { ...props } onDone={ dismissPicker } />;
			}
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
					<MultiProductPicker { ...props } onDone={ dismissPicker } />
				);
			case ProductCollectionUIStatesInEditor.TAXONOMY_PICKER:
				// This case is hit when no taxonomy terms are selected
				// and the picker was previously dismissed but terms were removed
				return <TaxonomyPicker { ...props } onDone={ dismissPicker } />;
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
