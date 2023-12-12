/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	store as blockEditorStore,
	useBlockProps,
} from '@wordpress/block-editor';
import { Placeholder, Button } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	// @ts-expect-error Type definitions for this function are missing in Guteberg
	store as blocksStore,
	createBlock,
	// @ts-expect-error Type definitions for this function are missing in Guteberg
	createBlocksFromInnerBlocksTemplate,
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import type {
	ProductCollectionEditComponentProps,
	ProductCollectionAttributes,
} from '../types';
import { getDefaultProductCollection } from '../constants';
import Icon from '../icon';
import blockJson from '../block.json';
import productCatalog from '../collections/product-catalog';

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
		return attributes.collection || productCatalog.name;
	}

	// Otherwise it should be the first available choice. We control collections
	// so there's always at least one available.
	return blockCollections.length ? blockCollections[ 0 ].name : '';
};

const ProductCollectionPlaceholder = (
	props: ProductCollectionEditComponentProps
) => {
	const blockProps = useBlockProps();
	const { clientId, attributes } = props;
	// @ts-expect-error Type definitions for this function are missing
	// https://github.com/DefinitelyTyped/DefinitelyTyped/blob/master/types/wordpress__blocks/store/actions.d.ts
	const { replaceBlock } = useDispatch( blockEditorStore );

	// Get Collections
	const blockCollections = [
		productCatalog,
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

	const applyCollection = ( chosenCollectionName: string ) => {
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
		<div { ...blockProps }>
			<Placeholder
				className="wc-blocks-product-collection__placeholder"
				icon={ Icon }
				label={ __(
					'Product Collection',
					'woo-gutenberg-products-block'
				) }
				instructions={ __(
					'Choose a product collection to display, or create your own.',
					'woo-gutenberg-products-block'
				) }
			>
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
								active={ defaultChosenCollection === name }
								key={ name }
								title={ title }
								description={ description }
								icon={ icon }
								onClick={ () => applyCollection( name ) }
							/>
						)
					) }
				</div>
			</Placeholder>
		</div>
	);
};

export default ProductCollectionPlaceholder;
