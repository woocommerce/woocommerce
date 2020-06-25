/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import {
	withDispatch,
	withSelect,
	__experimentalResolveSelect,
} from '@wordpress/data';
import { filter } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { Card, H } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/wc-admin-settings';
import {
	pluginNames,
	ONBOARDING_STORE_NAME,
	PLUGINS_STORE_NAME,
	OPTIONS_STORE_NAME,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { createNoticesFromResponse } from 'lib/notices';
import Logo from './logo';
import ManagementIcon from './images/management';
import SalesTaxIcon from './images/sales_tax';
import ShippingLabels from './images/shipping_labels';
import SpeedIcon from './images/speed';
import { recordEvent } from 'lib/tracks';

class Benefits extends Component {
	constructor( props ) {
		super( props );

		this.isJetpackActive = props.activePlugins.includes( 'jetpack' );
		this.isWcsActive = props.activePlugins.includes(
			'woocommerce-services'
		);
		this.pluginsToInstall = [];
		if ( ! this.isJetpackActive ) {
			this.pluginsToInstall.push( 'jetpack' );
		}
		if ( ! this.isWcsActive ) {
			this.pluginsToInstall.push( 'woocommerce-services' );
		}

		recordEvent( 'storeprofiler_plugins_to_install', {
			plugins: this.pluginsToInstall,
		} );

		this.startPluginInstall = this.startPluginInstall.bind( this );
		this.skipPluginInstall = this.skipPluginInstall.bind( this );
	}

	async skipPluginInstall() {
		const {
			createNotice,
			goToNextStep,
			isProfileItemsError,
			updateProfileItems,
		} = this.props;

		const plugins = this.isJetpackActive ? 'skipped-wcs' : 'skipped';
		await updateProfileItems( { plugins } );

		if ( isProfileItemsError ) {
			createNotice(
				'error',
				__(
					'There was a problem updating your preferences.',
					'woocommerce-admin'
				)
			);
		} else {
			recordEvent( 'storeprofiler_install_plugins', {
				install: false,
				plugins,
			} );
		}

		goToNextStep();
	}

	startPluginInstall() {
		const {
			createNotice,
			goToNextStep,
			installAndActivatePlugins,
			updateProfileItems,
			updateOptions,
		} = this.props;
		const plugins = this.isJetpackActive ? 'installed-wcs' : 'installed';

		recordEvent( 'storeprofiler_install_plugins', {
			install: true,
			plugins,
		} );

		Promise.all( [
			installAndActivatePlugins( this.pluginsToInstall ),
			updateProfileItems( { plugins } ),
			updateOptions( {
				woocommerce_setup_jetpack_opted_in: true,
			} ),
		] )
			.then( () => this.connectJetpack() )
			.catch( ( pluginError, profileError ) => {
				if ( pluginError ) {
					createNoticesFromResponse( pluginError );
				}
				if ( profileError ) {
					createNotice(
						'error',
						__(
							'There was a problem updating your preferences.',
							'woocommerce-admin'
						)
					);
				}
				goToNextStep();
			} );
	}

	connectJetpack() {
		const {
			getJetpackConnectUrl,
			getPluginsError,
			goToNextStep,
			isJetpackConnected,
		} = this.props;
		if ( isJetpackConnected ) {
			goToNextStep();
			return;
		}

		getJetpackConnectUrl( {
			redirect_url: getAdminLink(
				'admin.php?page=wc-admin&reset_profiler=0'
			),
		} ).then( ( url ) => {
			const error = getPluginsError( 'getJetpackConnectUrl' );
			if ( error ) {
				createNoticesFromResponse( error );
				goToNextStep();
				return;
			}
			window.location = url;
		} );
	}

	renderBenefit( benefit ) {
		const { description, icon, title } = benefit;

		return (
			<div
				className="woocommerce-profile-wizard__benefit-card"
				key={ title }
			>
				{ icon }
				<div className="woocommerce-profile-wizard__benefit-card-content">
					<H className="woocommerce-profile-wizard__benefit-card-title">
						{ title }
					</H>
					<p>{ description }</p>
				</div>
			</div>
		);
	}

	getBenefits() {
		return [
			{
				title: __( 'Store management on the go', 'woocommerce-admin' ),
				icon: <ManagementIcon />,
				description: __(
					'Your store in your pocket. Manage orders, receive sales notifications, and more. Only with a Jetpack connection.',
					'woocommerce-admin'
				),
				visible: ! this.isJetpackActive,
			},
			{
				title: __( 'Automated sales taxes', 'woocommerce-admin' ),
				icon: <SalesTaxIcon />,
				description: __(
					'Ensure that the correct rate of tax is charged on all of your orders automatically, and print shipping labels at home.',
					'woocommerce-admin'
				),
				visible: ! this.isWcsActive || ! this.isJetpackActive,
			},
			{
				title: __( 'Improved speed & security', 'woocommerce-admin' ),
				icon: <SpeedIcon />,
				description: __(
					'Automatically block brute force attacks and speed up your store using our powerful, global server network to cache images.',
					'woocommerce-admin'
				),
				visible: ! this.isJetpackActive,
			},
			{
				title: __(
					'Print shipping labels at home',
					'woocommerce-admin'
				),
				icon: <ShippingLabels />,
				description: __(
					'Save time at the post office by printing shipping labels for your orders at home.',
					'woocommerce-admin'
				),
				visible: this.isJetpackActive && ! this.isWcsActive,
			},
		];
	}

	renderBenefits() {
		return (
			<div className="woocommerce-profile-wizard__benefits">
				{ filter(
					this.getBenefits(),
					( benefit ) => benefit.visible
				).map( ( benefit ) => this.renderBenefit( benefit ) ) }
			</div>
		);
	}

	render() {
		const {
			activePlugins,
			isInstallingActivating,
			isRequesting,
		} = this.props;

		const pluginNamesString = this.pluginsToInstall
			.map( ( pluginSlug ) => pluginNames[ pluginSlug ] )
			.join( ' ' + __( 'and', 'woocommerce-admin' ) + ' ' );
		const pluginsRemaining = this.pluginsToInstall.filter(
			( plugin ) => ! activePlugins.includes( plugin )
		);
		const isInstallAction =
			isInstallingActivating || ! pluginsRemaining.length;

		return (
			<Card className="woocommerce-profile-wizard__benefits-card">
				<Logo />
				<H className="woocommerce-profile-wizard__header-title">
					{ sprintf(
						__( 'Enhance your store with %s', 'woocommerce-admin' ),
						pluginNamesString
					) }
				</H>

				{ this.renderBenefits() }

				<div className="woocommerce-profile-wizard__card-actions">
					<Button
						isPrimary
						isBusy={ isInstallAction }
						disabled={ isRequesting || isInstallAction }
						onClick={ this.startPluginInstall }
					>
						{ __( 'Yes please!', 'woocommerce-admin' ) }
					</Button>
					<Button
						isSecondary
						isBusy={ isRequesting && ! isInstallAction }
						disabled={ isRequesting || isInstallAction }
						className="woocommerce-profile-wizard__skip"
						onClick={ this.skipPluginInstall }
					>
						{ __( 'No thanks', 'woocommerce-admin' ) }
					</Button>
				</div>

				<p className="woocommerce-profile-wizard__benefits-install-notice">
					{ sprintf(
						__(
							'%s %s will be installed & activated for free.',
							'woocommerce-admin'
						),
						pluginNamesString,
						_n(
							'plugin',
							'plugins',
							this.pluginsToInstall.length,
							'woocommerce-admin'
						)
					) }
				</p>
			</Card>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const {
			getOnboardingError,
			getProfileItems,
			isOnboardingRequesting,
		} = select( ONBOARDING_STORE_NAME );

		const {
			getActivePlugins,
			getPluginsError,
			isJetpackConnected,
			isPluginsRequesting,
		} = select( PLUGINS_STORE_NAME );

		return {
			activePlugins: getActivePlugins(),
			getJetpackConnectUrl: __experimentalResolveSelect(
				PLUGINS_STORE_NAME
			).getJetpackConnectUrl,
			getPluginsError,
			isProfileItemsError: Boolean(
				getOnboardingError( 'updateProfileItems' )
			),
			profileItems: getProfileItems(),
			isJetpackConnected: isJetpackConnected(),
			isRequesting: isOnboardingRequesting( 'updateProfileItems' ),
			isInstallingActivating:
				isPluginsRequesting( 'installPlugins' ) ||
				isPluginsRequesting( 'activatePlugins' ) ||
				isPluginsRequesting( 'getJetpackConnectUrl' ),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { installAndActivatePlugins } = dispatch( PLUGINS_STORE_NAME );
		const { updateProfileItems } = dispatch( ONBOARDING_STORE_NAME );
		const { updateOptions } = dispatch( OPTIONS_STORE_NAME );
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
			installAndActivatePlugins,
			updateProfileItems,
			updateOptions,
		};
	} )
)( Benefits );
