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
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import CollectionChooser, { applyCollection } from './collection-chooser';
import { useTracksLocation } from '../tracks-utils';
import type {
	CollectionName,
	ProductCollectionEditComponentProps,
} from '../types';

const ProductCollectionPlaceholder = (
	props: ProductCollectionEditComponentProps
) => {
	const blockProps = useBlockProps();
	const { clientId, context } = props;

	// @ts-expect-error Type definitions for this function are missing
	// https://github.com/DefinitelyTyped/DefinitelyTyped/blob/master/types/wordpress__blocks/store/actions.d.ts
	const { replaceBlock } = useDispatch( blockEditorStore );
	const tracksLocation = useTracksLocation( context.templateSlug );

	const onCollectionClick = ( collectionName: CollectionName ) => {
		recordEvent(
			'blocks_product_collection_collection_chosen_from_placeholder',
			{
				collection: collectionName,
				location: tracksLocation,
			}
		);
		applyCollection( collectionName, clientId, replaceBlock );
	};

	return (
		<div { ...blockProps }>
			<Placeholder
				className="wc-blocks-product-collection__placeholder"
				instructions={ __(
					'What products do you want to show?',
					'woocommerce'
				) }
			>
				<CollectionChooser onCollectionClick={ onCollectionClick } />
			</Placeholder>
		</div>
	);
};

export default ProductCollectionPlaceholder;
