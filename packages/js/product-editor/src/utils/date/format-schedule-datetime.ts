/**
 * External dependencies
 */
import {
	DateSettings,
	dateI18n,
	getDate,
	__experimentalGetSettings as getSettings,
	isInTheFuture,
} from '@wordpress/date';
import { __, _x, isRTL, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getSiteSettingsTimezoneAbbreviation } from './get-site-settings-timezone-abbreviation';
import { isSameDay } from './is-same-day';
import { isSiteSettingsTimezoneSameAsDateTimezone } from './is-site-settings-timezone-same-as-date-timezone';

export const TIMEZONELESS_FORMAT = 'Y-m-d\\TH:i:s';

export function getFormattedDateTime( value: string | Date, format?: string ) {
	const { formats } = getSettings() as DateSettings;

	const dateTimeFormat = sprintf(
		// translators: %s: Time of day the product is scheduled for.
		_x( 'F j, Y %s', 'product schedule full date format', 'woocommerce' ),
		formats.time
	);

	return dateI18n( format ?? dateTimeFormat, value, undefined );
}

export function getFullScheduleLabel( dateAttribute: string ) {
	const timezoneAbbreviation = getSiteSettingsTimezoneAbbreviation();
	const formattedDate = getFormattedDateTime( dateAttribute );

	return isRTL()
		? `${ timezoneAbbreviation } ${ formattedDate }`
		: `${ formattedDate } ${ timezoneAbbreviation }`;
}

export function formatScheduleDatetime( dateAttribute: string ) {
	const { formats } = getSettings() as DateSettings;
	const date = getDate( dateAttribute );
	const now = getDate( null );

	if ( isSameDay( date, now ) && ! isInTheFuture( dateAttribute ) ) {
		return __( 'Immediately', 'woocommerce' );
	}

	// If the user timezone does not equal the site timezone then using words
	// like 'tomorrow' is confusing, so show the full date.
	if ( ! isSiteSettingsTimezoneSameAsDateTimezone( now ) ) {
		return getFullScheduleLabel( dateAttribute );
	}

	if ( isSameDay( date, now ) ) {
		return sprintf(
			// translators: %s: Time of day the product is scheduled for.
			__( 'Today at %s', 'woocommerce' ),
			getFormattedDateTime( dateAttribute, formats.time )
		);
	}

	const tomorrow = new Date( now );
	tomorrow.setDate( tomorrow.getDate() + 1 );

	if ( isSameDay( date, tomorrow ) ) {
		return sprintf(
			// translators: %s: Time of day the product is scheduled for.
			__( 'Tomorrow at %s', 'woocommerce' ),
			getFormattedDateTime( dateAttribute, formats.time )
		);
	}

	if ( date.getFullYear() === now.getFullYear() ) {
		return getFormattedDateTime(
			date,
			sprintf(
				// translators: %s: Time of day the product is scheduled for.
				_x(
					'F j %s',
					'product schedule date format without year',
					'woocommerce'
				),
				formats.time
			)
		);
	}

	return getFormattedDateTime( dateAttribute );
}
