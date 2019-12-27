/** @format */
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
import { updateQueryString } from '@woocommerce/navigation';

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
import UsageModal from '../usage-modal';
import { recordEvent } from 'lib/tracks';
import { pluginNames } from 'wc-api/onboarding/constants';

class Start extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			showUsageModal: false,
			continueAction: '',
		};
		this.startWizard = this.startWizard.bind( this );
		this.skipWizard = this.skipWizard.bind( this );
	}

	componentDidMount() {
		const { updateProfileItems, profileItems, tosAccepted, isJetpackConnected } = this.props;
		if (
			isJetpackConnected &&
			this.props.activePlugins.includes( 'woocommerce-services' ) &&
			tosAccepted
		) {
			// Don't track event again if they revisit the start page.
			if ( 'already-installed' !== profileItems.plugins ) {
				recordEvent( 'wcadmin_storeprofiler_already_installed_plugins', {} );
			}

			updateProfileItems( { plugins: 'already-installed' } );
			return updateQueryString( { step: 'store-details' } );
		}
	}

	async skipWizard() {
		const {
			createNotice,
			isProfileItemsError,
			updateProfileItems,
			isJetpackConnected,
		} = this.props;

		const plugins = isJetpackConnected ? 'skipped-wcs' : 'skipped';
		await updateProfileItems( { plugins } );

		if ( isProfileItemsError ) {
			createNotice(
				'error',
				__( 'There was a problem updating your preferences.', 'woocommerce-admin' )
			);
		} else {
			recordEvent( 'storeprofiler_welcome_clicked', { get_started: true, plugins } );
			return updateQueryString( { step: 'store-details' } );
		}
	}

	async startWizard() {
		const {
			createNotice,
			isProfileItemsError,
			updateProfileItems,
			updateOptions,
			goToNextStep,
			isJetpackConnected,
		} = this.props;

		await updateOptions( {
			woocommerce_setup_jetpack_opted_in: true,
		} );

		const plugins = isJetpackConnected ? 'installed-wcs' : 'installed';
		await updateProfileItems( { plugins } );

		if ( ! isProfileItemsError ) {
			recordEvent( 'storeprofiler_welcome_clicked', { get_started: true, plugins } );
			goToNextStep();
		} else {
			createNotice(
				'error',
				__( 'There was a problem updating your preferences.', 'woocommerce-admin' )
			);
		}
	}

	renderBenefit( benefit ) {
		const { description, icon, title } = benefit;

		return (
			<div className="woocommerce-profile-wizard__benefit" key={ title }>
				{ icon }
				<div className="woocommerce-profile-wizard__benefit-content">
					<H className="woocommerce-profile-wizard__benefit-title">{ title }</H>
					<p>{ description }</p>
				</div>
			</div>
		);
	}

	getBenefits() {
		const { activePlugins, isJetpackConnected, tosAccepted } = this.props;
		return [
			{
				title: __( 'Security', 'woocommerce-admin' ),
				icon: <SecurityIcon />,
				description: __(
					'Jetpack automatically blocks brute force attacks to protect your store from unauthorized access.',
					'woocommerce-admin'
				),
				visible: ! isJetpackConnected,
			},
			{
				title: __( 'Sales Tax', 'woocommerce-admin' ),
				icon: <SalesTaxIcon />,
				description: __(
					'With WooCommerce Services we ensure that the correct rate of tax is charged on all of your orders.',
					'woocommerce-admin'
				),
				visible: ! activePlugins.includes( 'woocommerce-services' ) || ! tosAccepted,
			},
			{
				title: __( 'Speed', 'woocommerce-admin' ),
				icon: <SpeedIcon />,
				description: __(
					'Cache your images and static files on our own powerful global network of servers and speed up your site.',
					'woocommerce-admin'
				),
				visible: ! isJetpackConnected,
			},
			{
				title: __( 'Mobile App', 'woocommerce-admin' ),
				icon: <MobileAppIcon />,
				description: __(
					'Your store in your pocket. Manage orders, receive sales notifications, and more. Only with a Jetpack connection.',
					'woocommerce-admin'
				),
				visible: ! isJetpackConnected,
			},
			{
				title: __( 'Print your own shipping labels', 'woocommerce-admin' ),
				icon: <PrintIcon />,
				description: __(
					'Save time at the Post Office by printing USPS shipping labels at home.',
					'woocommerce-admin'
				),
				visible: isJetpackConnected || ! tosAccepted,
			},
			{
				title: __( 'Simple payment setup', 'woocommerce-admin' ),
				icon: <CardIcon />,
				description: __(
					'WooCommerce Services enables us to provision Stripe and Paypal accounts quickly and easily for you.',
					'woocommerce-admin'
				),
				visible: isJetpackConnected || ! tosAccepted,
			},
		];
	}

	renderBenefits() {
		return (
			<div className="woocommerce-profile-wizard__benefits">
				{ filter( this.getBenefits(), benefit => benefit.visible ).map( benefit =>
					this.renderBenefit( benefit )
				) }
			</div>
		);
	}

	render() {
		const { showUsageModal, continueAction } = this.state;
		const { isJetpackConnected, activePlugins } = this.props;

		const pluginsToInstall = [];
		if ( ! isJetpackConnected ) {
			pluginsToInstall.push( 'jetpack' );
		}
		if ( ! activePlugins.includes( 'woocommerce-services' ) ) {
			pluginsToInstall.push( 'woocommerce-services' );
		}
		const pluginNamesString = pluginsToInstall
			.map( pluginSlug => pluginNames[ pluginSlug ] )
			.join( ' & ' );

		return (
			<Fragment>
				{ showUsageModal && (
					<UsageModal
						onContinue={ () =>
							'wizard' === continueAction ? this.startWizard() : this.skipWizard()
						}
						onClose={ () => this.setState( { showUsageModal: false, continueAction: '' } ) }
					/>
				) }
				<H className="woocommerce-profile-wizard__header-title">
					{ __( 'Start setting up your WooCommerce store', 'woocommerce-admin' ) }
				</H>

				<p>
					{ interpolateComponents( {
						mixedString: sprintf(
							__(
								'Simplify and enhance the setup of your store with the free features and benefits offered by ' +
									'{{strong}}%s{{/strong}}.',
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
								tosLink: <Link href="https://wordpress.com/tos" target="_blank" type="external" />,
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
						onClick={ () => this.setState( { showUsageModal: true, continueAction: 'wizard' } ) }
						className="woocommerce-profile-wizard__continue"
					>
						{ __( 'Get started', 'woocommerce-admin' ) }
					</Button>
				</Card>

				{ 0 !== pluginsToInstall.length && (
					<p>
						<Button
							isLink
							className="woocommerce-profile-wizard__skip"
							onClick={ () => this.setState( { showUsageModal: true, continueAction: 'skip' } ) }
						>
							{ sprintf( __( 'Proceed without %s', 'woocommerce-admin' ), pluginNamesString ) }
						</Button>
					</p>
				) }
			</Fragment>
		);
	}
}

export default compose(
	withSelect( select => {
		const {
			getProfileItemsError,
			getActivePlugins,
			getOptions,
			getProfileItems,
			isJetpackConnected,
		} = select( 'wc-api' );

		const isProfileItemsError = Boolean( getProfileItemsError() );

		const options = getOptions( [ 'woocommerce_setup_jetpack_opted_in', 'wc_connect_options' ] );
		const tosAccepted = get( options, [ 'wc_connect_options' ], {} ).tos_accepted;

		const activePlugins = getActivePlugins();
		const profileItems = getProfileItems();

		return {
			isProfileItemsError,
			activePlugins,
			tosAccepted,
			profileItems,
			isJetpackConnected: isJetpackConnected(),
		};
	} ),
	withDispatch( dispatch => {
		const { updateProfileItems, updateOptions } = dispatch( 'wc-api' );
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
			updateProfileItems,
			updateOptions,
		};
	} )
)( Start );
