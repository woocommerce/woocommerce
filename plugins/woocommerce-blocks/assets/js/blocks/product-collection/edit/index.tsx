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
import ProductCollectionContent, {
	ProductCollectionPreviewModeContext,
} from './product-collection-content';
import CollectionSelectionModal from './collection-selection-modal';
import './editor.scss';

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
		<ProductCollectionPreviewModeContext.Provider
			value={ {
				isPreview: true,
				previewMessage:
					'This preview message should be visible in Product Template',
			} }
		>
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
		</ProductCollectionPreviewModeContext.Provider>
	);
};

export default Edit;
