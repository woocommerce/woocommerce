/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { difference, filter, get } from 'lodash';
import interpolateComponents from 'interpolate-components';
import { withDispatch, withSelect } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { Card, H, Link, Stepper } from '@woocommerce/components';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import {
	getAdminLink,
	getSetting,
	setSetting,
} from '@woocommerce/wc-admin-settings';
import { SETTINGS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import Connect from './steps/connect';
import { getCountryCode } from 'dashboard/utils';
import Plugins from './steps/plugins';
import StoreLocation from './steps/location';
import withWCApiSelect from 'wc-api/with-select';
import { recordEvent, queueRecordEvent } from 'lib/tracks';

class Tax extends Component {
	constructor( props ) {
		super( props );

		this.initialState = {
			isPending: false,
			stepIndex: 0,
			automatedTaxEnabled: true,
			// Cache the value of pluginsToActivate so that we can show/hide tasks based on it, but not have them update mid task.
			pluginsToActivate: props.pluginsToActivate,
		};

		this.state = this.initialState;

		this.completeStep = this.completeStep.bind( this );
		this.configureTaxRates = this.configureTaxRates.bind( this );
		this.updateAutomatedTax = this.updateAutomatedTax.bind( this );
		this.setIsPending = this.setIsPending.bind( this );
	}

	componentDidMount() {
		this.reset();
	}

	reset() {
		this.setState( this.initialState );
	}

	componentDidUpdate( prevProps ) {
		const {
			generalSettings,
			isJetpackConnected,
			pluginsToActivate,
			taxSettings,
		} = this.props;
		const {
			woocommerce_calc_taxes: calcTaxes,
			woocommerce_store_address: storeAddress,
			woocommerce_default_country: defaultCountry,
			woocommerce_store_postcode: storePostCode,
		} = generalSettings;
		const { stepIndex } = this.state;
		const currentStep = this.getSteps()[ stepIndex ];
		const currentStepKey = currentStep && currentStep.key;
		const isCompleteAddress = Boolean(
			storeAddress && defaultCountry && storePostCode
		);

		// Show the success screen if all requirements are satisfied from the beginning.
		if (
			stepIndex !== null &&
			! pluginsToActivate.length &&
			isCompleteAddress &&
			isJetpackConnected &&
			this.isTaxJarSupported()
		) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( { stepIndex: null } );
			/* eslint-enable react/no-did-update-set-state */
			return;
		}

		if ( currentStepKey === 'store_location' && isCompleteAddress ) {
			this.completeStep();
		}

		if (
			taxSettings.wc_connect_taxes_enabled &&
			taxSettings.wc_connect_taxes_enabled !==
				prevProps.taxSettings.wc_connect_taxes_enabled
		) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				automatedTaxEnabled:
					taxSettings.wc_connect_taxes_enabled === 'yes'
						? true
						: false,
			} );
			/* eslint-enable react/no-did-update-set-state */
		}

		if ( currentStepKey === 'connect' && isJetpackConnected ) {
			this.completeStep();
		}

		const {
			woocommerce_calc_taxes: prevCalcTaxes,
		} = prevProps.generalSettings;
		if ( prevCalcTaxes === 'no' && calcTaxes === 'yes' ) {
			window.location = getAdminLink(
				'admin.php?page=wc-settings&tab=tax&section=standard'
			);
		}
	}

	isTaxJarSupported() {
		const { countryCode, tosAccepted } = this.props;
		const {
			automatedTaxSupportedCountries = [],
			taxJarActivated,
		} = getSetting( 'onboarding', {} );

		return (
			! taxJarActivated && // WCS integration doesn't work with the official TaxJar plugin.
			tosAccepted &&
			automatedTaxSupportedCountries.includes( countryCode )
		);
	}

	completeStep() {
		const { stepIndex } = this.state;
		const steps = this.getSteps();
		const nextStep = steps[ stepIndex + 1 ];

		if ( nextStep ) {
			this.setState( { stepIndex: stepIndex + 1 } );
		} else {
			getHistory().push( getNewPath( {}, '/', {} ) );
		}
	}

	configureTaxRates() {
		const {
			generalSettings,
			updateAndPersistSettingsForGroup,
		} = this.props;

		if ( generalSettings.woocommerce_calc_taxes !== 'yes' ) {
			this.setState( { isPending: true } );
			updateAndPersistSettingsForGroup( 'general', {
				general: {
					woocommerce_calc_taxes: 'yes',
				},
			} );
		} else {
			window.location = getAdminLink(
				'admin.php?page=wc-settings&tab=tax&section=standard&wc_onboarding_active_task=tax'
			);
		}
	}

	updateAutomatedTax() {
		const {
			createNotice,
			isGeneralSettingsError,
			isTaxSettingsError,
			updateAndPersistSettingsForGroup,
		} = this.props;
		const { automatedTaxEnabled } = this.state;

		updateAndPersistSettingsForGroup( 'tax', {
			tax: {
				wc_connect_taxes_enabled: automatedTaxEnabled ? 'yes' : 'no',
			},
		} );

		updateAndPersistSettingsForGroup( 'general', {
			general: {
				woocommerce_calc_taxes: 'yes',
			},
		} );

		if ( ! isTaxSettingsError && ! isGeneralSettingsError ) {
			// @todo This is a workaround to force the task to mark as complete.
			// This should probably be updated to use wc-api so we can fetch tax rates.
			setSetting( 'onboarding', {
				...getSetting( 'onboarding', {} ),
				isTaxComplete: true,
			} );
			createNotice(
				'success',
				__(
					"You're awesome! One less item on your to-do list ✅",
					'woocommerce-admin'
				)
			);
			if ( automatedTaxEnabled ) {
				getHistory().push( getNewPath( {}, '/', {} ) );
			} else {
				this.configureTaxRates();
			}
		} else {
			createNotice(
				'error',
				__(
					'There was a problem updating your tax settings.',
					'woocommerce-admin'
				)
			);
		}
	}

	setIsPending( value ) {
		this.setState( { isPending: value } );
	}

	getSteps() {
		const {
			generalSettings,
			isGeneralSettingsRequesting,
			isJetpackConnected,
		} = this.props;
		const { isPending, pluginsToActivate } = this.state;

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
						isSettingsRequesting={ isGeneralSettingsRequesting }
						settings={ generalSettings }
					/>
				),
				visible: true,
			},
			{
				key: 'plugins',
				label: __(
					'Install Jetpack and WooCommerce Services',
					'woocommerce-admin'
				),
				description: __(
					'Jetpack and WooCommerce Services allow you to automate sales tax calculations',
					'woocommerce-admin'
				),
				content: (
					<Plugins
						onComplete={ () => {
							recordEvent( 'tasklist_tax_install_extensions', {
								install_extensions: true,
							} );
							this.completeStep();
						} }
						onSkip={ () => {
							queueRecordEvent(
								'tasklist_tax_install_extensions',
								{
									install_extensions: false,
								}
							);
							window.location.href = getAdminLink(
								'admin.php?page=wc-settings&tab=tax&section=standard'
							);
						} }
						skipText={ __(
							'Set up tax rates manually',
							'woocommerce-admin'
						) }
					/>
				),
				visible: pluginsToActivate.length && this.isTaxJarSupported(),
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
						setIsPending={ this.setIsPending }
						onConnect={ () => {
							recordEvent( 'tasklist_tax_connect_store', {
								connect: true,
							} );
						} }
						onSkip={ () => {
							queueRecordEvent( 'tasklist_tax_connect_store', {
								connect: false,
							} );
							window.location.href = getAdminLink(
								'admin.php?page=wc-settings&tab=tax&section=standard'
							);
						} }
						skipText={ __(
							'Set up tax rates manually',
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
							isPrimary
							isBusy={ isPending }
							onClick={ () => {
								recordEvent( 'tasklist_tax_config_rates' );
								this.configureTaxRates();
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
												href="https://docs.woocommerce.com/document/setting-up-taxes-in-woocommerce/#section-1"
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

	render() {
		const { isPending, stepIndex } = this.state;
		const {
			isGeneralSettingsRequesting,
			isTaxSettingsRequesting,
			taxSettings,
		} = this.props;
		const step = this.getSteps()[ stepIndex ];

		return (
			<div className="woocommerce-task-tax">
				<Card className="is-narrow">
					{ step ? (
						<Stepper
							isPending={
								isPending ||
								isGeneralSettingsRequesting ||
								isTaxSettingsRequesting
							}
							isVertical={ true }
							currentStep={ step.key }
							steps={ this.getSteps() }
						/>
					) : (
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
										'{{strong}}Jetpack{{/strong}} and {{strong}}WooCommerce Services{{/strong}} ' +
											'can automate your sales tax calculations for you.',
										'woocommerce-admin'
									),
									components: {
										strong: <strong />,
									},
								} ) }
							</p>
							<Button
								isPrimary
								isBusy={
									Object.keys( taxSettings ).length &&
									isTaxSettingsRequesting
								}
								onClick={ () => {
									recordEvent(
										'tasklist_tax_setup_automated_proceed',
										{
											setup_automatically: true,
										}
									);
									this.setState(
										{ automatedTaxEnabled: true },
										this.updateAutomatedTax
									);
								} }
							>
								{ __( 'Yes please', 'woocommerce-admin' ) }
							</Button>
							<Button
								onClick={ () => {
									recordEvent(
										'tasklist_tax_setup_automated_proceed',
										{
											setup_automatically: false,
										}
									);
									this.setState(
										{ automatedTaxEnabled: false },
										this.updateAutomatedTax
									);
								} }
							>
								{ __(
									"No thanks, I'll configure taxes manually",
									'woocommerce-admin'
								) }
							</Button>
						</div>
					) }
				</Card>
			</div>
		);
	}
}

export default compose(
	withWCApiSelect( ( select ) => {
		const { getActivePlugins, getOptions, isJetpackConnected } = select(
			'wc-api'
		);

		const activePlugins = getActivePlugins();
		const pluginsToActivate = difference(
			[ 'jetpack', 'woocommerce-services' ],
			activePlugins
		);
		const options = getOptions( [
			'wc_connect_options',
			'woocommerce_setup_jetpack_opted_in',
		] );
		const connectOptions = get( options, 'wc_connect_options', {} );
		const tosAccepted =
			connectOptions.tos_accepted ||
			options.woocommerce_setup_jetpack_opted_in;

		return {
			isJetpackConnected: isJetpackConnected(),
			pluginsToActivate,
			tosAccepted,
		};
	} ),
	withSelect( ( select ) => {
		const {
			getSettings,
			getSettingsError,
			isGetSettingsRequesting,
		} = select( SETTINGS_STORE_NAME );

		const { general: generalSettings = {} } = getSettings( 'general' );
		const isGeneralSettingsError = Boolean( getSettingsError( 'general' ) );
		const isGeneralSettingsRequesting = isGetSettingsRequesting(
			'general'
		);
		const countryCode = getCountryCode(
			generalSettings.woocommerce_default_country
		);

		const { tax: taxSettings = {} } = getSettings( 'tax' );
		const isTaxSettingsError = Boolean( getSettingsError( 'tax' ) );
		const isTaxSettingsRequesting = isGetSettingsRequesting( 'tax' );

		return {
			isGeneralSettingsError,
			isGeneralSettingsRequesting,
			generalSettings,
			countryCode,
			taxSettings,
			isTaxSettingsError,
			isTaxSettingsRequesting,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateAndPersistSettingsForGroup } = dispatch(
			SETTINGS_STORE_NAME
		);

		return {
			createNotice,
			updateAndPersistSettingsForGroup,
		};
	} )
)( Tax );
