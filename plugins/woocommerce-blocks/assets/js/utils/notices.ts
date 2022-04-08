/**
 * External dependencies
 */
import { dispatch, select } from '@wordpress/data';
import type { Notice } from '@wordpress/notices';

export const hasNoticesOfType = (
	context = '',
	type: 'default' | 'snackbar'
): boolean => {
	const notices: Notice[] = select( 'core/notices' ).getNotices( context );
	return notices.some( ( notice: Notice ) => notice.type === type );
};

export const removeNoticesByStatus = ( status: string, context = '' ): void => {
	const notices = select( 'core/notices' ).getNotices();
	const { removeNotice } = dispatch( 'core/notices' );
	const noticesOfType = notices.filter(
		( notice ) => notice.status === status
	);
	noticesOfType.forEach( ( notice ) => removeNotice( notice.id, context ) );
};
