/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button, Card, CardBody, CardFooter } from '@wordpress/components';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { filter } from 'lodash';
import interpolateComponents from 'interpolate-components';
import { H, Link } from '@woocommerce/components';
import {
	pluginNames,
	ONBOARDING_STORE_NAME,
	PLUGINS_STORE_NAME,
	OPTIONS_STORE_NAME,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import { createNoticesFromResponse } from '../../../lib/notices';
import Logo from './logo';
import ManagementIcon from './images/management';
import SalesTaxIcon from './images/sales_tax';
import ShippingLabels from './images/shipping_labels';
import SpeedIcon from './images/speed';

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
		const { createNotice, goToNextStep, isProfileItemsError } = this.props;

		const plugins = this.isJetpackActive ? 'skipped-wcs' : 'skipped';

		if ( isProfileItemsError ) {
			createNotice(
				'error',
				__(
					'There was a problem updating your preferences',
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
			updateOptions,
		} = this.props;
		const plugins = this.isJetpackActive ? 'installed-wcs' : 'installed';

		recordEvent( 'storeprofiler_install_plugins', {
			install: true,
			plugins,
		} );

		Promise.all( [
			installAndActivatePlugins( this.pluginsToInstall ),
			updateOptions( {
				woocommerce_setup_jetpack_opted_in: true,
			} ),
		] )
			.then( goToNextStep )
			.catch( ( pluginError, profileError ) => {
				if ( pluginError ) {
					createNoticesFromResponse( pluginError );
				}
				if ( profileError ) {
					createNotice(
						'error',
						__(
							'There was a problem updating your preferences',
							'woocommerce-admin'
						)
					);
				}
				goToNextStep();
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
			isUpdatingProfileItems,
		} = this.props;

		const pluginNamesString = this.pluginsToInstall
			.map( ( pluginSlug ) => pluginNames[ pluginSlug ] )
			.join( ' ' + __( 'and', 'woocommerce-admin' ) + ' ' );
		const pluginsRemaining = this.pluginsToInstall.filter(
			( plugin ) => ! activePlugins.includes( plugin )
		);
		const isInstallAction =
			isInstallingActivating || ! pluginsRemaining.length;
		const isAcceptingTos = ! this.isWcsActive;
		const pluralizedPlugins = _n(
			'plugin',
			'plugins',
			this.pluginsToInstall.length,
			'woocommerce-admin'
		);

		return (
			<Card className="woocommerce-profile-wizard__benefits-card">
				<CardBody justify="center">
					<Logo />
					<div className="woocommerce-profile-wizard__step-header">
						<Text variant="title.small" as="h2">
							{ sprintf(
								__(
									'Enhance your store with %s',
									'woocommerce-admin'
								),
								pluginNamesString
							) }
						</Text>
					</div>

					{ this.renderBenefits() }
				</CardBody>
				<CardFooter isBorderless justify="center">
					<Button
						isPrimary
						isBusy={ isInstallAction }
						disabled={ isUpdatingProfileItems || isInstallAction }
						onClick={ this.startPluginInstall }
					>
						{ __( 'Yes please!', 'woocommerce-admin' ) }
					</Button>
					<Button
						isSecondary
						isBusy={ isUpdatingProfileItems && ! isInstallAction }
						disabled={ isUpdatingProfileItems || isInstallAction }
						className="woocommerce-profile-wizard__skip"
						onClick={ this.skipPluginInstall }
					>
						{ __( 'No thanks', 'woocommerce-admin' ) }
					</Button>
				</CardFooter>

				<CardFooter isBorderless justify="center">
					<p className="woocommerce-profile-wizard__benefits-install-notice">
						{ isAcceptingTos
							? interpolateComponents( {
									mixedString: sprintf(
										__(
											'%s %s will be installed & activated for free, and you agree to our {{link}}Terms of Service{{/link}}.',
											'woocommerce-admin'
										),
										pluginNamesString,
										pluralizedPlugins
									),
									components: {
										link: (
											<Link
												href="https://wordpress.com/tos/"
												target="_blank"
												type="external"
											/>
										),
									},
							  } )
							: sprintf(
									__(
										'%s %s will be installed & activated for free.',
										'woocommerce-admin'
									),
									pluginNamesString,
									pluralizedPlugins
							  ) }
					</p>
				</CardFooter>
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

		const { getActivePlugins, isPluginsRequesting } = select(
			PLUGINS_STORE_NAME
		);

		return {
			activePlugins: getActivePlugins(),
			isProfileItemsError: Boolean(
				getOnboardingError( 'updateProfileItems' )
			),
			profileItems: getProfileItems(),
			isUpdatingProfileItems: isOnboardingRequesting(
				'updateProfileItems'
			),
			isInstallingActivating:
				isPluginsRequesting( 'installPlugins' ) ||
				isPluginsRequesting( 'activatePlugins' ) ||
				isPluginsRequesting( 'getJetpackConnectUrl' ),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { installAndActivatePlugins } = dispatch( PLUGINS_STORE_NAME );
		const { updateOptions } = dispatch( OPTIONS_STORE_NAME );
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
			installAndActivatePlugins,
			updateOptions,
		};
	} )
)( Benefits );
