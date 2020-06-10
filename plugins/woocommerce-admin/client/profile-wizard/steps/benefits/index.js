/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { filter } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { Card, H, Plugins } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/wc-admin-settings';
import {
	pluginNames,
	ONBOARDING_STORE_NAME,
	PLUGINS_STORE_NAME,
	OPTIONS_STORE_NAME
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import Connect from 'dashboard/components/connect';
import Logo from './logo';
import ManagementIcon from './images/management';
import SalesTaxIcon from './images/sales_tax';
import ShippingLabels from './images/shipping_labels';
import SpeedIcon from './images/speed';
import { recordEvent } from 'lib/tracks';

class Benefits extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			isConnecting: false,
			isInstalling: false,
		};

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

	componentDidUpdate( prevProps, prevState ) {
		const { goToNextStep } = this.props;

		// No longer pending or updating profile items, go to next step.
		if (
			! this.isPending() &&
			( prevProps.isRequesting ||
				prevState.isConnecting ||
				prevState.isInstalling )
		) {
			goToNextStep();
		}
	}

	isPending() {
		const { isConnecting, isInstalling } = this.state;
		const { isRequesting } = this.props;
		return isConnecting || isInstalling || isRequesting;
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
		const { updateProfileItems, updateOptions } = this.props;

		this.setState( { isInstalling: true } );

		updateOptions( {
			woocommerce_setup_jetpack_opted_in: true,
		} );

		const plugins = this.isJetpackActive ? 'installed-wcs' : 'installed';
		recordEvent( 'storeprofiler_install_plugins', {
			install: true,
			plugins,
		} );
		updateProfileItems( { plugins } );
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
		const { isConnecting, isInstalling } = this.state;
		const { isJetpackConnected, isRequesting } = this.props;

		const pluginNamesString = this.pluginsToInstall
			.map( ( pluginSlug ) => pluginNames[ pluginSlug ] )
			.join( ' ' + __( 'and', 'woocommerce-admin' ) + ' ' );

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
						isBusy={
							this.isPending() && ( isInstalling || isConnecting )
						}
						disabled={ this.isPending() }
						onClick={ this.startPluginInstall }
						className="woocommerce-profile-wizard__continue"
					>
						{ __( 'Yes please!', 'woocommerce-admin' ) }
					</Button>
					<Button
						isDefault
						isBusy={
							this.isPending() && ! isInstalling && ! isConnecting
						}
						disabled={ this.isPending() }
						className="woocommerce-profile-wizard__skip"
						onClick={ this.skipPluginInstall }
					>
						{ __( 'No thanks', 'woocommerce-admin' ) }
					</Button>

					{ isInstalling && (
						<Plugins
							autoInstall
							onComplete={ () =>
								this.setState( {
									isInstalling: false,
									isConnecting: ! isJetpackConnected,
								} )
							}
							onError={ () =>
								this.setState( {
									isInstalling: false,
								} )
							}
							pluginSlugs={ this.pluginsToInstall }
						/>
					) }

					{ /* Make sure we're finished requesting since this will auto redirect us. */ }
					{ isConnecting && ! isJetpackConnected && ! isRequesting && (
						<Connect
							autoConnect
							onConnect={ () => {
								recordEvent(
									'storeprofiler_jetpack_connect_redirect'
								);
							} }
							onError={ () =>
								this.setState( { isConnecting: false } )
							}
							redirectUrl={ getAdminLink(
								'admin.php?page=wc-admin&reset_profiler=0'
							) }
						/>
					) }
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

		const { getActivePlugins, isJetpackConnected } = select(
			PLUGINS_STORE_NAME
		);

		const isProfileItemsError = Boolean(
			getOnboardingError( 'updateProfileItems' )
		);
		const activePlugins = getActivePlugins();
		const profileItems = getProfileItems();

		return {
			activePlugins,
			isProfileItemsError,
			profileItems,
			isJetpackConnected: isJetpackConnected(),
			isRequesting: isOnboardingRequesting( 'updateProfileItems' ),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateProfileItems } = dispatch( ONBOARDING_STORE_NAME );
		const { updateOptions } = dispatch( OPTIONS_STORE_NAME );
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
			updateProfileItems,
			updateOptions,
		};
	} )
)( Benefits );
