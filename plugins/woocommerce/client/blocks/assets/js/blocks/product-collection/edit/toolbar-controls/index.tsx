/**
 * External dependencies
 */
import { BlockControls } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import DisplayLayoutToolbar from './display-layout-toolbar';
import CollectionChooserToolbar from './collection-chooser-toolbar';
import type { ProductCollectionContentProps } from '../../types';
import { getCollectionByName } from '../../collections';

export default function ToolbarControls(
	props: ProductCollectionContentProps
) {
	const { attributes, openCollectionSelectionModal, setAttributes } = props;
	const { query, displayLayout } = attributes;

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
			{ ! query.inherit && (
				<DisplayLayoutToolbar
					displayLayout={ displayLayout }
					setAttributes={ setAttributes }
				/>
			) }
		</BlockControls>
	);
}
