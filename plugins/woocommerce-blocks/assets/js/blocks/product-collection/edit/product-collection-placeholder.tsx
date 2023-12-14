/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	store as blockEditorStore,
	useBlockProps,
} from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import {
	createBlock,
	// @ts-expect-error Type definitions for this function are missing in Guteberg
	createBlocksFromInnerBlocksTemplate,
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import CollectionChooser from './collection-chooser';
import type { ProductCollectionEditComponentProps } from '../types';
import Icon from '../icon';
import blockJson from '../block.json';
import { getCollectionByName } from '../collections';

const ProductCollectionPlaceholder = (
	props: ProductCollectionEditComponentProps
) => {
	const blockProps = useBlockProps();
	const { clientId } = props;
	// @ts-expect-error Type definitions for this function are missing
	// https://github.com/DefinitelyTyped/DefinitelyTyped/blob/master/types/wordpress__blocks/store/actions.d.ts
	const { replaceBlock } = useDispatch( blockEditorStore );

	const applyCollection = ( chosenCollectionName: string ) => {
		const collection = getCollectionByName( chosenCollectionName );

		if ( ! collection ) {
			return;
		}

		const newBlock = createBlock(
			blockJson.name,
			collection.attributes,
			createBlocksFromInnerBlocksTemplate( collection.innerBlocks )
		);

		replaceBlock( clientId, newBlock );
	};

	return (
		<div { ...blockProps }>
			<Placeholder
				className="wc-blocks-product-collection__placeholder"
				icon={ Icon }
				label={ __( 'Product Collection', 'woocommerce' ) }
				instructions={ __(
					"Choose a collection to get started. Don't worry, you can change and tweak this any time.",
					'woocommerce'
				) }
			>
				<CollectionChooser onCollectionClick={ applyCollection } />
			</Placeholder>
		</div>
	);
};

export default ProductCollectionPlaceholder;
