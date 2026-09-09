/**
 * External dependencies
 */
import { dateI18n } from '@wordpress/date';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';

export const EMPTY_VALUE = '—';

/**
 * Format an RFC 3339 date in the site date format and timezone.
 *
 * @param date The date string, or null.
 */
export const formatFinanceDate = ( date: string | null ): string => {
	if ( ! date ) {
		return EMPTY_VALUE;
	}

	return dateI18n(
		getAdminSetting( 'dateFormat', 'F j, Y' ),
		date,
		undefined
	);
};
