/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Stepper } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { Configure } from './configure';
import { StoreLocation } from '../components/store-location';

export type ManualConfigurationProps = {
	isPending: boolean;
	onDisable: () => void;
	onAutomate: () => void;
	onManual: () => void;
};

export const ManualConfiguration: React.FC< ManualConfigurationProps > = ( {
	isPending,
	onDisable,
	onAutomate,
	onManual,
} ) => {
	const [ stepIndex, setStepIndex ] = useState( 0 );

	const nextStep = () => {
		setStepIndex( stepIndex + 1 );
	};

	const stepProps = {
		isPending,
		onAutomate,
		onDisable,
		nextStep,
		onManual,
	};

	const steps = [
		{
			key: 'store_location',
			label: __( 'Set store location', 'woocommerce' ),
			description: __(
				'The address from which your business operates',
				'woocommerce'
			),
			content: <StoreLocation { ...stepProps } />,
		},
		{
			key: 'manual_configuration',
			label: __( 'Configure tax rates', 'woocommerce' ),
			description: __(
				'Head over to the tax rate settings screen to configure your tax rates',
				'woocommerce'
			),
			content: <Configure { ...stepProps } />,
		},
	];

	const step = steps[ stepIndex ];

	return (
		<Stepper isVertical={ true } currentStep={ step.key } steps={ steps } />
	);
};
