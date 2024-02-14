/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { Button } from '@wordpress/components';
import {
	BlockInstance,
	createBlock,
	// @ts-expect-error Type definitions for this function are missing in Guteberg
	createBlocksFromInnerBlocksTemplate,
	// @ts-expect-error Type definitions for this function are missing in Guteberg
	store as blocksStore,
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { type CollectionName, CoreCollectionNames } from '../types';
import blockJson from '../block.json';
import { getCollectionByName } from '../collections';
import { getDefaultProductCollection } from '../constants';

type CollectionButtonProps = {
	active?: boolean;
	title: string;
	icon: string;
	description: string;
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
	active = false,
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

const CollectionChooser = ( props: {
	chosenCollection?: CollectionName | undefined;
	onCollectionClick: ( name: string ) => void;
} ) => {
	const { chosenCollection, onCollectionClick } = props;

	// Get Collections
	const blockCollections = [
		...useSelect( ( select ) => {
			// @ts-expect-error Type definitions are missing
			// https://github.com/DefinitelyTyped/DefinitelyTyped/blob/master/types/wordpress__blocks/store/selectors.d.ts
			const { getBlockVariations } = select( blocksStore );
			return getBlockVariations( blockJson.name );
		}, [] ),
	];

	return (
		<div className="wc-blocks-product-collection__collections-section">
			{ blockCollections.map( ( { name, title, icon, description } ) => (
				<CollectionButton
					active={ chosenCollection === name }
					key={ name }
					title={ title }
					description={ description }
					icon={ icon }
					onClick={ () => onCollectionClick( name ) }
				/>
			) ) }
		</div>
	);
};

export default CollectionChooser;
