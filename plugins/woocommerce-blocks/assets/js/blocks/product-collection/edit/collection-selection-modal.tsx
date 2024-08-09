/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { Modal, Button } from '@wordpress/components';
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import CollectionChooser, { applyCollection } from './collection-chooser';
import type { ProductCollectionAttributes } from '../types';

const PatternSelectionModal = ( props: {
	clientId: string;
	attributes: ProductCollectionAttributes;
	closePatternSelectionModal: () => void;
} ) => {
	const { clientId, attributes } = props;
	// @ts-expect-error Type definitions for this function are missing
	// https://github.com/DefinitelyTyped/DefinitelyTyped/blob/master/types/wordpress__blocks/store/actions.d.ts
	const { replaceBlock } = useDispatch( blockEditorStore );

	const [ chosenCollection, selectCollectionName ] = useState(
		attributes.collection
	);

	const onContinueClick = () => {
		if ( chosenCollection ) {
			applyCollection( chosenCollection, clientId, replaceBlock );
		}
	};

	return (
		<Modal
			overlayClassName="wc-blocks-product-collection__modal"
			title={ __( 'What products do you want to show?', 'woocommerce' ) }
			onRequestClose={ props.closePatternSelectionModal }
			// @ts-expect-error Type definitions are missing in the version we are using i.e. 19.1.5,
			size={ 'large' }
		>
			<div className="wc-blocks-product-collection__content">
				<CollectionChooser
					chosenCollection={ chosenCollection }
					onCollectionClick={ selectCollectionName }
				/>
				<div className="wc-blocks-product-collection__footer">
					<Button
						variant="tertiary"
						onClick={ props.closePatternSelectionModal }
					>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Button variant="primary" onClick={ onContinueClick }>
						{ __( 'Continue', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		</Modal>
	);
};

export default PatternSelectionModal;
