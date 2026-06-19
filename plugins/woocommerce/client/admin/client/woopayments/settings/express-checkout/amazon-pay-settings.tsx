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
	useAmazonPayEnabledSettings,
	useAmazonPayLocations,
	useExpressCheckoutInPaymentMethodsEnabledSettings,
	useGetSettings,
} from '../data/hooks';

export const AmazonPaySettings = () => {
	const settings = asSettingsRecord( useGetSettings() );
	const supportsPaymentMethodsListMode =
		isExpressCheckoutInPaymentMethodsListSupported( settings );
	const [ isAmazonPayEnabled, setIsAmazonPayEnabled ] =
		useAmazonPayEnabledSettings() as [
			boolean,
			( value: boolean ) => void
		];
	const [ amazonPayLocations, updateAmazonPayLocation ] =
		useAmazonPayLocations() as [
			string[],
			(
				location: 'product' | 'cart' | 'checkout',
				value: boolean
			) => void
		];
	const [
		isExpressCheckoutInPaymentMethodsEnabled,
		setIsExpressCheckoutInPaymentMethodsEnabled,
	] = useExpressCheckoutInPaymentMethodsEnabledSettings() as [
		boolean,
		( value: boolean ) => void
	];
	const isPaymentMethodsListMode =
		supportsPaymentMethodsListMode &&
		isExpressCheckoutInPaymentMethodsEnabled;

	return (
		<>
			<ExpressCheckoutSettingsSection
				className="woopayments-express-checkout-settings__enable"
				title={ __( 'Amazon Pay', 'woocommerce' ) }
				description={
					<>
						<ExpressCheckoutMethodIcons methodId="amazon_pay" />
						<h2>{ __( 'Amazon Pay', 'woocommerce' ) }</h2>
						<p>
							{ __(
								'Allow your customers to collect payments via Amazon Pay.',
								'woocommerce'
							) }
						</p>
					</>
				}
			>
				<CheckboxControl
					checked={ isAmazonPayEnabled }
					label={ __(
						'Enable Amazon Pay as an express payment button',
						'woocommerce'
					) }
					help={ __(
						'Show Amazon Pay buttons on store pages for faster purchases. Customers with Amazon accounts can use their stored payment information.',
						'woocommerce'
					) }
					onChange={ ( value ) =>
						setIsAmazonPayEnabled( Boolean( value ) )
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
							'Apple Pay, Google Pay, and Amazon Pay will appear as options in the payment methods list instead of as separate express checkout buttons.',
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
					enabledLocations={ amazonPayLocations }
					isMethodEnabled={ isAmazonPayEnabled }
					isPaymentMethodsListMode={ isPaymentMethodsListMode }
					onChange={ updateAmazonPayLocation }
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
								'Configure the display of Amazon Pay buttons on your store.',
								'woocommerce'
							) }
						</p>
					</>
				}
			>
				<ExpressCheckoutAppearanceSettings
					currentMethod="amazon_pay"
					includeCta={ false }
				/>
			</ExpressCheckoutSettingsSection>
		</>
	);
};
