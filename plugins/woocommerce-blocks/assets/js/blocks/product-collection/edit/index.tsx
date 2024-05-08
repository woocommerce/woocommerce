/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { createContext, useContext, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import type {
	PreviewState,
	ProductCollectionEditComponentProps,
} from '../types';
import ProductCollectionPlaceholder from './product-collection-placeholder';
import ProductCollectionContent from './product-collection-content';
import CollectionSelectionModal from './collection-selection-modal';
import './editor.scss';

export const ProductCollectionPreviewModeContext = createContext<
	PreviewState | undefined
>( undefined );

// Hook to use values of ProductCollectionContext in child components.
export const usePreviewStateContext = () => {
	const context = useContext( ProductCollectionPreviewModeContext );
	if ( ! context ) {
		console.log(
			'usePreviewStateContext must be used within a Product Collection block'
		);
		return;
	}
	return context;
};

const Edit = ( props: ProductCollectionEditComponentProps ) => {
	const { clientId, attributes } = props;

	const [ isSelectionModalOpen, setIsSelectionModalOpen ] = useState( false );
	const hasInnerBlocks = useSelect(
		( select ) =>
			!! select( blockEditorStore ).getBlocks( clientId ).length,
		[ clientId ]
	);

	const Component = hasInnerBlocks
		? ProductCollectionContent
		: ProductCollectionPlaceholder;

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
