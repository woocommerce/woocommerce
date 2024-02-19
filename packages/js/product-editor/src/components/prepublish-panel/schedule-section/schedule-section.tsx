/**
 * External dependencies
 */
import { PanelBody } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import {
	DateSettings,
	dateI18n,
	getDate,
	date as formatDate,
	__experimentalGetSettings as getSettings,
	isInTheFuture,
} from '@wordpress/date';
import { createElement } from '@wordpress/element';
import { __, _x, isRTL, sprintf } from '@wordpress/i18n';
import { type ProductStatus } from '@woocommerce/data';
import {
	// @ts-expect-error no exported member
	__experimentalPublishDateTimePicker as PublishDateTimePicker,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { ScheduleSectionProps } from './types';
import {
	getSiteSettingsTimezoneAbbreviation,
	isSameDay,
	isSiteSettingsTime12HourFormatted,
	isSiteSettingsTimezoneSameAsDateTimezone,
} from '../../../utils';

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

export function getScheduleLabel( dateAttribute: string, now = new Date() ) {
	const { formats } = getSettings() as DateSettings;
	const date = getDate( dateAttribute );

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

export const TIMEZONELESS_FORMAT = 'Y-m-d\\TH:i:s';

export function ScheduleSection( { postType }: ScheduleSectionProps ) {
	const [ date, setDate ] = useEntityProp< string >(
		'postType',
		postType,
		'date_created_gmt'
	);

	const [ , setStatus, status ] = useEntityProp< ProductStatus >(
		'postType',
		postType,
		'status'
	);

	function handlePublishDateTimePickerChange( value: string | null ) {
		const valueAsDate = value ? getDate( value ) : new Date();
		const newValue = formatDate( TIMEZONELESS_FORMAT, valueAsDate, 'GMT' );

		setDate( newValue );
		if ( isInTheFuture( valueAsDate.toISOString() ) ) {
			setStatus( 'future' );
		} else {
			setStatus( status );
		}
	}

	const gmtDate = `${ date }+00:00`;

	const currentDate = formatDate( TIMEZONELESS_FORMAT, gmtDate, undefined );

	return (
		<PanelBody
			initialOpen={ false }
			// @ts-expect-error title does currently support this value
			title={ [
				__( 'Publish:', 'woocommerce' ),
				<span className="editor-post-publish-panel__link" key="label">
					{ getScheduleLabel( gmtDate ) }
				</span>,
			] }
		>
			<PublishDateTimePicker
				currentDate={ currentDate }
				onChange={ handlePublishDateTimePickerChange }
				is12Hour={ isSiteSettingsTime12HourFormatted() }
			/>
		</PanelBody>
	);
}
