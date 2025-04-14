/**
 * External dependencies
 */
import {
	type RecommendedPaymentMethod,
	paymentSettingsStore,
} from '@woocommerce/data';
import { useEffect, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './settings-payments-body.scss';
import './settings-payments-methods.scss';
import {
	getPaymentMethodById,
	getRecommendedPaymentMethods,
} from '~/settings-payments/utils';
import { ListPlaceholder } from './components/list-placeholder';
import { PaymentMethodListItem } from './components/payment-method-list-item';

type PaymentMethodsState = Record< string, boolean >;

interface SettingsPaymentsMethodsProps {
	/**
	 * Current state of payment methods, mapping method IDs to their enabled status.
	 */
	paymentMethodsState: PaymentMethodsState;
	/**
	 * A callback to update the state of payment methods.
	 */
	setPaymentMethodsState: React.Dispatch<
		React.SetStateAction< PaymentMethodsState >
	>;
}

/**
 * Combines Apple Pay and Google Pay into a single payment method.
 *
 * If both Apple Pay and Google Pay exist in the list of payment methods, they are combined into a single
 * method with the ID `apple_google`, including data from both methods. If either is missing, the original
 * list is returned.
 */
const combineRequestMethods = (
	paymentMethods: RecommendedPaymentMethod[]
) => {
	const applePay = getPaymentMethodById( 'apple_pay' )( paymentMethods );
	const googlePay = getPaymentMethodById( 'google_pay' )( paymentMethods );

	if ( ! applePay || ! googlePay ) {
		return paymentMethods; // If either Apple Pay or Google Pay is not found, return the original paymentMethods
	}

	return paymentMethods
		.map( ( method ) => {
			if ( method.id === 'apple_pay' ) {
				// Combine apple_pay and google_pay data into a new payment method
				return {
					...method,
					id: 'apple_google',
					extraTitle: googlePay.title,
					extraDescription: googlePay.description,
					extraIcon: googlePay.icon,
				};
			}

			// Exclude GooglePay from the list
			if ( method.id === 'google_pay' ) {
				return null;
			}

			return method; // Keep the rest of the payment methods
		} )
		.filter(
			( method ): method is RecommendedPaymentMethod => method !== null
		); // Filter null values
};

/**
 * A component for displaying and managing the list of recommended payment methods.
 * Combines Apple Pay and Google Pay into a single method if both exist and allows users
 * to toggle the enabled/disabled state of each payment method.
 */
export const SettingsPaymentsMethods = ( {
	paymentMethodsState,
	setPaymentMethodsState,
}: SettingsPaymentsMethodsProps ) => {
	const [ isExpanded, setIsExpanded ] = useState( false );

	const { paymentMethods, isFetching } = useSelect( ( select ) => {
		const paymentSettings = select( paymentSettingsStore );
		const paymentProviders = paymentSettings.getPaymentProviders() || [];
		const recommendedPaymentMethods =
			getRecommendedPaymentMethods( paymentProviders );

		return {
			isFetching: paymentSettings.isFetching(),
			paymentMethods: combineRequestMethods( recommendedPaymentMethods ),
		};
	}, [] );

	const initialPaymentMethodsState = paymentMethods.reduce<
		Record< string, boolean >
	>(
		(
			acc: Record< string, boolean >,
			{ id, enabled }: { id: string; enabled: boolean }
		) => {
			acc[ id ] = enabled;
			return acc;
		},
		{}
	);

	useEffect( () => {
		if ( initialPaymentMethodsState !== null && ! isFetching ) {
			setPaymentMethodsState( initialPaymentMethodsState );
		}
	}, [ isFetching ] );

	return (
		<div className="settings-payments-methods__container">
			{ isFetching ? (
				<ListPlaceholder rows={ 3 } hasDragIcon={ false } />
			) : (
				<>
					<div className="woocommerce-list">
						{ paymentMethods.map(
							( method: RecommendedPaymentMethod ) => (
								<PaymentMethodListItem
									method={ method }
									paymentMethodsState={ paymentMethodsState }
									setPaymentMethodsState={
										setPaymentMethodsState
									}
									isExpanded={ isExpanded }
									key={ method.id }
								/>
							)
						) }
					</div>
					{ ! isExpanded && (
						<Button
							className="settings-payments-methods__show-more"
							onClick={ () => {
								setIsExpanded( ! isExpanded );
							} }
							tabIndex={ 0 }
							aria-expanded={ isExpanded }
						>
							{ sprintf(
								/* translators: %s: number of disabled payment methods */
								__( 'Show more (%s)', 'woocommerce' ),
								paymentMethods.filter(
									( pm: RecommendedPaymentMethod ) =>
										pm.enabled === false
								).length ?? 0
							) }
						</Button>
					) }
				</>
			) }
		</div>
	);
};

export default SettingsPaymentsMethods;
