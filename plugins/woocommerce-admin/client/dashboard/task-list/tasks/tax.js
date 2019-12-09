/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { difference, filter, get } from 'lodash';
import interpolateComponents from 'interpolate-components';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { Card, H, Link, Stepper } from '@woocommerce/components';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { getAdminLink, getSetting, setSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import Connect from './steps/connect';
import { getCountryCode } from 'dashboard/utils';
import Plugins from './steps/plugins';
import StoreLocation from './steps/location';
import withSelect from 'wc-api/with-select';
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
	}

	componentDidMount() {
		this.reset();
	}

	reset() {
		this.setState( this.initialState );
	}

	componentDidUpdate( prevProps ) {
		const { generalSettings, isJetpackConnected, pluginsToActivate, taxSettings } = this.props;
		const {
			woocommerce_calc_taxes,
			woocommerce_store_address,
			woocommerce_default_country,
			woocommerce_store_postcode,
		} = generalSettings;
		const { isPending, stepIndex } = this.state;
		const currentStep = this.getSteps()[ stepIndex ];
		const currentStepKey = currentStep && currentStep.key;
		const isCompleteAddress = Boolean(
			woocommerce_store_address && woocommerce_default_country && woocommerce_store_postcode
		);

		// Show the success screen if all requirements are satisfied from the beginning.
		if (
			null !== stepIndex &&
			( ! pluginsToActivate.length &&
				isCompleteAddress &&
				isJetpackConnected &&
				this.isTaxJarSupported() )
		) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( { stepIndex: null } );
			/* eslint-enable react/no-did-update-set-state */
			return;
		}

		if ( 'store_location' === currentStepKey && isCompleteAddress ) {
			this.completeStep();
		}

		if (
			taxSettings.wc_connect_taxes_enabled &&
			taxSettings.wc_connect_taxes_enabled !== prevProps.taxSettings.wc_connect_taxes_enabled
		) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				automatedTaxEnabled: 'yes' === taxSettings.wc_connect_taxes_enabled ? true : false,
			} );
			/* eslint-enable react/no-did-update-set-state */
		}

		if ( 'connect' === currentStepKey && isJetpackConnected ) {
			this.completeStep();
		}

		if ( isPending && 'yes' === woocommerce_calc_taxes ) {
			window.location = getAdminLink( 'admin.php?page=wc-settings&tab=tax&section=standard' );
		}
	}

	isTaxJarSupported() {
		const { countryCode, tosAccepted } = this.props;
		const { automatedTaxSupportedCountries = [], taxJarActivated } = getSetting( 'onboarding', {} );

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
		const { generalSettings, updateSettings } = this.props;

		if ( 'yes' !== generalSettings.woocommerce_calc_taxes ) {
			this.setState( { isPending: true } );
			updateSettings( {
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

	async updateAutomatedTax() {
		const { createNotice, isTaxSettingsError, updateSettings } = this.props;
		const { automatedTaxEnabled } = this.state;

		await updateSettings( {
			tax: {
				wc_connect_taxes_enabled: automatedTaxEnabled ? 'yes' : 'no',
			},
		} );

		if ( ! isTaxSettingsError ) {
			// @todo This is a workaround to force the task to mark as complete.
			// This should probably be updated to use wc-api so we can fetch tax rates.
			setSetting( 'onboarding', {
				...getSetting( 'onboarding', {} ),
				isTaxComplete: true,
			} );
			createNotice( 'success', __( 'Your tax settings have been updated.', 'woocommerce-admin' ) );
			if ( automatedTaxEnabled ) {
				getHistory().push( getNewPath( {}, '/', {} ) );
			} else {
				this.configureTaxRates();
			}
		} else {
			createNotice(
				'error',
				__( 'There was a problem updating your tax settings.', 'woocommerce-admin' )
			);
		}
	}

	getSteps() {
		const { generalSettings, isGeneralSettingsRequesting, isJetpackConnected } = this.props;
		const { isPending, pluginsToActivate } = this.state;

		const steps = [
			{
				key: 'store_location',
				label: __( 'Set store location', 'woocommerce-admin' ),
				description: __( 'The address from which your business operates', 'woocommerce-admin' ),
				content: (
					<StoreLocation
						{ ...this.props }
						onComplete={ values => {
							const country = getCountryCode( values.countryState );
							recordEvent( 'tasklist_tax_set_location', { country } );
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
				label: __( 'Install Jetpack and WooCommerce Services', 'woocommerce-admin' ),
				description: __(
					'Jetpack and WooCommerce Services allow you to automate sales tax calculations',
					'woocommerce-admin'
				),
				content: (
					<Plugins
						onComplete={ () => {
							recordEvent( 'tasklist_tax_install_extensions', { install_extensions: true } );
							this.completeStep();
						} }
						onSkip={ () => {
							queueRecordEvent( 'tasklist_tax_install_extensions', { install_extensions: false } );
							window.location.href = getAdminLink(
								'admin.php?page=wc-settings&tab=tax&section=standard'
							);
						} }
						skipText={ __( 'Set up tax rates manually', 'woocommerce-admin' ) }
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
						onConnect={ () => {
							recordEvent( 'tasklist_tax_connect_store' );
						} }
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
							{ 'yes' !== generalSettings.woocommerce_calc_taxes &&
								interpolateComponents( {
									mixedString: __(
										'By clicking "Configure" you\'re enabling tax rates and calculations.' +
											'More info {{link}}here{{/link}}.',
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

		return filter( steps, step => step.visible );
	}

	render() {
		const { isPending, stepIndex } = this.state;
		const { isGeneralSettingsRequesting, isTaxSettingsRequesting } = this.props;
		const step = this.getSteps()[ stepIndex ];

		return (
			<div className="woocommerce-task-tax">
				<Card className="is-narrow">
					{ step ? (
						<Stepper
							isPending={ isPending || isGeneralSettingsRequesting || isTaxSettingsRequesting }
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
								onClick={ () => {
									recordEvent( 'tasklist_tax_setup_automated_proceed', {
										setup_automatically: true,
									} );
									this.setState( { automatedTaxEnabled: true }, this.updateAutomatedTax );
								} }
							>
								{ __( 'Yes please', 'woocommerce-admin' ) }
							</Button>
							<Button
								onClick={ () => {
									recordEvent( 'tasklist_tax_setup_automated_proceed', {
										setup_automatically: false,
									} );
									this.setState( { automatedTaxEnabled: false }, this.updateAutomatedTax );
								} }
							>
								{ __( "No thanks, I'll configure taxes manually", 'woocommerce-admin' ) }
							</Button>
						</div>
					) }
				</Card>
			</div>
		);
	}
}

export default compose(
	withSelect( select => {
		const {
			getActivePlugins,
			getOptions,
			getSettings,
			getSettingsError,
			isGetSettingsRequesting,
			isJetpackConnected,
		} = select( 'wc-api' );

		const generalSettings = getSettings( 'general' );
		const isGeneralSettingsError = Boolean( getSettingsError( 'general' ) );
		const isGeneralSettingsRequesting = isGetSettingsRequesting( 'general' );
		const taxSettings = getSettings( 'tax' );
		const isTaxSettingsError = Boolean( getSettingsError( 'tax' ) );
		const isTaxSettingsRequesting = isGetSettingsRequesting( 'tax' );
		const countryCode = getCountryCode( generalSettings.woocommerce_default_country );
		const activePlugins = getActivePlugins();
		const pluginsToActivate = difference( [ 'jetpack', 'woocommerce-services' ], activePlugins );
		const options = getOptions( [ 'wc_connect_options', 'woocommerce_setup_jetpack_opted_in' ] );
		const wc_connect_options = get( options, 'wc_connect_options', {} );
		const tosAccepted =
			wc_connect_options.tos_accepted || options.woocommerce_setup_jetpack_opted_in;

		return {
			countryCode,
			isGeneralSettingsError,
			isGeneralSettingsRequesting,
			generalSettings,
			isTaxSettingsError,
			isTaxSettingsRequesting,
			taxSettings,
			isJetpackConnected: isJetpackConnected(),
			pluginsToActivate,
			tosAccepted,
		};
	} ),
	withDispatch( dispatch => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateSettings } = dispatch( 'wc-api' );

		return {
			createNotice,
			updateSettings,
		};
	} )
)( Tax );
