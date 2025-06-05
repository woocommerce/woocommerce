/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store as canvasModeStore } from './index';

type CanvasModeSelector = {
	isEditMode: boolean;
};

/**
 * Hook to get canvas mode data from the store.
 * Returns true if canvas=edit is in the URL, false otherwise.
 *
 * @return {CanvasModeSelector} The canvas mode data.
 */
export default function useCanvasMode(): CanvasModeSelector {
	const { isEditMode } = useSelect( ( select ) => {
		const { isEditMode: getIsEditMode } = select( canvasModeStore );
		return {
			isEditMode: getIsEditMode(),
		};
	}, [] );

	return {
		isEditMode,
	};
}
