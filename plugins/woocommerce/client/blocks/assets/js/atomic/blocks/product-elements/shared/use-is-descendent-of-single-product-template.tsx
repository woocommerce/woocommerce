/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { isString } from '@woocommerce/types';
import { CORE_EDITOR_STORE } from '@woocommerce/utils';

export const useIsDescendentOfSingleProductTemplate = () => {
	const isDescendentOfSingleProductTemplate = useSelect( ( select ) => {
		const editor = select( CORE_EDITOR_STORE );
		// @ts-expect-error getEditedPostSlug is not typed
		const postSlug = editor?.getEditedPostSlug?.();

		return isString( postSlug )
			? postSlug.includes( 'single-product' )
			: false;
	}, [] );

	return { isDescendentOfSingleProductTemplate };
};
