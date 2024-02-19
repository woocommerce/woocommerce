/**
 * External dependencies
 */
import { Button, DateTimePicker, Modal } from '@wordpress/components';
import { getDate } from '@wordpress/date';
import { createElement, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { SchedulePublishModalProps } from './types';

export function SchedulePublishModal( {
	title = __( 'Schedule product', 'woocommerce' ),
	description = __(
		'Decide when this product should become visible to customers.',
		'woocommerce'
	),
	value,
	className,
	onCancel,
	onSchedule,
	...props
}: SchedulePublishModalProps ) {
	const [ date, setDate ] = useState< string >(
		value ?? getDate( null ).toISOString()
	);

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
							setDate( getDate( null ).toISOString() )
						}
					>
						{ __( 'Now', 'woocommerce' ) }
					</Button>
				</div>

				<DateTimePicker currentDate={ date } onChange={ setDate } />
			</div>

			<div className="woocommerce-schedule-publish-modal__buttons">
				<Button variant="tertiary" onClick={ onCancel }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ () => onSchedule?.( date ) }
				>
					{ __( 'Schedule', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
}
