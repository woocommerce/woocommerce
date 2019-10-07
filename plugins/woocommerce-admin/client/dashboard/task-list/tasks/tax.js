/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from 'newspack-components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { filter } from 'lodash';
import { FormToggle } from '@wordpress/components';
import interpolateComponents from 'interpolate-components';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { Card, H, Stepper } from '@woocommerce/components';
import { getAdminLink, getHistory, getNewPath } from '@woocommerce/navigation';
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import Connect from './steps/connect';
import { getCountryCode } from 'dashboard/utils';
import Plugins from './steps/plugins';
import StoreLocation from './steps/location';
import withSelect from 'wc-api/with-select';

class Tax extends Component {
	constructor() {
		super( ...arguments );

		this.initialState = {
			isPending: false,
			stepIndex: 0,
			automatedTaxEnabled: true,
		};

		this.state = this.initialState;

		this.completeStep = this.completeStep.bind( this );
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
			woocommerce_store_address,
			woocommerce_default_country,
			woocommerce_store_postcode,
		} = generalSettings;
		const { stepIndex } = this.state;
		const currentStep = this.getSteps()[ stepIndex ];
		const currentStepKey = currentStep && currentStep.key;
		const isCompleteAddress =
			woocommerce_store_address && woocommerce_default_country && woocommerce_store_postcode;

		// Show the success screen if all requirements are satisfied from the beginning.
		if (
			0 === stepIndex &&
			( ! pluginsToActivate.length &&
				isCompleteAddress &&
				isJetpackConnected &&
				this.isSupportedCountry() )
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
	}

	isSupportedCountry() {
		const { countryCode } = this.props;
		const { automatedTaxSupportedCountries = [] } = getSetting( 'onboarding', {} );
		return automatedTaxSupportedCountries.includes( countryCode );
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

	async updateAutomatedTax() {
		const { createNotice, isTaxSettingsError, updateSettings } = this.props;
		const { automatedTaxEnabled } = this.state;

		await updateSettings( {
			tax: {
				wc_connect_taxes_enabled: automatedTaxEnabled ? 'yes' : 'no',
			},
		} );

		if ( ! isTaxSettingsError ) {
			createNotice( 'success', __( 'Your tax settings have been updated.', 'woocommerce-admin' ) );
			if ( automatedTaxEnabled ) {
				getHistory().push( getNewPath( {}, '/', {} ) );
			} else {
				window.location = getAdminLink( 'admin.php?page=wc-settings&tab=tax&section=standard' );
			}
		} else {
			createNotice(
				'error',
				__( 'There was a problem updating your tax settings.', 'woocommerce-admin' )
			);
		}
	}

	getAutomatedTaxStepContent() {
		const { automatedTaxEnabled } = this.state;
		const { isTaxSettingsRequesting } = this.props;

		return (
			<Fragment>
				<div className="woocommerce-task-tax__automated-tax-control">
					<i className="material-icons-outlined">autorenew</i>
					<div className="woocommerce-task-tax__automated-tax-control-inner">
						<label
							htmlFor="woocommerce-task-tax__automated-tax-control-input"
							className="woocommerce-task-tax__automated-tax-control-label"
						>
							{ __( 'Automate sales tax calculations', 'woocommerce-adfmin' ) }
						</label>
						<FormToggle
							id="woocommerce-task-tax__automated-tax-control-input"
							checked={ automatedTaxEnabled }
							onChange={ () => this.setState( { automatedTaxEnabled: ! automatedTaxEnabled } ) }
						/>
					</div>
				</div>
				<Button isPrimary onClick={ this.updateAutomatedTax } isBusy={ isTaxSettingsRequesting }>
					{ __( 'Complete task', 'woocommerce-admin' ) }
				</Button>
			</Fragment>
		);
	}

	getSteps() {
		const { generalSettings, isGeneralSettingsRequesting } = this.props;

		const steps = [
			{
				key: 'store_location',
				label: __( 'Set store location', 'woocommerce-admin' ),
				description: __( 'The address from which your business operates', 'woocommerce-admin' ),
				content: (
					<StoreLocation
						{ ...this.props }
						onComplete={ this.completeStep }
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
					'Jetpack and WooCommerce services allow you to automate sales tax calculations',
					'woocommerce-admin'
				),
				content: (
					<Plugins
						onComplete={ this.completeStep }
						onSkip={ () =>
							( window.location.href = getAdminLink(
								'admin.php?page=wc-settings&tab=tax&section=standard'
							) )
						}
						skipText={ __( 'Set up tax rates manually', 'woocommerce-admin' ) }
					/>
				),
				visible: this.isSupportedCountry(),
			},
			{
				key: 'connect',
				label: __( 'Connect your store', 'woocommerce-admin' ),
				description: __(
					'Connect your store to WordPress.com to enable automated sales tax calculations',
					'woocommerce-admin'
				),
				content: <Connect { ...this.props } />,
				visible: this.isSupportedCountry(),
			},
			{
				key: 'automated_tax',
				label: __( 'Enable automated tax calculations', 'woocommerce-admin' ),
				description: __(
					'Sales taxes will be calculated automatically when a customer checks out',
					'woocommerce-admin'
				),
				content: this.getAutomatedTaxStepContent(),
				visible: this.isSupportedCountry(),
			},
			{
				key: 'manual_configuration',
				label: __( 'Congifure tax rates', 'woocommerce-admin' ),
				description: __(
					'Head over to the tax rate settings screen to configure your tax rates',
					'woocommerce-admin'
				),
				content: (
					<Button
						isPrimary
						href={ getAdminLink( 'admin.php?page=wc-settings&tab=tax&section=standard' ) }
					>
						{ __( 'Configure', 'woocommerce-admin' ) }
					</Button>
				),
				visible: ! this.isSupportedCountry(),
			},
		];

		return filter( steps, step => step.visible );
	}

	render() {
		const { isPending, stepIndex } = this.state;
		const { isGeneralSettingsRequesting, isTaxSettingsRequesting } = this.props;

		return (
			<div className="woocommerce-task-tax">
				<Card className="is-narrow">
					{ null !== stepIndex ? (
						<Stepper
							isPending={ isPending || isGeneralSettingsRequesting || isTaxSettingsRequesting }
							isVertical={ true }
							currentStep={ this.getSteps()[ stepIndex ].key }
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
								onClick={ () =>
									this.setState( { automatedTaxEnabled: true }, this.updateAutomatedTax )
								}
							>
								{ __( 'Yes please', 'woocommerce-admin' ) }
							</Button>
							<Button
								onClick={ () =>
									this.setState( { automatedTaxEnabled: false }, this.updateAutomatedTax )
								}
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
		const { getSettings, getSettingsError, isGetSettingsRequesting } = select( 'wc-api' );

		const generalSettings = getSettings( 'general' );
		const isGeneralSettingsError = Boolean( getSettingsError( 'general' ) );
		const isGeneralSettingsRequesting = isGetSettingsRequesting( 'general' );
		const taxSettings = getSettings( 'tax' );
		const isTaxSettingsError = Boolean( getSettingsError( 'tax' ) );
		const isTaxSettingsRequesting = isGetSettingsRequesting( 'tax' );
		const countryCode = getCountryCode( generalSettings.woocommerce_default_country );
		// @todo This value should be fetched and updated via the wc-api.
		const isJetpackConnected = false;
		// @todo This should check against a list of already activated plugins and should be
		// revisited after https://github.com/woocommerce/woocommerce-admin/pull/2825 is merged.
		const pluginsToActivate = [ 'jetpack', 'woocommerce-services' ];

		return {
			countryCode,
			isGeneralSettingsError,
			isGeneralSettingsRequesting,
			generalSettings,
			isTaxSettingsError,
			isTaxSettingsRequesting,
			taxSettings,
			isJetpackConnected,
			pluginsToActivate,
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
