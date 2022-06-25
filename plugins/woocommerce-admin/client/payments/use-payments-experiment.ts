/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { loadExperimentAssignment } from '@woocommerce/explat';
import { ExperimentAssignment } from '@automattic/explat-client';
import { useSelect } from '@wordpress/data';
import {
	ONBOARDING_STORE_NAME,
	PAYMENT_GATEWAYS_STORE_NAME,
	PaymentGateway,
	WCDataSelector,
} from '@woocommerce/data';
import moment from 'moment';

/**
 * Internal dependencies
 */
import { isWcPaySupported } from './utils';

export const usePaymentExperiment = () => {
	const [ isLoadingExperiment, setIsLoadingExperiment ] =
		useState< boolean >( true );
	const [ experimentAssignment, setExperimentAssignment ] =
		useState< null | ExperimentAssignment >( null );

	const {
		installedPaymentGateways,
		paymentGatewaySuggestions,
		hasFinishedResolution,
	} = useSelect( ( select: WCDataSelector ) => {
		return {
			installedPaymentGateways: select(
				PAYMENT_GATEWAYS_STORE_NAME
			).getPaymentGateways(),
			paymentGatewaySuggestions: select(
				ONBOARDING_STORE_NAME
			).getPaymentGatewaySuggestions(),
			hasFinishedResolution:
				select( ONBOARDING_STORE_NAME ).hasFinishedResolution(
					'getPaymentGatewaySuggestions'
				) &&
				select( PAYMENT_GATEWAYS_STORE_NAME ).hasFinishedResolution(
					'getPaymentGateways'
				),
		};
	} );

	const isWcPayInstalled = installedPaymentGateways.some(
		( gateway: PaymentGateway ) => {
			return gateway.id === 'woocommerce_payments';
		}
	);

	const isWcPayDisabled = installedPaymentGateways.find(
		( gateway: PaymentGateway ) => {
			return (
				gateway.id === 'woocommerce_payments' &&
				gateway.enabled === false
			);
		}
	);

	useEffect( () => {
		if (
			experimentAssignment === null &&
			hasFinishedResolution &&
			isWcPaySupported( paymentGatewaySuggestions ) &&
			isWcPayInstalled &&
			isWcPayDisabled
		) {
			const momentDate = moment().utc();
			const year = momentDate.format( 'YYYY' );
			const month = momentDate.format( 'MM' );
			setIsLoadingExperiment( true );
			loadExperimentAssignment(
				`woocommerce_payments_settings_banner_${ year }_${ month }`
			)
				.then( ( assignment ) => {
					setExperimentAssignment( assignment );
					setIsLoadingExperiment( false );
				} )
				.catch( () => {
					setIsLoadingExperiment( false );
				} );
		} else if ( hasFinishedResolution ) {
			setIsLoadingExperiment( false );
		}
	}, [
		experimentAssignment,
		hasFinishedResolution,
		isWcPayDisabled,
		isWcPayInstalled,
		paymentGatewaySuggestions,
	] );

	return {
		isLoadingExperiment,
		experimentAssignment,
	};
};
