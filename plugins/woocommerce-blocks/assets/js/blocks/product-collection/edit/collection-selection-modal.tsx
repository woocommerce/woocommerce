/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { Modal, Button } from '@wordpress/components';
import {
	// @ts-expect-error Type definitions for this function are missing in Guteberg
	store as blocksStore,
	createBlock,
	// @ts-expect-error Type definitions for this function are missing in Guteberg
	createBlocksFromInnerBlocksTemplate,
} from '@wordpress/blocks';
/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import type { ProductCollectionAttributes } from '../types';
import { getDefaultProductCollection } from '../constants';
import blockJson from '../block.json';
import { collections } from '../collections';

type CollectionButtonProps = {
	active: boolean;
	title: string;
	icon: string;
	description: string;
	onClick: () => void;
};

const CollectionButton = ( {
	active,
	title,
	icon,
	description,
	onClick,
}: CollectionButtonProps ) => {
	const variant = active ? 'primary' : 'secondary';

	return (
		<Button
			className="wc-blocks-product-collection__collection-button"
			variant={ variant }
			onClick={ onClick }
		>
			<div className="wc-blocks-product-collection__collection-button-icon">
				{ icon }
			</div>
			<div className="wc-blocks-product-collection__collection-button-text">
				<p className="wc-blocks-product-collection__collection-button-title">
					{ title }
				</p>
				<p className="wc-blocks-product-collection__collection-button-description">
					{ description }
				</p>
			</div>
		</Button>
	);
};

const getDefaultChosenCollection = (
	attributes: ProductCollectionAttributes,
	// @ts-expect-error Type definitions are missing
	// https://github.com/DefinitelyTyped/DefinitelyTyped/blob/master/types/wordpress__blocks/store/selectors.d.ts
	blockCollections
) => {
	// If `attributes.query` is truthy, that means Product Collection was already
	// configured. So it's either a collection or we need to return defaultQuery
	// collection name;
	if ( attributes.query ) {
		return attributes.collection || collections.productCatalog.name;
	}

	// Otherwise it should be the first available choice. We control collections
	// so there's always at least one available.
	return blockCollections.length ? blockCollections[ 0 ].name : '';
};

const PatternSelectionModal = ( props: {
	clientId: string;
	attributes: ProductCollectionAttributes;
	closePatternSelectionModal: () => void;
} ) => {
	const { clientId, attributes } = props;
	// @ts-expect-error Type definitions for this function are missing
	// https://github.com/DefinitelyTyped/DefinitelyTyped/blob/master/types/wordpress__blocks/store/actions.d.ts
	const { replaceBlock } = useDispatch( blockEditorStore );

	// Get Collections
	const blockCollections = [
		collections.productCatalog,
		...useSelect( ( select ) => {
			// @ts-expect-error Type definitions are missing
			// https://github.com/DefinitelyTyped/DefinitelyTyped/blob/master/types/wordpress__blocks/store/selectors.d.ts
			const { getBlockVariations } = select( blocksStore );
			return getBlockVariations( blockJson.name );
		}, [] ),
	];

	// Prepare Collections
	const defaultChosenCollection = getDefaultChosenCollection(
		attributes,
		blockCollections
	);

	const [ chosenCollectionName, selectCollectionName ] = useState(
		defaultChosenCollection
	);

	const applyCollection = () => {
		// Case 1: Merchant has chosen Default Query. In that case we create defaultProductCollection
		if (
			chosenCollectionName ===
			'woocommerce-blocks/product-collection/default-query'
		) {
			const defaultProductCollection = getDefaultProductCollection();
			replaceBlock( clientId, defaultProductCollection );
			return;
		}

		// Case 2: Merchant has chosen another Collection
		const chosenCollection = blockCollections.find(
			( { name }: { name: string } ) => name === chosenCollectionName
		);

		const newBlock = createBlock(
			blockJson.name,
			chosenCollection.attributes,
			createBlocksFromInnerBlocksTemplate( chosenCollection.innerBlocks )
		);

		replaceBlock( clientId, newBlock );
	};

	return (
		<Modal
			overlayClassName="wc-blocks-product-collection__modal"
			title={ __(
				'Choose a collection',
				'woo-gutenberg-products-block'
			) }
			onRequestClose={ props.closePatternSelectionModal }
			// @ts-expect-error Type definitions are missing in the version we are using i.e. 19.1.5,
			size={ 'large' }
		>
			<div className="wc-blocks-product-collection__content">
				<p className="wc-blocks-product-collection__subtitle">
					{ __(
						"Pick what products are shown. Don't worry, you can switch and tweak this collection any time.",
						'woo-gutenberg-products-block'
					) }
				</p>
				<div className="wc-blocks-product-collection__collections-section">
					{ blockCollections.map(
						( { name, title, icon, description } ) => (
							<CollectionButton
								active={ chosenCollectionName === name }
								key={ name }
								title={ title }
								description={ description }
								icon={ icon }
								onClick={ () => selectCollectionName( name ) }
							/>
						)
					) }
				</div>
				<div className="wc-blocks-product-collection__footer">
					<Button
						variant="tertiary"
						onClick={ props.closePatternSelectionModal }
					>
						{ __( 'Cancel', 'woo-gutenberg-products-block' ) }
					</Button>
					<Button variant="primary" onClick={ applyCollection }>
						{ __( 'Continue', 'woo-gutenberg-products-block' ) }
					</Button>
				</div>
			</div>
		</Modal>
	);
};

export default PatternSelectionModal;
