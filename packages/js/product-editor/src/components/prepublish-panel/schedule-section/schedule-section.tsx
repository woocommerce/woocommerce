/**
 * External dependencies
 */
import {
	// @ts-expect-error no exported member
	__experimentalPublishDateTimePicker as PublishDateTimePicker,
} from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import {
	DateSettings,
	dateI18n,
	getDate,
	__experimentalGetSettings as getSettings,
} from '@wordpress/date';
import { createElement } from '@wordpress/element';
import { __, _x, isRTL, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { ScheduleSectionProps } from './types';
import {
	getSiteSettingsTimezoneAbbreviation,
	isSameDay,
	isSiteSettingsTime12HourFormatted,
	isSiteSettingsTimezoneSameAsDateTimezone,
} from '../../../utils';

export function getFormattedDateTime( value: string ) {
	const { formats } = getSettings() as DateSettings;

	return dateI18n(
		sprintf(
			// translators: %s: Time of day the product is scheduled for.
			_x(
				'F j, Y %s',
				'product schedule full date format',
				'woocommerce'
			),
			formats.time
		),
		value,
		undefined
	);
}

export function getFullScheduleLabel( dateAttribute: string ) {
	const timezoneAbbreviation = getSiteSettingsTimezoneAbbreviation();
	const formattedDate = getFormattedDateTime( dateAttribute );

	return isRTL()
		? `${ timezoneAbbreviation } ${ formattedDate }`
		: `${ formattedDate } ${ timezoneAbbreviation }`;
}

export function getScheduleLabel( dateAttribute?: string, now = new Date() ) {
	if ( ! dateAttribute ) {
		return __( 'Immediately', 'woocommerce' );
	}

	// If the user timezone does not equal the site timezone then using words
	// like 'tomorrow' is confusing, so show the full date.
	if ( ! isSiteSettingsTimezoneSameAsDateTimezone( now ) ) {
		return getFullScheduleLabel( dateAttribute );
	}

	const { formats } = getSettings() as DateSettings;
	const date = getDate( dateAttribute );

	if ( isSameDay( date, now ) ) {
		return sprintf(
			// translators: %s: Time of day the product is scheduled for.
			__( 'Today at %s', 'woocommerce' ),
			dateI18n( formats.time, dateAttribute, undefined )
		);
	}

	const tomorrow = new Date( now );
	tomorrow.setDate( tomorrow.getDate() + 1 );

	if ( isSameDay( date, tomorrow ) ) {
		return sprintf(
			// translators: %s: Time of day the product is scheduled for.
			__( 'Tomorrow at %s', 'woocommerce' ),
			dateI18n( formats.time, dateAttribute, undefined )
		);
	}

	if ( date.getFullYear() === now.getFullYear() ) {
		return dateI18n(
			sprintf(
				// translators: %s: Time of day the product is scheduled for.
				_x(
					'F j %s',
					'product schedule date format without year',
					'woocommerce'
				),
				formats.time
			),
			date,
			undefined
		);
	}

	return getFormattedDateTime( dateAttribute );
}

export function ScheduleSection( {}: ScheduleSectionProps ) {
	const [ date, setDate ] = useProductEntityProp< string >( 'date_created' );

	function handlePublishDateTimePickerChange( value: string ) {
		setDate( value );
	}

	return (
		<PanelBody
			initialOpen={ false }
			// @ts-expect-error title does currently support this value
			title={ [
				__( 'Add:', 'woocommerce' ),
				<span className="editor-post-publish-panel__link" key="label">
					{ getScheduleLabel( date ) }
				</span>,
			] }
		>
			<PublishDateTimePicker
				currentDate={ date }
				onChange={ handlePublishDateTimePickerChange }
				is12Hour={ isSiteSettingsTime12HourFormatted() }
			/>
		</PanelBody>
	);
}
