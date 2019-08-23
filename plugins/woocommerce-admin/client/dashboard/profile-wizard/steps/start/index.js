/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { FormToggle } from '@wordpress/components';
import { Button, CheckboxControl } from 'newspack-components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import interpolateComponents from 'interpolate-components';
import { withDispatch } from '@wordpress/data';
import { filter } from 'lodash';

/**
 * WooCommerce depdencies
 */
import { Card, H, Link } from '@woocommerce/components';
import { updateQueryString } from '@woocommerce/navigation';

/**
 * Internal depdencies
 */
import CardIcon from './images/card';
import SecurityIcon from './images/security';
import SalesTaxIcon from './images/local_atm';
import SpeedIcon from './images/flash_on';
import MobileAppIcon from './images/phone_android';
import PrintIcon from './images/print';
import withSelect from 'wc-api/with-select';
import { recordEvent } from 'lib/tracks';

class Start extends Component {
	constructor() {
		super( ...arguments );

		this.state = {
			allowTracking: true,
		};

		this.onTrackingChange = this.onTrackingChange.bind( this );
		this.startWizard = this.startWizard.bind( this );
		this.skipWizard = this.skipWizard.bind( this );
	}

	componentDidMount() {
		if (
			this.props.activePlugins.includes( 'jetpack' ) &&
			this.props.activePlugins.includes( 'woocommerce-services' )
		) {
			return updateQueryString( { step: 'store-details' } );
		}
	}

	async updateTracking() {
		const { updateSettings } = this.props;
		const allowTracking = this.state.allowTracking ? 'yes' : 'no';
		await updateSettings( { advanced: { woocommerce_allow_tracking: allowTracking } } );
	}

	async skipWizard() {
		const { createNotice, isProfileItemsError, updateProfileItems, isSettingsError } = this.props;

		recordEvent( 'storeprofiler_welcome_clicked', { proceed_without_install: true } );

		await updateProfileItems( { skipped: true } );
		await this.updateTracking();

		if ( isProfileItemsError || isSettingsError ) {
			createNotice(
				'error',
				__( 'There was a problem updating your preferences.', 'woocommerce-admin' )
			);
		}
	}

	async startWizard() {
		const { createNotice, isSettingsError } = this.props;

		recordEvent( 'storeprofiler_welcome_clicked', { get_started: true } );

		await this.updateTracking();

		if ( ! isSettingsError ) {
			this.props.goToNextStep();
		} else {
			createNotice(
				'error',
				__( 'There was a problem updating your preferences.', 'woocommerce-admin' )
			);
		}
	}

	onTrackingChange() {
		this.setState( {
			allowTracking: ! this.state.allowTracking,
		} );
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
		const { activePlugins } = this.props;
		return [
			{
				title: __( 'Security', 'woocommerce-admin' ),
				icon: <SecurityIcon />,
				description: __(
					'Jetpack automatically blocks brute force attacks to protect your store from unauthorized access.',
					'woocommerce-admin'
				),
				visible: ! activePlugins.includes( 'jetpack' ),
			},
			{
				title: __( 'Sales Tax', 'woocommerce-admin' ),
				icon: <SalesTaxIcon />,
				description: __(
					'With WooCommerce Services we ensure that the correct rate of tax is charged on all of your orders.',
					'woocommerce-admin'
				),
				visible: true,
			},
			{
				title: __( 'Speed', 'woocommerce-admin' ),
				icon: <SpeedIcon />,
				description: __(
					'Cache your images and static files on our own powerful global network of servers and speed up your site.',
					'woocommerce-admin'
				),
				visible: ! activePlugins.includes( 'jetpack' ),
			},
			{
				title: __( 'Mobile App', 'woocommerce-admin' ),
				icon: <MobileAppIcon />,
				description: __(
					'Your store in your pocket. Manage orders, receive sales notifications, and more. Only with a Jetpack connection.',
					'woocommerce-admin'
				),
				visible: ! activePlugins.includes( 'jetpack' ),
			},
			{
				title: __( 'Print your own shipping labels', 'woocommerce-admin' ),
				icon: <PrintIcon />,
				description: __(
					'Save time at the Post Office by printing USPS shipping labels at home.',
					'woocommerce-admin'
				),
				visible:
					activePlugins.includes( 'jetpack' ) && ! activePlugins.includes( 'woocommerce-services' ),
			},
			{
				title: __( 'Simple payment setup', 'woocommerce-admin' ),
				icon: <CardIcon />,
				description: __(
					'WooCommerce Services enables us to provision Stripe and Paypal accounts quickly and easily for you.',
					'woocommerce-admin'
				),
				visible:
					activePlugins.includes( 'jetpack' ) && ! activePlugins.includes( 'woocommerce-services' ),
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
		const { allowTracking } = this.state;
		const { activePlugins } = this.props;
		const pluginNames = activePlugins.includes( 'jetpack' )
			? __( 'WooCommerce Services', 'woocommerce-admin' )
			: __( 'Jetpack & WooCommerce Services', 'woocommerce-admin' );

		const trackingLabel = interpolateComponents( {
			mixedString: __(
				'Help improve WooCommerce with {{link}}usage tracking{{/link}}',
				'woocommerce-admin'
			),
			components: {
				link: (
					<Link href="https://woocommerce.com/usage-tracking" target="_blank" type="external" />
				),
			},
		} );

		return (
			<Fragment>
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
							pluginNames
						),
						components: {
							strong: <strong />,
						},
					} ) }
				</p>

				<Card>
					{ this.renderBenefits() }

					<div className="woocommerce-profile-wizard__tracking">
						<CheckboxControl
							className="woocommerce-profile-wizard__tracking-checkbox"
							checked={ allowTracking }
							label={ __( trackingLabel, 'woocommerce-admin' ) }
							onChange={ this.onTrackingChange }
						/>

						<FormToggle
							aria-hidden="true"
							checked={ allowTracking }
							onChange={ this.onTrackingChange }
							onClick={ e => e.stopPropagation() }
							tabIndex="-1"
						/>
					</div>

					<Button
						isPrimary
						onClick={ this.startWizard }
						className="woocommerce-profile-wizard__continue"
					>
						{ __( 'Get started', 'woocommerce-admin' ) }
					</Button>
				</Card>

				<p>
					<Button isLink className="woocommerce-profile-wizard__skip" onClick={ this.skipWizard }>
						{ sprintf( __( 'Proceed without %s', 'woocommerce-admin' ), pluginNames ) }
					</Button>
				</p>
			</Fragment>
		);
	}
}

export default compose(
	withSelect( select => {
		const {
			getProfileItemsError,
			getSettings,
			getSettingsError,
			isGetSettingsRequesting,
			getActivePlugins,
		} = select( 'wc-api' );

		const isSettingsError = Boolean( getSettingsError( 'advanced' ) );
		const isSettingsRequesting = isGetSettingsRequesting( 'advanced' );
		const isProfileItemsError = Boolean( getProfileItemsError() );

		const activePlugins = getActivePlugins();

		return {
			getSettings,
			isSettingsError,
			isProfileItemsError,
			isSettingsRequesting,
			activePlugins,
		};
	} ),
	withDispatch( dispatch => {
		const { updateProfileItems, updateSettings } = dispatch( 'wc-api' );
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
			updateProfileItems,
			updateSettings,
		};
	} )
)( Start );
