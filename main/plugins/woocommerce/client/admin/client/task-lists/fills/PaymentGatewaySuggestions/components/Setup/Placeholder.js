/**
 * External dependencies
 */
import clsx from 'clsx';
import { Card, CardBody } from '@wordpress/components';
import { Stepper } from '@woocommerce/components';

export const Placeholder = () => {
	const classes = clsx(
		'is-loading',
		'woocommerce-task-payment-method',
		'woocommerce-task-card'
	);

	return (
		<Card aria-hidden="true" className={ classes }>
			<CardBody>
				<Stepper
					isVertical
					currentStep={ 'none' }
					steps={ [
						{
							key: 'first',
							label: '',
						},
						{
							key: 'second',
							label: '',
						},
					] }
				/>
			</CardBody>
		</Card>
	);
};
