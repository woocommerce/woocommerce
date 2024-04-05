/**
 * External dependencies
 */
import { useMemo, cloneElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { noticeContexts } from '@woocommerce/base-context';
import {
	RadioControl,
	RadioControlOptionType,
} from '@woocommerce/blocks-components';
import {
	usePaymentMethodInterface,
	useStoreEvents,
} from '@woocommerce/base-context/hooks';
import { PAYMENT_STORE_KEY } from '@woocommerce/block-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { getPaymentMethods } from '@woocommerce/blocks-registry';
import { isNull } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { getCanMakePaymentArg } from '../../../data/payment/utils/check-payment-methods';
import { CustomerPaymentMethodConfiguration } from '../../../data/payment/types';

/**
 * Returns the option object for a cc or echeck saved payment method token.
 */
const getCcOrEcheckLabel = ( {
	method,
	expires,
}: {
	method: CustomerPaymentMethodConfiguration;
	expires: string;
} ): string => {
	return sprintf(
		/* translators: %1$s is referring to the payment method brand, %2$s is referring to the last 4 digits of the payment card, %3$s is referring to the expiry date.  */
		__( '%1$s ending in %2$s (expires %3$s)', 'woocommerce' ),
		method.display_brand ?? method?.networks?.preferred ?? method.brand,
		method.last4,
		expires
	);
};

/**
 * Returns the option object for any non specific saved payment method.
 */
const getDefaultLabel = ( {
	method,
}: {
	method: CustomerPaymentMethodConfiguration;
} ): string => {
	/* For saved payment methods with brand & last 4 */
	if ( method.brand && method.last4 ) {
		return sprintf(
			/* translators: %1$s is referring to the payment method brand, %2$s is referring to the last 4 digits of the payment card. */
			__( '%1$s ending in %2$s', 'woocommerce' ),
			method.brand,
			method.last4
		);
	}

	/* For saved payment methods without brand & last 4 */
	return sprintf(
		/* translators: %s is the name of the payment method gateway. */
		__( 'Saved token for %s', 'woocommerce' ),
		method.gateway
	);
};

const SavedPaymentMethodOptions = () => {
	const { activeSavedToken, activePaymentMethod, savedPaymentMethods } =
		useSelect( ( select ) => {
			const store = select( PAYMENT_STORE_KEY );
			return {
				activeSavedToken: store.getActiveSavedToken(),
				activePaymentMethod: store.getActivePaymentMethod(),
				savedPaymentMethods: store.getSavedPaymentMethods(),
			};
		} );
	const { __internalSetActivePaymentMethod } =
		useDispatch( PAYMENT_STORE_KEY );
	const canMakePaymentArg = getCanMakePaymentArg();
	const paymentMethods = getPaymentMethods();
	const paymentMethodInterface = usePaymentMethodInterface();
	const { removeNotice } = useDispatch( 'core/notices' );
	const { dispatchCheckoutEvent } = useStoreEvents();

	const options = useMemo< RadioControlOptionType[] >( () => {
		const types = Object.keys( savedPaymentMethods );

		// Get individual payment methods from saved payment methods and put them into a unique array.
		const individualPaymentGateways = new Set(
			types.flatMap( ( type ) =>
				savedPaymentMethods[ type ].map(
					( paymentMethod ) => paymentMethod.method.gateway
				)
			)
		);

		const gatewaysThatCanMakePayment = Array.from(
			individualPaymentGateways
		).filter( ( method ) => {
			return paymentMethods[ method ]?.canMakePayment(
				canMakePaymentArg
			);
		} );

		const mappedOptions = types.flatMap( ( type ) => {
			const typeMethods = savedPaymentMethods[ type ];
			return typeMethods.map( ( paymentMethod ) => {
				const canMakePayment = gatewaysThatCanMakePayment.includes(
					paymentMethod.method.gateway
				);
				if ( ! canMakePayment ) {
					return void 0;
				}
				const isCC = type === 'cc' || type === 'echeck';
				const paymentMethodSlug = paymentMethod.method.gateway;
				return {
					name: `wc-saved-payment-method-token-${ paymentMethodSlug }`,
					label: isCC
						? getCcOrEcheckLabel( paymentMethod )
						: getDefaultLabel( paymentMethod ),
					value: paymentMethod.tokenId.toString(),
					onChange: ( token: string ) => {
						const savedTokenKey = `wc-${ paymentMethodSlug }-payment-token`;
						__internalSetActivePaymentMethod( paymentMethodSlug, {
							token,
							payment_method: paymentMethodSlug,
							[ savedTokenKey ]: token.toString(),
							isSavedToken: true,
						} );
						removeNotice(
							'wc-payment-error',
							noticeContexts.PAYMENTS
						);
						dispatchCheckoutEvent( 'set-active-payment-method', {
							paymentMethodSlug,
						} );
					},
				};
			} );
		} );
		return mappedOptions.filter(
			( option ) => typeof option !== 'undefined'
		) as RadioControlOptionType[];
	}, [
		savedPaymentMethods,
		paymentMethods,
		__internalSetActivePaymentMethod,
		removeNotice,
		dispatchCheckoutEvent,
		canMakePaymentArg,
	] );
	const savedPaymentMethodHandler =
		!! activeSavedToken &&
		paymentMethods[ activePaymentMethod ] &&
		typeof paymentMethods[ activePaymentMethod ]?.savedTokenComponent !==
			'undefined' &&
		! isNull( paymentMethods[ activePaymentMethod ].savedTokenComponent )
			? cloneElement(
					// eslint-disable-next-line @typescript-eslint/ban-ts-comment
					// @ts-ignore - we know for sure that the savedTokenComponent is not null or undefined at this point.
					paymentMethods[ activePaymentMethod ].savedTokenComponent,
					{ token: activeSavedToken, ...paymentMethodInterface }
			  )
			: null;

	return options.length > 0 ? (
		<>
			<RadioControl
				id={ 'wc-payment-method-saved-tokens' }
				selected={ activeSavedToken }
				options={ options }
				onChange={ () => void 0 }
			/>
			{ savedPaymentMethodHandler }
		</>
	) : null;
};

export default SavedPaymentMethodOptions;
