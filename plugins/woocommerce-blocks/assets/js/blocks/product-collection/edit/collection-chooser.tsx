/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Button, Tooltip } from '@wordpress/components';
import {
	BlockInstance,
	createBlock,
	// @ts-expect-error Type definitions for this function are missing in Guteberg
	createBlocksFromInnerBlocksTemplate,
	// @ts-expect-error Type definitions for this function are missing in Guteberg
	store as blocksStore,
	BlockVariation,
	BlockIcon,
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { type CollectionName, CoreCollectionNames } from '../types';
import blockJson from '../block.json';
import { getCollectionByName } from '../collections';
import { getDefaultProductCollection } from '../constants';

type CollectionButtonProps = {
	title: string;
	icon: BlockIcon | undefined;
	description: string | undefined;
	onClick: () => void;
};

export const applyCollection = (
	collectionName: CollectionName,
	clientId: string,
	replaceBlock: ( clientId: string, block: BlockInstance ) => void
) => {
	const collection = getCollectionByName( collectionName );

	if ( ! collection ) {
		return;
	}

	const newBlock =
		collection.name === CoreCollectionNames.PRODUCT_CATALOG
			? getDefaultProductCollection()
			: createBlock(
					blockJson.name,
					collection.attributes,
					createBlocksFromInnerBlocksTemplate(
						collection.innerBlocks
					)
			  );

	replaceBlock( clientId, newBlock );
};

const CollectionButton = ( {
	title,
	icon,
	description,
	onClick,
}: CollectionButtonProps ) => {
	return (
		<Tooltip text={ description } placement="top">
			<Button
				className="wc-blocks-product-collection__collection-button"
				onClick={ onClick }
			>
				<div className="wc-blocks-product-collection__collection-button-icon">
					{ icon }
				</div>
				<p className="wc-blocks-product-collection__collection-button-title">
					{ title }
				</p>
			</Button>
		</Tooltip>
	);
};

const CollectionChooser = ( props: {
	chosenCollection?: CollectionName | undefined;
	onCollectionClick: ( name: string ) => void;
} ) => {
	const { onCollectionClick } = props;

	// Get Collections
	const blockCollections = useSelect( ( select ) => {
		// @ts-expect-error Type definitions are missing
		// https://github.com/DefinitelyTyped/DefinitelyTyped/blob/master/types/wordpress__blocks/store/selectors.d.ts
		const { getBlockVariations } = select( blocksStore );
		return getBlockVariations( blockJson.name );
	}, [] ) as BlockVariation[];

	const productCatalog = useMemo(
		() =>
			blockCollections.find(
				( { name } ) => name === CoreCollectionNames.PRODUCT_CATALOG
			) as BlockVariation,
		[ blockCollections ]
	);

	return (
		<>
			<div className="wc-blocks-product-collection__collections-section">
				{ blockCollections
					.filter(
						( { name } ) =>
							name !== CoreCollectionNames.PRODUCT_CATALOG
					)
					.map( ( { name, title, icon, description } ) => (
						<CollectionButton
							key={ name }
							title={ title }
							description={ description }
							icon={ icon }
							onClick={ () => onCollectionClick( name ) }
						/>
					) ) }
			</div>
			<div className="wc-blocks-product-collection__collections-custom">
				<span>or</span>
				<Button
					className="wc-blocks-product-collection__collections-custom-button"
					onClick={ () => onCollectionClick( productCatalog.name ) }
				>
					create your own
				</Button>
			</div>
		</>
	);
};

export default CollectionChooser;
