/** @format */

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment, Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { filter, noop } from 'lodash';
import { FormToggle, CheckboxControl } from '@wordpress/components';
import { TextControl } from 'newspack-components';

/**
 * WooCommerce dependencies
 */
import { getCountryCode } from 'dashboard/utils';
import { Form, Card, Stepper, List } from '@woocommerce/components';
import { getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import withSelect from 'wc-api/with-select';

class Payments extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			step: 'choose',
		};
		this.completeStep = this.completeStep.bind( this );
	}

	getInitialValues() {
		const values = {
			stripe: false,
			paypal: false,
			klarna_checkout: false,
			klarna_payments: false,
			create_stripe: false,
			create_paypal: false,
			stripe_email: '',
			paypal_email: '',
		};
		return values;
	}

	validate() {
		const errors = {};
		return errors;
	}

	completeStep() {
		const { step } = this.state;
		const steps = this.getSteps();
		const currentStepIndex = steps.findIndex( s => s.key === step );
		const nextStep = steps[ currentStepIndex + 1 ];

		if ( nextStep ) {
			this.setState( { step: nextStep.key } );
		} else {
			getHistory().push( getNewPath( {}, '/', {} ) );
		}
	}

	// If Jetpack is connected and WCS is enabled, we will offer a streamlined option.
	renderWooCommerceServicesStripeConnect( { getInputProps, values } ) {
		if ( ! values.stripe ) {
			return null;
		}

		const { isJetpackConnected, activePlugins } = this.props;
		if ( ! isJetpackConnected || ! activePlugins.includes( 'woocommerce-services' ) ) {
			return null;
		}

		return (
			<div className="woocommerce-task-payments__woocommerce-services-options">
				<CheckboxControl
					label={ __( 'Create a Stripe account for me', 'woocommerce-admin' ) }
					{ ...getInputProps( 'create_stripe' ) }
				/>

				{ values.create_stripe && (
					<TextControl
						label={ __( 'Email address', 'woocommerce-admin' ) }
						{ ...getInputProps( 'stripe_email' ) }
					/>
				) }
			</div>
		);
	}

	renderWooCommerceServicesPayPalConnect( { getInputProps, values } ) {
		if ( ! values.paypal ) {
			return null;
		}

		const { isJetpackConnected, activePlugins } = this.props;
		if ( ! isJetpackConnected || ! activePlugins.includes( 'woocommerce-services' ) ) {
			return null;
		}

		return (
			<div className="woocommerce-task-payments__woocommerce-services-options">
				<CheckboxControl
					label={ __( 'Create a Paypal account for me', 'woocommerce-admin' ) }
					{ ...getInputProps( 'create_paypal' ) }
				/>

				{ values.create_paypal && (
					<TextControl
						label={ __( 'Email address', 'woocommerce-admin' ) }
						{ ...getInputProps( 'paypal_email' ) }
					/>
				) }
			</div>
		);
	}

	getMethodOptions( formData ) {
		const { getInputProps } = formData;
		const { countryCode, profileItems } = this.props;
		const methods = [
			{
				title: __( 'Credit cards - powered by Stripe', 'woocommerce-admin' ),
				content: (
					<Fragment>
						{ __(
							'Accept debit and credit cards in 135+ currencies, methods such as Alipay, ' +
								'and one-touch checkout with Apple Pay.',
							'woocommerce-admin'
						) }
						{ this.renderWooCommerceServicesStripeConnect( formData ) }
					</Fragment>
				),
				before: <div />, // @todo Logo
				after: <FormToggle { ...getInputProps( 'stripe' ) } />,
				visible: true,
			},
			{
				title: __( 'PayPal Checkout', 'woocommerce-admin' ),
				content: (
					<Fragment>
						{ __(
							"Safe and secure payments using credit cards or your customer's PayPal account.",
							'woocommerce-admin'
						) }
						{ this.renderWooCommerceServicesPayPalConnect( formData ) }
					</Fragment>
				),
				before: <div />, // @todo Logo
				after: <FormToggle { ...getInputProps( 'paypal' ) } />,
				visible: true,
			},
			{
				title: __( 'Klarna Checkout', 'woocommerce-admin' ),
				content: __(
					'Choose the payment that you want, pay now, pay later or slice it. No credit card numbers, no passwords, no worries.',
					'woocommerce-admin'
				),
				before: <div />, // @todo Logo
				after: <FormToggle { ...getInputProps( 'klarna_checkout' ) } />,
				visible: [ 'SE', 'FI', 'NO', 'NL' ].includes( countryCode ),
			},
			{
				title: __( 'Klarna Payments', 'woocommerce-admin' ),
				content: __(
					'Choose the payment that you want, pay now, pay later or slice it. No credit card numbers, no passwords, no worries.',
					'woocommerce-admin'
				),
				before: <div />, // @todo Logo
				after: <FormToggle { ...getInputProps( 'klarna_payments' ) } />,
				visible: [ 'DK', 'DE', 'AT' ].includes( countryCode ),
			},
			{
				title: __( 'Square', 'woocommerce-admin' ),
				content: __(
					'Securely accept credit and debit cards with one low rate, no surprise fees (custom rates available). ' +
						'Sell online and in store and track sales and inventory in one place.',
					'woocommerce-admin'
				),
				before: <div />, // @todo Logo
				after: <FormToggle { ...getInputProps( 'square' ) } />,
				visible:
					[ 'brick-mortar', 'brick-mortar-other' ].includes( profileItems.selling_venues ) &&
					[ 'US', 'CA', 'JP', 'GB', 'AU' ].includes( countryCode ),
			},
		];

		return filter( methods, method => method.visible );
	}

	getSteps( formData ) {
		const steps = [
			{
				key: 'choose',
				label: __( 'Choose payment methods', 'woocommerce-admin' ),
				description: __( "Select which payment methods you'd like to use", 'woocommerce-admin' ),
				content: <List items={ this.getMethodOptions( formData ) } />,
				visible: true,
			},
		];

		return filter( steps, step => step.visible );
	}

	render() {
		const { step } = this.state;
		const { isSettingsRequesting } = this.props;
		return (
			<Form
				initialValues={ this.getInitialValues() }
				onSubmitCallback={ noop }
				validate={ this.validate }
			>
				{ ( { getInputProps, values } ) => {
					return (
						<div className="woocommerce-task-payments">
							<Card className="is-narrow">
								<Stepper
									isVertical
									isPending={ isSettingsRequesting }
									currentStep={ step }
									steps={ this.getSteps( { getInputProps, values } ) }
								/>
							</Card>
						</div>
					);
				} }
			</Form>
		);
	}
}

export default compose(
	withSelect( select => {
		const {
			getSettings,
			getSettingsError,
			isGetSettingsRequesting,
			getProfileItems,
			isJetpackConnected,
			getActivePlugins,
		} = select( 'wc-api' );

		const settings = getSettings( 'general' );
		const isSettingsError = Boolean( getSettingsError( 'general' ) );
		const isSettingsRequesting = isGetSettingsRequesting( 'general' );
		const countryCode = getCountryCode( settings.woocommerce_default_country );

		return {
			countryCode,
			isSettingsError,
			isSettingsRequesting,
			settings,
			profileItems: getProfileItems(),
			activePlugins: getActivePlugins(),
			isJetpackConnected: isJetpackConnected(),
		};
	} )
)( Payments );
