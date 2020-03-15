/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Fragment, cloneElement, Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { get, filter } from 'lodash';
import { Button, FormToggle } from '@wordpress/components';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { Card, H } from '@woocommerce/components';
import {
	getHistory,
	getNewPath,
	updateQueryString,
} from '@woocommerce/navigation';
import {
	WC_ASSET_URL as wcAssetUrl,
	getSetting,
} from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import { recordEvent } from 'lib/tracks';
import { getCountryCode } from 'dashboard/utils';
import withSelect from 'wc-api/with-select';
import Plugins from '../steps/plugins';
import Stripe from './stripe';
import Square from './square';
import PayPal from './paypal';
import { pluginNames } from 'wc-api/onboarding/constants';
import Klarna from './klarna';
import PayFast from './payfast';

class Payments extends Component {
	constructor() {
		super( ...arguments );

		this.recommendedMethod = 'stripe';
		this.completeTask = this.completeTask.bind( this );
		this.markConfigured = this.markConfigured.bind( this );
		this.skipTask = this.skipTask.bind( this );
	}

	completeTask() {
		const { configured, createNotice, options, updateOptions } = this.props;

		updateOptions( {
			woocommerce_task_list_payments: {
				...options.woocommerce_task_list_payments,
				completed: 1,
			},
		} );

		recordEvent( 'tasklist_payment_done', {
			configured,
		} );

		createNotice(
			'success',
			__(
				'💰 Ka-ching! Your store can now accept payments 💳',
				'woocommerce-admin'
			)
		);

		getHistory().push( getNewPath( {}, '/', {} ) );
	}

	skipTask() {
		const { options, updateOptions } = this.props;

		updateOptions( {
			woocommerce_task_list_payments: {
				...options.woocommerce_task_list_payments,
				completed: 1,
			},
		} );

		recordEvent( 'tasklist_payment_skip_task', {
			options: this.getMethodOptions().map( ( method ) => method.key ),
		} );

		getHistory().push( getNewPath( {}, '/', {} ) );
	}

	isStripeEnabled() {
		const { countryCode } = this.props;
		const stripeCountries = getSetting( 'onboarding', {
			stripeSupportedCountries: [],
		} ).stripeSupportedCountries;
		return stripeCountries.includes( countryCode );
	}

	markConfigured( method ) {
		const { options, configured, updateOptions } = this.props;

		getHistory().push( getNewPath( { task: 'payments' }, '/', {} ) );

		if ( configured.includes( method ) ) {
			return;
		}

		recordEvent( 'tasklist_payment_connect_method', {
			payment_method: method,
		} );

		configured.push( method );
		updateOptions( {
			woocommerce_task_list_payments: {
				...options.woocommerce_task_list_payments,
				configured,
			},
		} );
	}

	getMethodOptions() {
		const { countryCode, profileItems } = this.props;

		const methods = [
			{
				key: 'stripe',
				title: __(
					'Credit cards - powered by Stripe',
					'woocommerce-admin'
				),
				content: (
					<Fragment>
						{ __(
							'Accept debit and credit cards in 135+ currencies, methods such as Alipay, ' +
								'and one-touch checkout with Apple Pay.',
							'woocommerce-admin'
						) }
					</Fragment>
				),
				before: <img src={ wcAssetUrl + 'images/stripe.png' } alt="" />,
				visible: this.isStripeEnabled(),
				plugins: [ 'woocommerce-gateway-stripe' ],
				container: <Stripe markConfigured={ this.markConfigured } />,
			},
			{
				key: 'paypal',
				title: __( 'PayPal Checkout', 'woocommerce-admin' ),
				content: (
					<Fragment>
						{ __(
							"Safe and secure payments using credit cards or your customer's PayPal account.",
							'woocommerce-admin'
						) }
					</Fragment>
				),
				before: <img src={ wcAssetUrl + 'images/paypal.png' } alt="" />,
				visible: true,
				plugins: [ 'woocommerce-gateway-paypal-express-checkout' ],
				container: <PayPal markConfigured={ this.markConfigured } />,
			},
			{
				key: 'klarna_checkout',
				title: __( 'Klarna Checkout', 'woocommerce-admin' ),
				content: __(
					'Choose the payment that you want, pay now, pay later or slice it. No credit card numbers, no passwords, no worries.',
					'woocommerce-admin'
				),
				before: (
					<img
						src={ wcAssetUrl + 'images/klarna-black.png' }
						alt=""
					/>
				),
				visible: [ 'SE', 'FI', 'NO', 'NL' ].includes( countryCode ),
				plugins: [ 'klarna-checkout-for-woocommerce' ],
				container: (
					<Klarna
						markConfigured={ this.markConfigured }
						plugin={ 'checkout' }
					/>
				),
			},
			{
				key: 'klarna_payments',
				title: __( 'Klarna Payments', 'woocommerce-admin' ),
				content: __(
					'Choose the payment that you want, pay now, pay later or slice it. No credit card numbers, no passwords, no worries.',
					'woocommerce-admin'
				),
				before: (
					<img
						src={ wcAssetUrl + 'images/klarna-black.png' }
						alt=""
					/>
				),
				visible: [ 'DK', 'DE', 'AT' ].includes( countryCode ),
				plugins: [ 'klarna-payments-for-woocommerce' ],
				container: (
					<Klarna
						markConfigured={ this.markConfigured }
						plugin={ 'payments' }
					/>
				),
			},
			{
				key: 'square',
				title: __( 'Square', 'woocommerce-admin' ),
				content: __(
					'Securely accept credit and debit cards with one low rate, no surprise fees (custom rates available). ' +
						'Sell online and in store and track sales and inventory in one place.',
					'woocommerce-admin'
				),
				before: (
					<img
						src={ wcAssetUrl + 'images/square-black.png' }
						alt=""
					/>
				),
				visible:
					[ 'brick-mortar', 'brick-mortar-other' ].includes(
						profileItems.selling_venues
					) &&
					[ 'US', 'CA', 'JP', 'GB', 'AU' ].includes( countryCode ),
				plugins: [ 'woocommerce-square' ],
				container: <Square markConfigured={ this.markConfigured } />,
			},
			{
				key: 'payfast',
				title: __( 'PayFast', 'woocommerce-admin' ),
				content: (
					<Fragment>
						{ __(
							'The PayFast extension for WooCommerce enables you to accept payments by Credit Card and EFT via one of South Africa’s most popular payment gateways. No setup fees or monthly subscription costs.',
							'woocommerce-admin'
						) }
						<p>
							{ __(
								'Selecting this extension will configure your store to use South African rands as the selected currency.',
								'woocommerce-admin'
							) }
						</p>
					</Fragment>
				),
				before: (
					<img
						src={ wcAssetUrl + 'images/payfast.png' }
						alt="PayFast logo"
					/>
				),
				visible: [ 'ZA' ].includes( countryCode ),
				plugins: [ 'woocommerce-payfast-gateway' ],
				container: <PayFast markConfigured={ this.markConfigured } />,
			},
		];

		return filter( methods, ( method ) => method.visible );
	}

	getCurrentMethod() {
		const { query } = this.props;

		if ( ! query.method ) {
			return;
		}

		const methods = this.getMethodOptions();

		return methods.find( ( method ) => method.key === query.method );
	}

	getInstallStep() {
		const currentMethod = this.getCurrentMethod();

		if ( ! currentMethod.plugins || ! currentMethod.plugins.length ) {
			return;
		}

		const { activePlugins } = this.props;
		const pluginsToInstall = currentMethod.plugins.filter(
			( method ) => ! activePlugins.includes( method )
		);
		const pluginNamesString = currentMethod.plugins
			.map( ( pluginSlug ) => pluginNames[ pluginSlug ] )
			.join( ' ' + __( 'and', 'woocommerce-admin' ) + ' ' );

		return {
			key: 'install',
			label: sprintf(
				__( 'Install %s', 'woocommerce-admin' ),
				pluginNamesString
			),
			content: (
				<Plugins
					onComplete={ () => {
						recordEvent( 'tasklist_payment_install_method', {
							plugins: currentMethod.plugins,
						} );
					} }
					autoInstall
					pluginSlugs={ currentMethod.plugins }
				/>
			),
			isComplete: ! pluginsToInstall.length,
		};
	}

	render() {
		const currentMethod = this.getCurrentMethod();
		const { configured, query } = this.props;

		if ( currentMethod ) {
			return (
				<Card className="woocommerce-task-payment-method is-narrow">
					{ cloneElement( currentMethod.container, {
						query,
						installStep: this.getInstallStep(),
					} ) }
				</Card>
			);
		}

		const methods = this.getMethodOptions();

		return (
			<div className="woocommerce-task-payments">
				{ methods.map( ( method ) => {
					const {
						before,
						container,
						content,
						key,
						title,
						visible,
					} = method;

					if ( ! visible ) {
						return null;
					}

					return (
						<Card
							key={ key }
							className="woocommerce-task-payment is-narrow"
						>
							<div className="woocommerce-task-payment__before">
								{ key === this.recommendedMethod &&
									! configured.includes( key ) && (
										<div className="woocommerce-task-payment__recommended-ribbon">
											<span>
												{ __(
													'Recommended',
													'woocommerce-admin'
												) }
											</span>
										</div>
									) }
								{ before }
							</div>
							<div className="woocommerce-task-payment__text">
								<H className="woocommerce-task-payment__title">
									{ title }
								</H>
								<p className="woocommerce-task-payment__content">
									{ content }
								</p>
							</div>
							<div className="woocommerce-task-payment__after">
								{ container ? (
									<Button
										isPrimary={
											key === this.recommendedMethod
										}
										isDefault={
											key !== this.recommendedMethod
										}
										onClick={ () => {
											recordEvent(
												'tasklist_payment_setup',
												{
													options: this.getMethodOptions().map(
														( option ) => option.key
													),
													selected: key,
												}
											);
											updateQueryString( {
												method: key,
											} );
										} }
									>
										{ __( 'Set up', 'woocommerce-admin' ) }
									</Button>
								) : (
									<FormToggle />
								) }
							</div>
						</Card>
					);
				} ) }
				<div className="woocommerce-task-payments__actions">
					{ configured.length === 0 ? (
						<Button isLink onClick={ this.skipTask }>
							{ __(
								'My store doesn’t take payments',
								'woocommerce-admin'
							) }
						</Button>
					) : (
						<Button isPrimary onClick={ this.completeTask }>
							{ __( 'Done', 'woocommerce-admin' ) }
						</Button>
					) }
				</div>
			</div>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const { getProfileItems, getActivePlugins, getOptions } = select(
			'wc-api'
		);

		const options = getOptions( [
			'woocommerce_task_list_payments',
			'woocommerce_default_country',
		] );
		const countryCode = getCountryCode(
			options.woocommerce_default_country
		);

		const configured = get(
			options,
			[ 'woocommerce_task_list_payments', 'configured' ],
			[]
		);

		return {
			countryCode,
			profileItems: getProfileItems(),
			activePlugins: getActivePlugins(),
			options,
			configured,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateOptions } = dispatch( 'wc-api' );
		return {
			createNotice,
			updateOptions,
		};
	} )
)( Payments );
