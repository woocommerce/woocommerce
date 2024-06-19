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
