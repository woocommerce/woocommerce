/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

export const useIsDescendentOfSingleProductTemplate = () => {
	const isDescendentOfSingleProductTemplate = useSelect( ( select ) => {
		const store = select( 'core/edit-site' );
		const postId = store?.getEditedPostId< string | undefined >();

		return Boolean( postId?.includes( '//single-product' ) );
	}, [] );

	return { isDescendentOfSingleProductTemplate };
};
