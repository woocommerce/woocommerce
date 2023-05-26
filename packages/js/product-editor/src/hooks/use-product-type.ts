/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	store as blockEditorStore,
} from '@wordpress/block-editor';

export function useProductType() {
	const currentSettings = useSelect( ( select ) => {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		return select( blockEditorStore ).getSettings();
	}, [] );

	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore This action exists in the block editor store.
	const { updateSettings } = useDispatch( blockEditorStore );

	function setProductType( productType: string ) {
		const template = productBlockEditorSettings.templates[ productType ];
		if ( ! template ) {
			// eslint-disable-next-line no-console
			console.warn( 'Product type template not found: ', productType );
			return;
		}
		updateSettings( {
			...currentSettings,
			template,
		} );
	}

	return {
		setProductType,
	};
}
