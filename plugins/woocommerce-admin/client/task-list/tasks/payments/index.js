/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import classnames from 'classnames';
import { cloneElement, Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import {
	Button,
	Card,
	CardBody,
	CardMedia,
	CardFooter,
	FormToggle,
	Spinner,
} from '@wordpress/components';
import { withDispatch, withSelect } from '@wordpress/data';
import { H, Plugins } from '@woocommerce/components';
import {
	getHistory,
	getNewPath,
	updateQueryString,
} from '@woocommerce/navigation';
import {
	ONBOARDING_STORE_NAME,
	OPTIONS_STORE_NAME,
	PLUGINS_STORE_NAME,
	pluginNames,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { createNoticesFromResponse } from '../../../lib/notices';
import { getCountryCode } from '../../../dashboard/utils';
import { getPaymentMethods } from './methods';

export const setMethodEnabledOption = async (
	optionName,
	value,
	{ clearTaskStatusCache, updateOptions, options }
) => {
	const methodOptions = options[ optionName ];

	// Don't update the option if it already has the same value.
	if ( methodOptions.enabled !== value ) {
		await updateOptions( {
			[ optionName ]: {
				...methodOptions,
				enabled: value,
			},
		} );

		clearTaskStatusCache();
	}
};

class Payments extends Component {
	constructor( props ) {
		super( ...arguments );
		const { methods } = props;

		const enabledMethods = {};
		methods.forEach(
			( method ) => ( enabledMethods[ method.key ] = method.isEnabled )
		);
		this.state = {
			busyMethod: null,
			enabledMethods,
			recommendedMethod: this.getRecommendedMethod(),
		};

		this.markConfigured = this.markConfigured.bind( this );
	}

	componentDidUpdate() {
		const { recommendedMethod } = this.state;

		const method = this.getRecommendedMethod();
		if ( recommendedMethod !== method ) {
			this.setState( {
				recommendedMethod: method,
			} );
		}
	}

	getRecommendedMethod() {
		const { methods } = this.props;
		return methods.find( ( m ) => m.key === 'wcpay' && m.visible )
			? 'wcpay'
			: 'stripe';
	}

	async markConfigured( methodName, queryParams = {} ) {
		const { enabledMethods } = this.state;
		const { methods } = this.props;

		const method = methods.find( ( option ) => option.key === methodName );

		if ( ! method ) {
			throw `Method ${ methodName } not found in available methods list`;
		}

		this.setState( {
			enabledMethods: {
				...enabledMethods,
				[ methodName ]: true,
			},
		} );

		await setMethodEnabledOption( method.optionName, 'yes', this.props );

		recordEvent( 'tasklist_payment_connect_method', {
			payment_method: methodName,
		} );

		getHistory().push(
			getNewPath( { ...queryParams, task: 'payments' }, '/', {} )
		);
	}

	getCurrentMethod() {
		const { methods, query } = this.props;

		if ( ! query.method ) {
			return;
		}

		const currentMethod = methods.find(
			( method ) => method.key === query.method
		);

		if ( ! currentMethod ) {
			throw `Current method ${ query.method } not found in available methods list`;
		}

		return currentMethod;
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
					onComplete={ ( plugins, response ) => {
						createNoticesFromResponse( response );
						recordEvent( 'tasklist_payment_install_method', {
							plugins: currentMethod.plugins,
						} );
					} }
					onError={ ( errors, response ) =>
						createNoticesFromResponse( response )
					}
					autoInstall
					pluginSlugs={ currentMethod.plugins }
				/>
			),
			isComplete: ! pluginsToInstall.length,
		};
	}

	async toggleMethod( key ) {
		const { methods } = this.props;
		const { enabledMethods } = this.state;
		const method = methods.find( ( option ) => option.key === key );

		if ( ! method ) {
			throw `Method ${ key } not found in available methods list`;
		}

		enabledMethods[ key ] = ! enabledMethods[ key ];
		this.setState( { enabledMethods } );

		recordEvent( 'tasklist_payment_toggle', {
			enabled: ! method.isEnabled,
			payment_method: key,
		} );

		await setMethodEnabledOption(
			method.optionName,
			method.isEnabled ? 'no' : 'yes',
			this.props
		);
	}

	async handleClick( method ) {
		const { methods } = this.props;
		const { key, onClick } = method;

		recordEvent( 'tasklist_payment_setup', {
			options: methods.map( ( option ) => option.key ),
			selected: key,
		} );

		if ( onClick ) {
			this.setState( { busyMethod: key } );
			await new Promise( onClick )
				.then( () => {
					this.setState( { busyMethod: null } );
				} )
				.catch( () => {
					this.setState( { busyMethod: null } );
				} );

			return;
		}

		updateQueryString( {
			method: key,
		} );
	}

	getSetupButtons( method ) {
		const { busyMethod, enabledMethods, recommendedMethod } = this.state;
		const { container, isConfigured, key } = method;
		if ( container && ! isConfigured ) {
			return (
				<div>
					<Button
						isPrimary={ key === recommendedMethod }
						isSecondary={ key !== recommendedMethod }
						isBusy={ busyMethod === key }
						disabled={ busyMethod }
						onClick={ () => this.handleClick( method ) }
					>
						{ __( 'Set up', 'woocommerce-admin' ) }
					</Button>
				</div>
			);
		}
		return (
			<FormToggle
				checked={ enabledMethods[ key ] }
				onChange={ () => this.toggleMethod( key ) }
				onClick={ ( e ) => e.stopPropagation() }
			/>
		);
	}

	render() {
		const currentMethod = this.getCurrentMethod();
		const { recommendedMethod } = this.state;
		const { methods, query } = this.props;

		if ( currentMethod ) {
			return (
				<Card className="woocommerce-task-payment-method woocommerce-task-card">
					<CardBody>
						{ cloneElement( currentMethod.container, {
							methodConfig: currentMethod,
							query,
							installStep: this.getInstallStep(),
							markConfigured: this.markConfigured,
							hasCbdIndustry: currentMethod.hasCbdIndustry,
						} ) }
					</CardBody>
				</Card>
			);
		}

		return (
			<div className="woocommerce-task-payments">
				{ methods.map( ( method ) => {
					const {
						before,
						content,
						isConfigured,
						key,
						title,
						visible,
						loading,
					} = method;

					if ( ! visible ) {
						return null;
					}

					const classes = classnames(
						'woocommerce-task-payment',
						'woocommerce-task-card',
						! isConfigured &&
							'woocommerce-task-payment-not-configured',
						'woocommerce-task-payment-' + key
					);

					const isRecommended =
						key === recommendedMethod && ! isConfigured;
					const showRecommendedRibbon =
						isRecommended && key !== 'wcpay';
					const showRecommendedPill =
						isRecommended && key === 'wcpay';

					return (
						<Card key={ key } className={ classes }>
							{ showRecommendedRibbon && (
								<div className="woocommerce-task-payment__recommended-ribbon">
									<span>
										{ __(
											'Recommended',
											'woocommerce-admin'
										) }
									</span>
								</div>
							) }
							<CardMedia isBorderless>{ before }</CardMedia>
							<CardBody>
								<H className="woocommerce-task-payment__title">
									{ title }
									{ showRecommendedPill && (
										<span className="woocommerce-task-payment__recommended-pill">
											{ __(
												'Recommended',
												'woocommerce-admin'
											) }
										</span>
									) }
								</H>
								<div className="woocommerce-task-payment__content">
									{ content }
								</div>
							</CardBody>
							<CardFooter isBorderless>
								{ loading ? (
									<Spinner />
								) : (
									this.getSetupButtons( method )
								) }
							</CardFooter>
						</Card>
					);
				} ) }
			</div>
		);
	}
}

export default compose(
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		const {
			installAndActivatePlugins,
			invalidateResolutionForStoreSelector: invalidatePluginStoreSelector,
		} = dispatch( PLUGINS_STORE_NAME );
		const { updateOptions } = dispatch( OPTIONS_STORE_NAME );
		const {
			invalidateResolution,
			invalidateResolutionForStoreSelector,
		} = dispatch( ONBOARDING_STORE_NAME );
		invalidateResolution( 'getProfileItems', [] );
		invalidateResolution( 'getTasksStatus', [] );
		return {
			clearTaskStatusCache: () => {
				invalidateResolutionForStoreSelector( 'getTasksStatus' );
				invalidatePluginStoreSelector( 'getPaypalOnboardingStatus' );
			},
			createNotice,
			installAndActivatePlugins,
			updateOptions,
		};
	} ),
	withSelect( ( select, props ) => {
		const { createNotice, installAndActivatePlugins } = props;
		const { getProfileItems } = select( ONBOARDING_STORE_NAME );
		const { getOption } = select( OPTIONS_STORE_NAME );
		const {
			getActivePlugins,
			isJetpackConnected,
			getPaypalOnboardingStatus,
			hasFinishedResolution,
		} = select( PLUGINS_STORE_NAME );
		const { getSettings } = select( SETTINGS_STORE_NAME );
		const { general: generalSettings = {} } = getSettings( 'general' );
		const { getTasksStatus } = select( ONBOARDING_STORE_NAME );

		const activePlugins = getActivePlugins();
		const onboardingStatus = getTasksStatus();
		const profileItems = getProfileItems();

		const optionNames = [
			'woocommerce_woocommerce_payments_settings',
			'woocommerce_stripe_settings',
			'woocommerce-ppcp-settings',
			'woocommerce_ppcp-gateway_settings',
			'woocommerce_payfast_settings',
			'woocommerce_square_credit_card_settings',
			'woocommerce_klarna_payments_settings',
			'woocommerce_kco_settings',
			'wc_square_refresh_tokens',
			'woocommerce_cod_settings',
			'woocommerce_bacs_settings',
			'woocommerce_bacs_accounts',
			'woocommerce_eway_settings',
			'woocommerce_razorpay_settings',
			'woocommerce_mollie_payments_settings',
			'woocommerce_payubiz_settings',
			'woocommerce_paystack_settings',
		];

		const options = optionNames.reduce( ( result, name ) => {
			result[ name ] = getOption( name );
			return result;
		}, {} );
		const countryCode = getCountryCode(
			generalSettings.woocommerce_default_country
		);
		const paypalOnboardingStatus = getPaypalOnboardingStatus();

		const methods = getPaymentMethods( {
			activePlugins,
			countryCode,
			createNotice,
			installAndActivatePlugins,
			isJetpackConnected: isJetpackConnected(),
			onboardingStatus,
			options,
			profileItems,
			paypalOnboardingStatus,
			loadingPaypalStatus:
				! hasFinishedResolution( 'getPaypalOnboardingStatus' ) &&
				! paypalOnboardingStatus,
		} );

		return {
			countryCode,
			profileItems,
			activePlugins,
			options,
			methods,
		};
	} )
)( Payments );
