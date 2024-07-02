/**
 * External dependencies
 */
import { Button, DateTimePicker, Modal } from '@wordpress/components';
import { createElement, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import {
	getSiteDatetime,
	isSiteSettingsTime12HourFormatted,
} from '../../utils';
import { SchedulePublishModalProps } from './types';

export function SchedulePublishModal( {
	postType,
	title = __( 'Schedule product', 'woocommerce' ),
	description = __(
		'Decide when this product should become visible to customers.',
		'woocommerce'
	),
	value,
	className,
	onCancel,
	onSchedule,
	isScheduling,
	...props
}: SchedulePublishModalProps ) {
	const [ date, setDate ] = useState< string | undefined >(
		() => value ?? getSiteDatetime()
	);

	function handleDateTimePickerChange( newDate?: string ) {
		setDate( newDate );
	}

	return (
		<Modal
			{ ...props }
			title={ title }
			className={ classNames(
				className,
				'woocommerce-schedule-publish-modal'
			) }
			onRequestClose={ () => onCancel?.() }
		>
			<p className="woocommerce-schedule-publish-modal__description">
				{ description }
			</p>

			<div className="woocommerce-schedule-publish-modal__content">
				<div className="woocommerce-schedule-publish-modal__button-now">
					<strong>{ __( 'Publish', 'woocommerce' ) }</strong>

					<Button
						variant="link"
						onClick={ () =>
							handleDateTimePickerChange( getSiteDatetime() )
						}
					>
						{ __( 'Now', 'woocommerce' ) }
					</Button>
				</div>

				<DateTimePicker
					currentDate={ date }
					onChange={ handleDateTimePickerChange }
					is12Hour={ isSiteSettingsTime12HourFormatted() }
				/>
			</div>

			<div className="woocommerce-schedule-publish-modal__buttons">
				<Button variant="tertiary" onClick={ onCancel }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					variant="primary"
					isBusy={ isScheduling }
					disabled={ isScheduling }
					onClick={ () => onSchedule?.( date ) }
				>
					{ __( 'Schedule', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
}
