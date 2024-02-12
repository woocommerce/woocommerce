/**
 * External dependencies
 */
import {
	// @ts-expect-error no exported member
	__experimentalPublishDateTimePicker as PublishDateTimePicker,
} from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __experimentalGetSettings as getSettings } from '@wordpress/date';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { ScheduleSectionProps } from './types';

function is12HourTime() {
	const settings = getSettings() as { formats: { time: string } };

	return /a(?!\\)/i.test(
		settings.formats.time
			.toLowerCase()
			.replace( /\\\\/g, '' )
			.split( '' )
			.reverse()
			.join( '' )
	);
}

export function ScheduleSection( {}: ScheduleSectionProps ) {
	const [ date, setDate ] = useProductEntityProp( 'date_created' );

	function handlePublishDateTimePickerChange( value: string ) {
		setDate( value );
	}

	return (
		<PanelBody initialOpen={ false } title={ __( 'Add', 'woocommerce' ) }>
			<PublishDateTimePicker
				currentDate={ date }
				onChange={ handlePublishDateTimePickerChange }
				is12Hour={ is12HourTime() }
			/>
		</PanelBody>
	);
}
