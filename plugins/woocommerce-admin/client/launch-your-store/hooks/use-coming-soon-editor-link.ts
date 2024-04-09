/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { getAdminLink } from '@woocommerce/settings';

const COMMING_SOON_PAGE_ID_OPTION = 'woocommerce_coming_soon_page_id';

export const useComingSoonEditorLink = () => {
	return useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );

		const isLoading = ! hasFinishedResolution( 'getOption', [
			COMMING_SOON_PAGE_ID_OPTION,
		] );
		const pageID = getOption( COMMING_SOON_PAGE_ID_OPTION );

		const pageLink =
			isLoading || ! pageID
				? // Fallback to the pages list if the page ID is not set yet.
				  getAdminLink( 'edit.php?post_type=page' )
				: getAdminLink( `post.php?post=${ pageID }&action=edit` );

		return [ pageLink, isLoading ];
	}, [] );
};
