/**
 * External dependencies
 */
import {
	BaseControl,
	RangeControl,
	RadioControl,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ExpressCheckoutPreview } from './express-checkout-preview';
import { ExpressCheckoutSettingsNotices } from './notices';
import {
	usePaymentRequestButtonBorderRadius,
	usePaymentRequestButtonSize,
	usePaymentRequestButtonTheme,
	usePaymentRequestButtonType,
} from '../data/hooks';

type ExpressCheckoutMethod = 'woopay' | 'payment_request' | 'amazon_pay';

const buttonActionOptions = [
	{
		label: __( 'Only icon', 'woocommerce' ),
		value: 'default',
	},
	{
		label: __( 'Buy with', 'woocommerce' ),
		value: 'buy',
	},
	{
		label: __( 'Donate with', 'woocommerce' ),
		value: 'donate',
	},
	{
		label: __( 'Book with', 'woocommerce' ),
		value: 'book',
	},
];

const buttonSizeOptions = [
	{
		label: __( 'Small (40 px)', 'woocommerce' ),
		value: 'small',
	},
	{
		label: __( 'Medium (48 px)', 'woocommerce' ),
		value: 'medium',
	},
	{
		label: __( 'Large (55 px)', 'woocommerce' ),
		value: 'large',
	},
];

const buttonThemeOptions = [
	{
		label: __( 'Dark', 'woocommerce' ),
		value: 'dark',
	},
	{
		label: __( 'Light', 'woocommerce' ),
		value: 'light',
	},
	{
		label: __( 'Outline', 'woocommerce' ),
		value: 'light-outline',
	},
];

export const ExpressCheckoutAppearanceSettings = ( {
	currentMethod,
	includeCta = true,
}: {
	currentMethod: ExpressCheckoutMethod;
	includeCta?: boolean;
} ) => {
	const [ buttonType, setButtonType ] = usePaymentRequestButtonType() as [
		string,
		( value: string ) => void
	];
	const [ size, setSize ] = usePaymentRequestButtonSize() as [
		string,
		( value: string ) => void
	];
	const [ theme, setTheme ] = usePaymentRequestButtonTheme() as [
		string,
		( value: string ) => void
	];
	const [ radius, setRadius ] = usePaymentRequestButtonBorderRadius() as [
		number,
		( value: number ) => void
	];

	return (
		<div className="woopayments-express-checkout-settings__appearance">
			<ExpressCheckoutSettingsNotices currentMethod={ currentMethod } />
			{ includeCta && (
				<SelectControl
					label={ __( 'Call to action', 'woocommerce' ) }
					help={ __(
						'Select a button label that fits best with the flow of purchase or payment experience on your store.',
						'woocommerce'
					) }
					value={ buttonType }
					options={ buttonActionOptions }
					onChange={ setButtonType }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			) }
			<RadioControl
				label={ __( 'Button size', 'woocommerce' ) }
				selected={ size }
				options={ buttonSizeOptions }
				onChange={ setSize }
			/>
			{ includeCta && (
				<RadioControl
					label={ __( 'Theme', 'woocommerce' ) }
					selected={ theme }
					options={ buttonThemeOptions }
					onChange={ setTheme }
				/>
			) }
			{ includeCta && (
				<BaseControl
					id="woopayments-express-checkout-border-radius"
					label={ __( 'Border radius', 'woocommerce' ) }
					help={ __(
						'Controls the corner roundness of express payment buttons.',
						'woocommerce'
					) }
					__nextHasNoMarginBottom
				>
					<div className="woopayments-express-checkout-settings__border-radius">
						<TextControl
							type="number"
							label={ __(
								'Border radius, number input',
								'woocommerce'
							) }
							hideLabelFromVision
							value={ String( radius ) }
							onChange={ ( value ) =>
								setRadius( parseInt( value || '0', 10 ) )
							}
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
						<RangeControl
							label={ __(
								'Border radius, slider',
								'woocommerce'
							) }
							hideLabelFromVision
							value={ radius }
							min={ 0 }
							max={ 30 }
							withInputField={ false }
							onChange={ ( value ) =>
								setRadius( Number( value || 0 ) )
							}
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
					</div>
				</BaseControl>
			) }
			{ includeCta && (
				<BaseControl
					id="woopayments-express-checkout-preview"
					label={ __( 'Preview', 'woocommerce' ) }
					__nextHasNoMarginBottom
				>
					<div className="woopayments-express-checkout-settings__help-text">
						{ __(
							'See the preview of enabled express payment buttons.',
							'woocommerce'
						) }
					</div>
					<ExpressCheckoutPreview />
				</BaseControl>
			) }
		</div>
	);
};
