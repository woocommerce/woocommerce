/**
 * External dependencies
 */
import { store as editorStore } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { isString } from '@woocommerce/types';

export const useIsDescendentOfSingleProductTemplate = () => {
	const isDescendentOfSingleProductTemplate = useSelect( ( select ) => {
		// @ts-expect-error getEditedPostSlug is not typed
		const postSlug = select( editorStore ).getEditedPostSlug();

		return isString( postSlug )
			? postSlug.includes( 'single-product' )
			: false;
	}, [] );

	return { isDescendentOfSingleProductTemplate };
};
