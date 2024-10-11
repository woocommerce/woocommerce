/**
 * External dependencies
 */
import {
	NOTES_STORE_NAME,
	USER_STORE_NAME,
	QUERY_DEFAULTS,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { getUnreadNotesCount } from '~/inbox-panel/utils';
import { getAdminSetting } from '~/utils/admin-settings';

const UNREAD_NOTES_QUERY = {
	page: 1,
	per_page: QUERY_DEFAULTS.pageSize,
	status: 'unactioned',
	type: QUERY_DEFAULTS.noteTypes,
	orderby: 'date',
	order: 'desc',
};

export function hasUnreadNotes( select ) {
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

	getNotes( UNREAD_NOTES_QUERY );
	const isError = Boolean(
		getNotesError( 'getNotes', [ UNREAD_NOTES_QUERY ] )
	);
	const isRequesting = isResolving( 'getNotes', [ UNREAD_NOTES_QUERY ] );

	if ( isError || isRequesting ) {
		return null;
	}

	const latestNotes = getNotes( UNREAD_NOTES_QUERY );
	const unreadNotesCount = getUnreadNotesCount( latestNotes, lastRead );

	return unreadNotesCount > 0;
}

export function getLowStockCount() {
	return getAdminSetting( 'lowStockCount', 0 );
}
