/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ExpressCheckoutAppearanceSettings } from './appearance-settings';
import {
	ExpressCheckoutLocationCheckboxes,
	ExpressCheckoutSettingsSection,
} from './components';
import { ExpressCheckoutMethodIcons } from './method-icons';
import {
	asSettingsRecord,
	isExpressCheckoutInPaymentMethodsListSupported,
} from './settings-utils';
import {
	useExpressCheckoutInPaymentMethodsEnabledSettings,
	useGetSettings,
	usePaymentRequestEnabledSettings,
	usePaymentRequestLocations,
} from '../data/hooks';

export const PaymentRequestSettings = () => {
	const settings = asSettingsRecord( useGetSettings() );
	const supportsPaymentMethodsListMode =
		isExpressCheckoutInPaymentMethodsListSupported( settings );
	const [ isPaymentRequestEnabled, setIsPaymentRequestEnabled ] =
		usePaymentRequestEnabledSettings() as [
			boolean,
			( value: boolean ) => void
		];
	const [
		isExpressCheckoutInPaymentMethodsEnabled,
		setIsExpressCheckoutInPaymentMethodsEnabled,
	] = useExpressCheckoutInPaymentMethodsEnabledSettings() as [
		boolean,
		( value: boolean ) => void
	];
	const [ paymentRequestLocations, updatePaymentRequestLocation ] =
		usePaymentRequestLocations() as [
			string[],
			(
				location: 'product' | 'cart' | 'checkout',
				value: boolean
			) => void
		];
	const isPaymentMethodsListMode =
		supportsPaymentMethodsListMode &&
		isExpressCheckoutInPaymentMethodsEnabled;

	return (
		<>
			<ExpressCheckoutSettingsSection
				className="woopayments-express-checkout-settings__enable"
				title={ __( 'Apple Pay / Google Pay', 'woocommerce' ) }
				description={
					<>
						<ExpressCheckoutMethodIcons methodId="payment_request" />
						<h2>
							{ __( 'Apple Pay / Google Pay', 'woocommerce' ) }
						</h2>
						<p>
							{ __(
								'Allow your customers to collect payments via Apple Pay and Google Pay.',
								'woocommerce'
							) }
						</p>
					</>
				}
			>
				<CheckboxControl
					checked={ isPaymentRequestEnabled }
					label={ __(
						'Enable Apple Pay / Google Pay as express payment buttons',
						'woocommerce'
					) }
					help={ __(
						'Show express payment buttons on store pages for faster purchases. Customers with Apple Pay or Google Pay enabled will be able to pay with their preferred wallet.',
						'woocommerce'
					) }
					onChange={ ( value ) =>
						setIsPaymentRequestEnabled( Boolean( value ) )
					}
					__nextHasNoMarginBottom
				/>
				{ supportsPaymentMethodsListMode && (
					<CheckboxControl
						checked={ isExpressCheckoutInPaymentMethodsEnabled }
						label={ __(
							'Enable express checkout methods as options in the payment methods list',
							'woocommerce'
						) }
						help={ __(
							'Apple Pay and Google Pay will appear as options in the payment methods list instead of as separate express checkout buttons.',
							'woocommerce'
						) }
						onChange={ ( value ) =>
							setIsExpressCheckoutInPaymentMethodsEnabled(
								Boolean( value )
							)
						}
						__nextHasNoMarginBottom
					/>
				) }
				<ExpressCheckoutLocationCheckboxes
					enabledLocations={ paymentRequestLocations }
					isMethodEnabled={ isPaymentRequestEnabled }
					isPaymentMethodsListMode={ isPaymentMethodsListMode }
					onChange={ updatePaymentRequestLocation }
				/>
			</ExpressCheckoutSettingsSection>
			<ExpressCheckoutSettingsSection
				className="woopayments-express-checkout-settings__general"
				title={ __( 'Settings', 'woocommerce' ) }
				description={
					<>
						<h2>{ __( 'Settings', 'woocommerce' ) }</h2>
						<p>
							{ __(
								'Configure the display of Apple Pay and Google Pay buttons on your store.',
								'woocommerce'
							) }
						</p>
					</>
				}
			>
				<ExpressCheckoutAppearanceSettings currentMethod="payment_request" />
			</ExpressCheckoutSettingsSection>
		</>
	);
};
