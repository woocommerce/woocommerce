/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useDispatch, useSelect } from '@wordpress/data';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useEffect, useMemo, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { WCPayCard } from '../components/WCPayCard';
import { PaymentMethodList } from './components/PaymentMethodList';
import { PaymentMethod } from './components/PaymentMethod';
import { sift } from '../../../../utils';

export const RemotePayments = ( { query } ) => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { getOption } = useSelect( ( select ) => {
		return {
			getOption: select( OPTIONS_STORE_NAME ).getOption,
		};
	} );

	const [ methods, setMethods ] = useState( [] );
	const [ isFetching, setIsFetching ] = useState( true );

	useEffect( () => {
		apiFetch( {
			path: '/wc-admin/onboarding/payments',
		} )
			.then( ( results ) => {
				setMethods( results );
				setIsFetching( false );
			} )
			.catch( () => {
				setIsFetching( false );
			} );
	}, [] );

	const recommendedMethod = useMemo( () => {
		const method = methods.find(
			( m ) =>
				( m.key === 'wcpay' && m.visible ) ||
				( m.key === 'mercadopago' && m.visible )
		);
		if ( ! method ) {
			return 'stripe';
		}
		return method.key;
	}, [ methods ] );

	const enableMethod = ( optionName ) => {
		if ( ! optionName ) {
			return;
		}

		const currentValue = getOption( optionName );

		if ( currentValue === 'yes' ) {
			return;
		}

		// @tood This could be moved to a data store and/or REST API endpoint.
		updateOptions( {
			[ optionName ]: {
				...currentValue,
				enabled: 'yes',
			},
		} );
	};

	const markConfigured = async ( methodKey, queryParams = {} ) => {
		const method = methods.find( ( option ) => option.key === methodKey );

		if ( ! method ) {
			throw `Method ${ methodKey } not found in available methods list`;
		}

		setEnabledMethods( {
			...enabledMethods,
			[ methodKey ]: true,
		} );

		enableMethod( method.optionName );

		recordEvent( 'tasklist_payment_connect_method', {
			payment_method: methodKey,
		} );

		getHistory().push(
			getNewPath( { ...queryParams, task: 'payments' }, '/', {} )
		);
	};

	const recordConnectStartEvent = ( methodName ) => {
		recordEvent( 'tasklist_payment_connect_start', {
			payment_method: methodName,
		} );
	};

	const currentMethod = useMemo( () => {
		if ( ! query.method || isFetching ) {
			return null;
		}

		const method = methods.find( ( m ) => m.key === query.method );

		if ( ! method ) {
			throw `Current method ${ query.method } not found in available methods list`;
		}

		return method;
	}, [ isFetching, query ] );

	const [ enabledMethods, setEnabledMethods ] = useState(
		methods.reduce( ( acc, method ) => {
			acc[ method.key ] = method.isEnabled;
			return acc;
		}, {} )
	);

	if ( currentMethod ) {
		return (
			<PaymentMethod
				method={ currentMethod }
				markConfigured={ markConfigured }
				recordConnectStartEvent={ recordConnectStartEvent }
			/>
		);
	}

	const [ enabledCardMethods, additionalCardMethods ] = sift(
		methods,
		( method ) => method.isEnabled && method.isConfigured
	);

	const wcPayIndex = additionalCardMethods.findIndex(
		( m ) => m.key === 'wcpay'
	);

	const wcPayMethod =
		wcPayIndex === -1
			? null
			: additionalCardMethods.splice( wcPayIndex, 1 );

	return (
		<div className="woocommerce-task-payments">
			{ !! wcPayMethod && <WCPayCard method={ wcPayMethod[ 0 ] } /> }

			{ !! enabledCardMethods.length && (
				<PaymentMethodList
					recommendedMethod={ recommendedMethod }
					heading={ __( 'Enabled payment methods', 'wc-admin' ) }
					methods={ enabledCardMethods }
				/>
			) }

			{ !! additionalCardMethods.length && (
				<PaymentMethodList
					recommendedMethod={ recommendedMethod }
					heading={ __( 'Additional payment methods', 'wc-admin' ) }
					methods={ additionalCardMethods }
					markConfigured={ markConfigured }
				/>
			) }
		</div>
	);
};

export default RemotePayments;
