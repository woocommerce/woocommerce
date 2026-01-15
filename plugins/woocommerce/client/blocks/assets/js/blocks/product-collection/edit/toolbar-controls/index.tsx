/**
 * External dependencies
 */
import { BlockControls } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import CollectionChooserToolbar from './collection-chooser-toolbar';
import type { ProductCollectionContentProps } from '../../types';
import { getCollectionByName } from '../../collections';

export default function ToolbarControls(
	props: ProductCollectionContentProps
) {
	const { openCollectionSelectionModal } = props;

	const collection = getCollectionByName( props.attributes.collection );
	const showCollectionChooserToolbar =
		collection?.scope?.includes( 'block' ) ||
		collection?.scope === undefined;

	return (
		<BlockControls>
			{ showCollectionChooserToolbar && (
				<CollectionChooserToolbar
					openCollectionSelectionModal={
						openCollectionSelectionModal
					}
				/>
			) }
		</BlockControls>
	);
}
