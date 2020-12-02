/**
 * External dependencies
 */
import {
	NOTES_STORE_NAME,
	USER_STORE_NAME,
	QUERY_DEFAULTS,
} from '@woocommerce/data';
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import { getUnreadNotesCount } from '../../inbox-panel/utils';

export function getUnreadNotes( select ) {
	const { getNotes, getNotesError, isResolving } = select( NOTES_STORE_NAME );

	const { getCurrentUser } = select( USER_STORE_NAME );
	const userData = getCurrentUser();
	const lastRead = parseInt(
		userData &&
			userData.woocommerce_meta &&
			userData.woocommerce_meta.activity_panel_inbox_last_read,
		10
	);

	if ( ! lastRead ) {
		return null;
	}

	// @todo This method would be more performant if we ask only for 1 item per page with status "unactioned".
	// This change should be applied after having pagination implemented.
	const notesQuery = {
		page: 1,
		per_page: QUERY_DEFAULTS.pageSize,
		status: 'unactioned',
		type: QUERY_DEFAULTS.noteTypes,
		orderby: 'date',
		order: 'desc',
	};

	// Disable eslint rule requiring `latestNotes` to be defined below because the next two statements
	// depend on `getNotes` to have been called.
	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const latestNotes = getNotes( notesQuery );
	const isError = Boolean( getNotesError( 'getNotes', [ notesQuery ] ) );
	const isRequesting = isResolving( 'getNotes', [ notesQuery ] );

	if ( isError || isRequesting ) {
		return null;
	}

	const unreadNotesCount = getUnreadNotesCount( latestNotes, lastRead );

	return unreadNotesCount > 0;
}

export function getLowStockCount() {
	return getSetting( 'lowStockCount', 0 );
}
