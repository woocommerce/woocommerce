/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Card, CardBody } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { difference, filter } from 'lodash';
import interpolateComponents from 'interpolate-components';
import { withDispatch, withSelect } from '@wordpress/data';
import { H, Link, Stepper, Plugins, Spinner } from '@woocommerce/components';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { getAdminLink } from '@woocommerce/wc-admin-settings';
import {
	ONBOARDING_STORE_NAME,
	OPTIONS_STORE_NAME,
	PLUGINS_STORE_NAME,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { recordEvent, queueRecordEvent } from '@woocommerce/tracks';
import { Text } from '@woocommerce/experimental';
import { registerPlugin } from '@wordpress/plugins';
import { WooOnboardingTask } from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import Connect from '../../dashboard/components/connect';
import { createNoticesFromResponse } from '../../lib/notices';
import { getCountryCode } from '../../dashboard/utils';
import StoreLocation from './steps/location';

class Tax extends Component {
	constructor( props ) {
		super( props );
		const { hasCompleteAddress, pluginsToActivate } = props;

		this.initialState = {
			isPending: false,
			stepIndex: hasCompleteAddress ? 1 : 0,
			// Cache the value of pluginsToActivate so that we can
			// show/hide tasks based on it, but not have them update mid task.
			cachedPluginsToActivate: pluginsToActivate,
		};
		this.state = this.initialState;

		this.completeStep = this.completeStep.bind( this );
	}

	componentDidMount() {
		const { query } = this.props;
		const { auto } = query;
		this.reset();

		if ( auto === 'true' ) {
			this.enableAutomatedTax();
		}
	}

	reset() {
		this.setState( this.initialState );
	}

	shouldShowSuccessScreen() {
		const {
			isJetpackConnected,
			hasCompleteAddress,
			pluginsToActivate,
		} = this.props;

		return (
			hasCompleteAddress &&
			! pluginsToActivate.length &&
			isJetpackConnected &&
			this.isTaxJarSupported()
		);
	}

	isTaxJarSupported() {
		const { countryCode, tasksStatus } = this.props;
		const {
			automatedTaxSupportedCountries = [],
			taxJarActivated,
		} = tasksStatus;

		return (
			! taxJarActivated && // WCS integration doesn't work with the official TaxJar plugin.
			automatedTaxSupportedCountries.includes( countryCode )
		);
	}

	completeStep() {
		const { stepIndex } = this.state;
		const steps = this.getSteps();
		const nextStep = steps[ stepIndex + 1 ];

		if ( nextStep ) {
			this.setState( { stepIndex: stepIndex + 1 } );
		}
	}

	async manuallyConfigureTaxRates() {
		const {
			generalSettings,
			updateAndPersistSettingsForGroup,
		} = this.props;

		if ( generalSettings.woocommerce_calc_taxes !== 'yes' ) {
			this.setState( { isPending: true } );
			updateAndPersistSettingsForGroup( 'general', {
				general: {
					...generalSettings,
					woocommerce_calc_taxes: 'yes',
				},
			} )
				.then( () => this.redirectToTaxSettings() )
				.catch( ( error ) => createNoticesFromResponse( error ) );
		} else {
			this.redirectToTaxSettings();
		}
	}

	updateAutomatedTax( isEnabling ) {
		const {
			clearTaskStatusCache,
			createNotice,
			updateAndPersistSettingsForGroup,
			generalSettings,
			taxSettings,
		} = this.props;

		Promise.all( [
			updateAndPersistSettingsForGroup( 'tax', {
				tax: {
					...taxSettings,
					wc_connect_taxes_enabled: isEnabling ? 'yes' : 'no',
				},
			} ),
			updateAndPersistSettingsForGroup( 'general', {
				general: {
					...generalSettings,
					woocommerce_calc_taxes: 'yes',
				},
			} ),
		] )
			.then( () => {
				clearTaskStatusCache();

				if ( isEnabling ) {
					createNotice(
						'success',
						__(
							"You're awesome! One less item on your to-do list ✅",
							'woocommerce-admin'
						)
					);
					getHistory().push( getNewPath( {}, '/', {} ) );
				} else {
					this.redirectToTaxSettings();
				}
			} )
			.catch( () => {
				createNotice(
					'error',
					__(
						'There was a problem updating your tax settings',
						'woocommerce-admin'
					)
				);
			} );
	}

	redirectToTaxSettings() {
		window.location = getAdminLink(
			'admin.php?page=wc-settings&tab=tax&section=standard&wc_onboarding_active_task=tax'
		);
	}

	doNotChargeSalesTax() {
		const { updateOptions } = this.props;

		queueRecordEvent( 'tasklist_tax_connect_store', {
			connect: false,
			no_tax: true,
		} );

		updateOptions( {
			woocommerce_no_sales_tax: true,
			woocommerce_calc_taxes: 'no',
		} ).then( () => {
			window.location = getAdminLink( 'admin.php?page=wc-admin' );
		} );
	}

	getSteps() {
		const {
			generalSettings,
			isJetpackConnected,
			isPending,
			tosAccepted,
			updateOptions,
		} = this.props;
		const { cachedPluginsToActivate } = this.state;
		let step2Label, agreementText;

		if ( cachedPluginsToActivate.includes( 'woocommerce-services' ) ) {
			step2Label = __(
				'Install Jetpack and WooCommerce Tax',
				'woocommerce-admin'
			);
			agreementText = __(
				'By installing Jetpack and WooCommerce Tax you agree to the {{link}}Terms of Service{{/link}}.',
				'woocommerce-admin'
			);
		} else {
			step2Label = __( 'Install Jetpack', 'woocommerce-admin' );
			agreementText = __(
				'By installing Jetpack you agree to the {{link}}Terms of Service{{/link}}.',
				'woocommerce-admin'
			);
		}

		const steps = [
			{
				key: 'store_location',
				label: __( 'Set store location', 'woocommerce-admin' ),
				description: __(
					'The address from which your business operates',
					'woocommerce-admin'
				),
				content: (
					<StoreLocation
						{ ...this.props }
						onComplete={ ( values ) => {
							const country = getCountryCode(
								values.countryState
							);
							recordEvent( 'tasklist_tax_set_location', {
								country,
							} );
							this.completeStep();
						} }
						isSettingsRequesting={ false }
						settings={ generalSettings }
					/>
				),
				visible: true,
			},
			{
				key: 'plugins',
				label: step2Label,
				description: __(
					'Jetpack and WooCommerce Tax allow you to automate sales tax calculations',
					'woocommerce-admin'
				),
				content: (
					<Fragment>
						<Plugins
							onComplete={ ( plugins, response ) => {
								createNoticesFromResponse( response );
								recordEvent(
									'tasklist_tax_install_extensions',
									{
										install_extensions: true,
									}
								);
								updateOptions( {
									woocommerce_setup_jetpack_opted_in: true,
								} );
								this.completeStep();
							} }
							onError={ ( errors, response ) =>
								createNoticesFromResponse( response )
							}
							onSkip={ () => {
								queueRecordEvent(
									'tasklist_tax_install_extensions',
									{
										install_extensions: false,
									}
								);
								this.manuallyConfigureTaxRates();
							} }
							skipText={ __(
								'Set up manually',
								'woocommerce-admin'
							) }
							onAbort={ () => this.doNotChargeSalesTax() }
							abortText={ __(
								"I don't charge sales tax",
								'woocommerce-admin'
							) }
						/>
						{ ! tosAccepted && (
							<Text
								variant="caption"
								className="woocommerce-task__caption"
								size="12"
								lineHeight="16px"
							>
								{ interpolateComponents( {
									mixedString: agreementText,
									components: {
										link: (
											<Link
												href={
													'https://wordpress.com/tos/'
												}
												target="_blank"
												type="external"
											/>
										),
									},
								} ) }
							</Text>
						) }
					</Fragment>
				),
				visible:
					( cachedPluginsToActivate.length || ! tosAccepted ) &&
					this.isTaxJarSupported(),
			},
			{
				key: 'connect',
				label: __( 'Connect your store', 'woocommerce-admin' ),
				description: __(
					'Connect your store to WordPress.com to enable automated sales tax calculations',
					'woocommerce-admin'
				),
				content: (
					<Connect
						{ ...this.props }
						onConnect={ () => {
							recordEvent( 'tasklist_tax_connect_store', {
								connect: true,
								no_tax: false,
							} );
						} }
						onSkip={ () => {
							queueRecordEvent( 'tasklist_tax_connect_store', {
								connect: false,
								no_tax: false,
							} );
							this.manuallyConfigureTaxRates();
						} }
						skipText={ __(
							'Set up tax rates manually',
							'woocommerce-admin'
						) }
						onAbort={ () => this.doNotChargeSalesTax() }
						abortText={ __(
							"My business doesn't charge sales tax",
							'woocommerce-admin'
						) }
					/>
				),
				visible: ! isJetpackConnected && this.isTaxJarSupported(),
			},
			{
				key: 'manual_configuration',
				label: __( 'Configure tax rates', 'woocommerce-admin' ),
				description: __(
					'Head over to the tax rate settings screen to configure your tax rates',
					'woocommerce-admin'
				),
				content: (
					<Fragment>
						<Button
							disabled={ isPending }
							isPrimary
							isBusy={ isPending }
							onClick={ () => {
								recordEvent( 'tasklist_tax_config_rates' );
								this.manuallyConfigureTaxRates();
							} }
						>
							{ __( 'Configure', 'woocommerce-admin' ) }
						</Button>
						<p>
							{ generalSettings.woocommerce_calc_taxes !==
								'yes' &&
								interpolateComponents( {
									mixedString: __(
										/*eslint-disable max-len*/
										'By clicking "Configure" you\'re enabling tax rates and calculations. More info {{link}}here{{/link}}.',
										/*eslint-enable max-len*/
										'woocommerce-admin'
									),
									components: {
										link: (
											<Link
												href="https://docs.woocommerce.com/document/setting-up-taxes-in-woocommerce/?utm_medium=product#section-1"
												target="_blank"
												type="external"
											/>
										),
									},
								} ) }
						</p>
					</Fragment>
				),
				visible: ! this.isTaxJarSupported(),
			},
		];

		return filter( steps, ( step ) => step.visible );
	}

	enableAutomatedTax() {
		recordEvent( 'tasklist_tax_setup_automated_proceed', {
			setup_automatically: true,
		} );
		this.updateAutomatedTax( true );
	}

	renderSuccessScreen() {
		const { isPending } = this.props;

		return (
			<div className="woocommerce-task-tax__success">
				<span
					className="woocommerce-task-tax__success-icon"
					role="img"
					aria-labelledby="woocommerce-task-tax__success-message"
				>
					🎊
				</span>
				<H id="woocommerce-task-tax__success-message">
					{ __( 'Good news!', 'woocommerce-admin' ) }
				</H>
				<p>
					{ interpolateComponents( {
						mixedString: __(
							'{{strong}}Jetpack{{/strong}} and {{strong}}WooCommerce Tax{{/strong}} ' +
								'can automate your sales tax calculations for you.',
							'woocommerce-admin'
						),
						components: {
							strong: <strong />,
						},
					} ) }
				</p>
				<Button
					disabled={ isPending }
					isPrimary
					isBusy={ isPending }
					onClick={ this.enableAutomatedTax.bind( this ) }
				>
					{ __( 'Yes please', 'woocommerce-admin' ) }
				</Button>
				<Button
					disabled={ isPending }
					isTertiary
					onClick={ () => {
						recordEvent( 'tasklist_tax_setup_automated_proceed', {
							setup_automatically: false,
						} );
						this.updateAutomatedTax( false );
					} }
				>
					{ __(
						"No thanks, I'll set up manually",
						'woocommerce-admin'
					) }
				</Button>
				<Button
					disabled={ isPending }
					isTertiary
					onClick={ () => this.doNotChargeSalesTax() }
				>
					{ __( "I don't charge sales tax", 'woocommerce-admin' ) }
				</Button>
			</div>
		);
	}

	render() {
		const { stepIndex } = this.state;
		const { isPending, isResolving } = this.props;
		if ( isResolving ) {
			return <Spinner />;
		}

		const step = this.getSteps()[ stepIndex ];

		return (
			<div className="woocommerce-task-tax">
				<Card className="woocommerce-task-card">
					<CardBody>
						{ this.shouldShowSuccessScreen() ? (
							this.renderSuccessScreen()
						) : (
							<Stepper
								isPending={ isPending || isResolving }
								isVertical={ true }
								currentStep={ step.key }
								steps={ this.getSteps() }
							/>
						) }
					</CardBody>
				</Card>
			</div>
		);
	}
}

const TaxWrapper = compose(
	withSelect( ( select ) => {
		const { getSettings, isUpdateSettingsRequesting } = select(
			SETTINGS_STORE_NAME
		);
		const { getOption, hasFinishedResolution } = select(
			OPTIONS_STORE_NAME
		);
		const {
			getActivePlugins,
			isJetpackConnected,
			isPluginsRequesting,
		} = select( PLUGINS_STORE_NAME );
		const { getTasksStatus } = select( ONBOARDING_STORE_NAME );

		const { general: generalSettings = {} } = getSettings( 'general' );
		const countryCode = getCountryCode(
			generalSettings.woocommerce_default_country
		);
		const {
			woocommerce_store_address: storeAddress,
			woocommerce_default_country: defaultCountry,
			woocommerce_store_postcode: storePostCode,
		} = generalSettings;
		const hasCompleteAddress = Boolean(
			storeAddress && defaultCountry && storePostCode
		);

		const { tax: taxSettings = {} } = getSettings( 'tax' );
		const activePlugins = getActivePlugins();
		const pluginsToActivate = difference(
			[ 'jetpack', 'woocommerce-services' ],
			activePlugins
		);
		const connectOptions = getOption( 'wc_connect_options' ) || {};
		const jetpackOptIn = getOption( 'woocommerce_setup_jetpack_opted_in' );
		const tosAccepted = connectOptions.tos_accepted || jetpackOptIn === '1';

		const tasksStatus = getTasksStatus();

		const isPending =
			isUpdateSettingsRequesting( 'tax' ) ||
			isUpdateSettingsRequesting( 'general' );
		const isResolving =
			isPluginsRequesting( 'getJetpackConnectUrl' ) ||
			! hasFinishedResolution( 'getOption', [
				'woocommerce_setup_jetpack_opted_in',
			] ) ||
			! hasFinishedResolution( 'getOption', [ 'wc_connect_options' ] ) ||
			jetpackOptIn === undefined ||
			connectOptions === undefined;

		return {
			countryCode,
			generalSettings,
			hasCompleteAddress,
			isJetpackConnected: isJetpackConnected(),
			isPending,
			isResolving,
			pluginsToActivate,
			tasksStatus,
			taxSettings,
			tosAccepted,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateOptions } = dispatch( OPTIONS_STORE_NAME );
		const { updateAndPersistSettingsForGroup } = dispatch(
			SETTINGS_STORE_NAME
		);
		const { invalidateResolutionForStoreSelector } = dispatch(
			ONBOARDING_STORE_NAME
		);

		return {
			clearTaskStatusCache: () =>
				invalidateResolutionForStoreSelector( 'getTasksStatus' ),
			createNotice,
			updateAndPersistSettingsForGroup,
			updateOptions,
		};
	} )
)( Tax );

registerPlugin( 'wc-admin-onboarding-task-tax', {
	scope: 'woocommerce-tasks',
	render: () => (
		<WooOnboardingTask id="tax">
			{ ( { query } ) => <TaxWrapper query={ query } /> }
		</WooOnboardingTask>
	),
} );
