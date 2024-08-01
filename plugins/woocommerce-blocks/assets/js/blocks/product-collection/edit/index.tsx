/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { useGetLocation } from '@woocommerce/blocks/product-template/utils';

/**
 * Internal dependencies
 */
import {
	ProductCollectionUIStatesInEditor,
	type ProductCollectionEditComponentProps,
} from '../types';
import ProductCollectionPlaceholder from './product-collection-placeholder';
import ProductCollectionContent from './product-collection-content';
import CollectionSelectionModal from './collection-selection-modal';
import './editor.scss';
import { getProductCollectionUIStateInEditor } from '../utils';
import EditorProductPicker from './EditorProductPicker';

const Edit = ( props: ProductCollectionEditComponentProps ) => {
	const { clientId, attributes } = props;
	const location = useGetLocation( props.context, props.clientId );

	const [ isSelectionModalOpen, setIsSelectionModalOpen ] = useState( false );
	const hasInnerBlocks = useSelect(
		( select ) =>
			!! select( blockEditorStore ).getBlocks( clientId ).length,
		[ clientId ]
	);

	const productCollectionUIStateInEditor =
		getProductCollectionUIStateInEditor( {
			location,
			attributes: props.attributes,
			hasInnerBlocks,
			usesReference: props.usesReference,
		} );

	/**
	 * Component to render based on the UI state.
	 */
	let Component;
	switch ( productCollectionUIStateInEditor ) {
		case ProductCollectionUIStatesInEditor.COLLECTION_CHOOSER:
			Component = ProductCollectionPlaceholder;
			break;
		case ProductCollectionUIStatesInEditor.PRODUCT_CONTEXT_PICKER:
			Component = EditorProductPicker;
			break;
		case ProductCollectionUIStatesInEditor.VALID:
			Component = ProductCollectionContent;
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
				productCollectionUIStateInEditor={
					productCollectionUIStateInEditor
				}
				location={ location }
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
