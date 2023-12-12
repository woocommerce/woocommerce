/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import type { ProductCollectionAttributes } from '../types';
import ProductCollectionPlaceholder from './product-collection-placeholder';
import ProductCollectionContent from './product-collection-content';
import PatternSelectionModal from './collection-selection-modal';
import './editor.scss';

const Edit = ( props: BlockEditProps< ProductCollectionAttributes > ) => {
	const { clientId, attributes } = props;

	const [ isPatternSelectionModalOpen, setIsPatternSelectionModalOpen ] =
		useState( false );
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
				openPatternSelectionModal={ () =>
					setIsPatternSelectionModalOpen( true )
				}
			/>
			{ isPatternSelectionModalOpen && (
				<PatternSelectionModal
					clientId={ clientId }
					attributes={ attributes }
					closePatternSelectionModal={ () =>
						setIsPatternSelectionModalOpen( false )
					}
				/>
			) }
		</>
	);
};

export default Edit;
