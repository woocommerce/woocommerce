/**
 * External dependencies
 */
import { PanelBody } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	// @ts-expect-error no exported member
	__experimentalPublishDateTimePicker as PublishDateTimePicker,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { useProductScheduled } from '../../../hooks/use-product-scheduled';
import { isSiteSettingsTime12HourFormatted } from '../../../utils';
import { ScheduleSectionProps } from './types';

export function ScheduleSection( { postType }: ScheduleSectionProps ) {
	const [ productId ] = useProductEntityProp< number >( 'id' );
	const { schedule, date, formattedDate } = useProductScheduled( postType );

	// @ts-expect-error There are no types for this.
	const { editEntityRecord } = useDispatch( 'core' );

	async function handlePublishDateTimePickerChange( value: string | null ) {
		await schedule( ( product ) => {
			return editEntityRecord( 'postType', postType, productId, product );
		}, value ?? undefined );
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
