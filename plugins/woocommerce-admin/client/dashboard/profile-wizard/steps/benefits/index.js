/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import interpolateComponents from 'interpolate-components';
import { withDispatch } from '@wordpress/data';
import { filter, get } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { Card, H, Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import CardIcon from './images/card';
import SecurityIcon from './images/security';
import SalesTaxIcon from './images/local_atm';
import SpeedIcon from './images/flash_on';
import MobileAppIcon from './images/phone_android';
import PrintIcon from './images/print';
import withSelect from 'wc-api/with-select';
import { recordEvent } from 'lib/tracks';
import { pluginNames } from 'wc-api/onboarding/constants';

class Benefits extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			isPending: false,
		};
		this.startPluginInstall = this.startPluginInstall.bind( this );
		this.skipPluginInstall = this.skipPluginInstall.bind( this );
	}

	componentDidUpdate( prevProps ) {
		const { goToNextStep, isRequesting } = this.props;
		const { isPending } = this.state;

		if ( ! isRequesting && prevProps.isRequesting && isPending ) {
			goToNextStep();
			this.setState( { isPending: false } );
		}
	}

	async skipPluginInstall() {
		const {
			createNotice,
			isJetpackActive,
			isProfileItemsError,
			updateProfileItems,
		} = this.props;

		this.setState( { isPending: true } );

		const plugins = isJetpackActive ? 'skipped-wcs' : 'skipped';
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
	}

	async startPluginInstall() {
		const {
			isJetpackActive,
			updateProfileItems,
			updateOptions,
		} = this.props;

		this.setState( { isPending: true } );

		await updateOptions( {
			woocommerce_setup_jetpack_opted_in: true,
		} );

		const plugins = isJetpackActive ? 'installed-wcs' : 'installed';
		recordEvent( 'storeprofiler_install_plugins', {
			install: true,
			plugins,
		} );
		updateProfileItems( { plugins } );
	}

	renderBenefit( benefit ) {
		const { description, icon, title } = benefit;

		return (
			<div className="woocommerce-profile-wizard__benefit" key={ title }>
				{ icon }
				<div className="woocommerce-profile-wizard__benefit-content">
					<H className="woocommerce-profile-wizard__benefit-title">
						{ title }
					</H>
					<p>{ description }</p>
				</div>
			</div>
		);
	}

	getBenefits() {
		const { isJetpackActive, isWcsActive, tosAccepted } = this.props;
		return [
			{
				title: __( 'Security', 'woocommerce-admin' ),
				icon: <SecurityIcon />,
				description: __(
					'Jetpack automatically blocks brute force attacks to protect your store from unauthorized access.',
					'woocommerce-admin'
				),
				visible: ! isJetpackActive,
			},
			{
				title: __( 'Sales Tax', 'woocommerce-admin' ),
				icon: <SalesTaxIcon />,
				description: __(
					'With WooCommerce Services we ensure that the correct rate of tax is charged on all of your orders.',
					'woocommerce-admin'
				),
				visible: ! isWcsActive || ! tosAccepted,
			},
			{
				title: __( 'Speed', 'woocommerce-admin' ),
				icon: <SpeedIcon />,
				description: __(
					'Cache your images and static files on our own powerful global network of servers and speed up your site.',
					'woocommerce-admin'
				),
				visible: ! isJetpackActive,
			},
			{
				title: __( 'Mobile App', 'woocommerce-admin' ),
				icon: <MobileAppIcon />,
				description: __(
					'Your store in your pocket. Manage orders, receive sales notifications, and more. Only with a Jetpack connection.',
					'woocommerce-admin'
				),
				visible: ! isJetpackActive,
			},
			{
				title: __(
					'Print your own shipping labels',
					'woocommerce-admin'
				),
				icon: <PrintIcon />,
				description: __(
					'Save time at the Post Office by printing USPS shipping labels at home.',
					'woocommerce-admin'
				),
				visible: isJetpackActive || ! tosAccepted,
			},
			{
				title: __( 'Simple payment setup', 'woocommerce-admin' ),
				icon: <CardIcon />,
				description: __(
					'WooCommerce Services enables us to provision Stripe and Paypal accounts quickly and easily for you.',
					'woocommerce-admin'
				),
				visible: isJetpackActive || ! tosAccepted,
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
		const { isJetpackActive, isWcsActive } = this.props;
		const { isPending } = this.state;

		const pluginsToInstall = [];
		if ( ! isJetpackActive ) {
			pluginsToInstall.push( 'jetpack' );
		}
		if ( ! isWcsActive ) {
			pluginsToInstall.push( 'woocommerce-services' );
		}
		const pluginNamesString = pluginsToInstall
			.map( ( pluginSlug ) => pluginNames[ pluginSlug ] )
			.join( ' & ' );

		return (
			<Fragment>
				<H className="woocommerce-profile-wizard__header-title">
					{ __(
						'Start enhancing your WooCommerce store',
						'woocommerce-admin'
					) }
				</H>

				<p>
					{ interpolateComponents( {
						mixedString: sprintf(
							__(
								'Simplify and enhance the setup of your store with the free features and benefits offered by {{strong}}%s{{/strong}}.',
								'woocommerce-admin'
							),
							pluginNamesString
						),
						components: {
							strong: <strong />,
						},
					} ) }
				</p>

				<Card>
					{ this.renderBenefits() }

					<p className="woocommerce-profile-wizard__tos">
						{ interpolateComponents( {
							mixedString: __(
								'By connecting your site you agree to our fascinating {{tosLink}}Terms of Service{{/tosLink}} and to ' +
									'{{detailsLink}}share details{{/detailsLink}} with WordPress.com. ',
								'woocommerce-admin'
							),
							components: {
								tosLink: (
									<Link
										href="https://wordpress.com/tos"
										target="_blank"
										type="external"
									/>
								),
								detailsLink: (
									<Link
										href="https://jetpack.com/support/what-data-does-jetpack-sync"
										target="_blank"
										type="external"
									/>
								),
							},
						} ) }
					</p>

					<Button
						isPrimary
						isBusy={ isPending }
						onClick={ this.startPluginInstall }
						className="woocommerce-profile-wizard__continue"
					>
						{ __( 'Get started', 'woocommerce-admin' ) }
					</Button>
				</Card>

				{ pluginsToInstall.length !== 0 && (
					<p>
						<Button
							isLink
							isBusy={ isPending }
							className="woocommerce-profile-wizard__skip"
							onClick={ this.skipPluginInstall }
						>
							{ sprintf(
								__( 'Proceed without %s', 'woocommerce-admin' ),
								pluginNamesString
							) }
						</Button>
					</p>
				) }
			</Fragment>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const {
			getProfileItemsError,
			getActivePlugins,
			getOptions,
			getProfileItems,
			isGetProfileItemsRequesting,
		} = select( 'wc-api' );

		const isProfileItemsError = Boolean( getProfileItemsError() );

		const options = getOptions( [
			'woocommerce_setup_jetpack_opted_in',
			'wc_connect_options',
		] );
		const tosAccepted = get( options, [ 'wc_connect_options' ], {} )
			.tos_accepted;

		const activePlugins = getActivePlugins();
		const profileItems = getProfileItems();
		const isJetpackActive = activePlugins.includes( 'jetpack' );
		const isWcsActive = activePlugins.includes( 'woocommerce-services' );

		return {
			isJetpackActive,
			isProfileItemsError,
			isWcsActive,
			tosAccepted,
			profileItems,
			isRequesting: isGetProfileItemsRequesting(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateProfileItems, updateOptions } = dispatch( 'wc-api' );
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
			updateProfileItems,
			updateOptions,
		};
	} )
)( Benefits );
