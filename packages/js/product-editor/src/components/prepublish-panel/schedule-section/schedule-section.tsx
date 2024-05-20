/**
 * External dependencies
 */
import { PanelBody } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	// @ts-expect-error no exported member
	__experimentalPublishDateTimePicker as PublishDateTimePicker,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { useProductScheduled } from '../../../hooks/use-product-scheduled';
import { isSiteSettingsTime12HourFormatted } from '../../../utils';
import { ScheduleSectionProps } from './types';

export function ScheduleSection( { postType }: ScheduleSectionProps ) {
	const { setDate, date, formattedDate } = useProductScheduled( postType );

	async function handlePublishDateTimePickerChange( value: string | null ) {
		await setDate( value ?? undefined );
	}

	return (
		<PanelBody
			initialOpen={ false }
			// @ts-expect-error title does currently support this value
			title={ [
				__( 'Publish:', 'woocommerce' ),
				<span className="editor-post-publish-panel__link" key="label">
					{ formattedDate }
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
