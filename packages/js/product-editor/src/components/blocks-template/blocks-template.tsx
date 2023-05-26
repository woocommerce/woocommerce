/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { synchronizeBlocksWithTemplate } from '@wordpress/blocks';
import { useLayoutEffect } from '@wordpress/element';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	useEntityBlockEditor,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	useEntityId,
} from '@wordpress/core-data';

export function BlocksTemplate() {
	const blockEditorSettings = useSelect( ( select ) => {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		return select( blockEditorStore ).getSettings();
	}, [] );

	const productId = useEntityId( 'postType', 'product' );

	const [ , , onChange ] = useEntityBlockEditor( 'postType', 'product', {
		id: productId,
	} );

	useLayoutEffect( () => {
		onChange(
			synchronizeBlocksWithTemplate( [], blockEditorSettings?.template ),
			{}
		);
	}, [ blockEditorSettings?.template ] );

	return null;
}
