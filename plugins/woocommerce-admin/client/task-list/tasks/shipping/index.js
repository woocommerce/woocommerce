/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { difference, filter } from 'lodash';
import interpolateComponents from 'interpolate-components';
import { withDispatch, withSelect } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { Card, Link, Stepper, Plugins } from '@woocommerce/components';
import { getAdminLink, getSetting } from '@woocommerce/wc-admin-settings';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { SETTINGS_STORE_NAME, PLUGINS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import Connect from 'dashboard/components/connect';
import { getCountryCode } from 'dashboard/utils';
import StoreLocation from '../steps/location';
import ShippingRates from './rates';
import { recordEvent } from 'lib/tracks';

class Shipping extends Component {
	constructor( props ) {
		super( props );

		this.initialState = {
			isPending: false,
			step: 'store_location',
			shippingZones: [],
		};

		// Cache active plugins to prevent removal mid-step.
		this.activePlugins = props.activePlugins;
		this.state = this.initialState;
		this.completeStep = this.completeStep.bind( this );
	}

	componentDidMount() {
		this.reset();
	}

	reset() {
		this.setState( this.initialState );
	}

	async fetchShippingZones() {
		this.setState( { isPending: true } );
		const { countryCode, countryName } = this.props;

		// @todo The following fetches for shipping information should be moved into
		// the wc-api to make these methods and states more readily available.
		const shippingZones = [];
		const zones = await apiFetch( { path: '/wc/v3/shipping/zones' } );
		let hasCountryZone = false;

		await Promise.all(
			zones.map( async ( zone ) => {
				// "Rest of the world zone"
				if ( zone.id === 0 ) {
					zone.methods = await apiFetch( {
						path: `/wc/v3/shipping/zones/${ zone.id }/methods`,
					} );
					zone.name = __( 'Rest of the world', 'woocommerce-admin' );
					zone.toggleable = true;
					shippingZones.push( zone );
					return;
				}

				// Return any zone with a single location matching the country zone.
				zone.locations = await apiFetch( {
					path: `/wc/v3/shipping/zones/${ zone.id }/locations`,
				} );
				const countryLocation = zone.locations.find(
					( location ) => countryCode === location.code
				);
				if ( countryLocation ) {
					zone.methods = await apiFetch( {
						path: `/wc/v3/shipping/zones/${ zone.id }/methods`,
					} );
					shippingZones.push( zone );
					hasCountryZone = true;
				}
			} )
		);

		// Create the default store country zone if it doesn't exist.
		if ( ! hasCountryZone ) {
			const zone = await apiFetch( {
				method: 'POST',
				path: '/wc/v3/shipping/zones',
				data: { name: countryName },
			} );
			zone.locations = await apiFetch( {
				method: 'POST',
				path: `/wc/v3/shipping/zones/${ zone.id }/locations`,
				data: [ { code: countryCode, type: 'country' } ],
			} );
			shippingZones.push( zone );
		}

		shippingZones.reverse();

		this.setState( { isPending: false, shippingZones } );
	}

	componentDidUpdate( prevProps, prevState ) {
		const { countryCode } = this.props;
		const { step } = this.state;

		if (
			step === 'rates' &&
			( prevProps.countryCode !== countryCode ||
				prevState.step !== 'rates' )
		) {
			this.fetchShippingZones();
		}
	}

	completeStep() {
		const { createNotice } = this.props;
		const { step } = this.state;
		const steps = this.getSteps();
		const currentStepIndex = steps.findIndex( ( s ) => s.key === step );
		const nextStep = steps[ currentStepIndex + 1 ];

		if ( nextStep ) {
			this.setState( { step: nextStep.key } );
		} else {
			createNotice(
				'success',
				__(
					"📦 Shipping is done! Don't worry, you can always change it later.",
					'woocommerce-admin'
				)
			);
			getHistory().push( getNewPath( {}, '/', {} ) );
		}
	}

	getPluginsToActivate() {
		const { countryCode } = this.props;

		const plugins = [];
		if ( [ 'GB', 'CA', 'AU' ].includes( countryCode ) ) {
			plugins.push( 'woocommerce-shipstation-integration' );
		} else if ( countryCode === 'US' ) {
			plugins.push( 'woocommerce-services' );
			plugins.push( 'jetpack' );
		}
		return difference( plugins, this.activePlugins );
	}

	getSteps() {
		const { countryCode, isJetpackConnected } = this.props;
		const pluginsToActivate = this.getPluginsToActivate();
		const requiresJetpackConnection =
			! isJetpackConnected && countryCode === 'US';

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
							recordEvent( 'tasklist_shipping_set_location', {
								country,
							} );
							this.completeStep();
						} }
					/>
				),
				visible: true,
			},
			{
				key: 'rates',
				label: __( 'Set shipping costs', 'woocommerce-admin' ),
				description: __(
					'Define how much customers pay to ship to different destinations',
					'woocommerce-admin'
				),
				content: (
					<ShippingRates
						buttonText={
							pluginsToActivate.length ||
							requiresJetpackConnection
								? __( 'Proceed', 'woocommerce-admin' )
								: __( 'Complete task', 'woocommerce-admin' )
						}
						shippingZones={ this.state.shippingZones }
						onComplete={ this.completeStep }
						{ ...this.props }
					/>
				),
				visible: true,
			},
			{
				key: 'label_printing',
				label: __(
					'Enable shipping label printing',
					'woocommerce-admin'
				),
				description: pluginsToActivate.includes(
					'woocommerce-shipstation-integration'
				)
					? interpolateComponents( {
							mixedString: __(
								'We recommend using ShipStation to save time at the post office by printing your shipping ' +
									'labels at home. Try ShipStation free for 30 days. {{link}}Learn more{{/link}}.',
								'woocommerce-admin'
							),
							components: {
								link: (
									<Link
										href="https://woocommerce.com/products/shipstation-integration"
										target="_blank"
										type="external"
									/>
								),
							},
					  } )
					: __(
							'With WooCommerce Services and Jetpack you can save time at the ' +
								'Post Office by printing your shipping labels at home',
							'woocommerce-admin'
					  ),
				content: (
					<Plugins
						onComplete={ () => {
							recordEvent( 'tasklist_shipping_label_printing', {
								install: true,
								pluginsToActivate,
							} );
							this.completeStep();
						} }
						onSkip={ () => {
							recordEvent( 'tasklist_shipping_label_printing', {
								install: false,
								pluginsToActivate,
							} );
							getHistory().push( getNewPath( {}, '/', {} ) );
						} }
						pluginSlugs={ pluginsToActivate }
						{ ...this.props }
					/>
				),
				visible: pluginsToActivate.length,
			},
			{
				key: 'connect',
				label: __( 'Connect your store', 'woocommerce-admin' ),
				description: __(
					'Connect your store to WordPress.com to enable label printing',
					'woocommerce-admin'
				),
				content: (
					<Connect
						redirectUrl={ getAdminLink(
							'admin.php?page=wc-admin'
						) }
						completeStep={ this.completeStep }
						{ ...this.props }
						onConnect={ () => {
							recordEvent( 'tasklist_shipping_connect_store' );
						} }
					/>
				),
				visible: requiresJetpackConnection,
			},
		];

		return filter( steps, ( step ) => step.visible );
	}

	render() {
		const { isPending, step } = this.state;
		const { isSettingsRequesting } = this.props;

		return (
			<div className="woocommerce-task-shipping">
				<Card className="is-narrow">
					<Stepper
						isPending={ isPending || isSettingsRequesting }
						isVertical
						currentStep={ step }
						steps={ this.getSteps() }
					/>
				</Card>
			</div>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const {
			getSettings,
			getSettingsError,
			isGetSettingsRequesting,
		} = select( SETTINGS_STORE_NAME );
		const { getActivePlugins, isJetpackConnected } = select(
			PLUGINS_STORE_NAME
		);

		const { general: settings = {} } = getSettings( 'general' );
		const isSettingsError = Boolean( getSettingsError( 'general' ) );
		const isSettingsRequesting = isGetSettingsRequesting( 'general' );

		const countryCode = getCountryCode(
			settings.woocommerce_default_country
		);

		const { countries = [] } = getSetting( 'dataEndpoints', {} );
		const country = countryCode
			? countries.find( ( c ) => c.code === countryCode )
			: null;
		const countryName = country ? country.name : null;
		const activePlugins = getActivePlugins();

		return {
			countryCode,
			countryName,
			isSettingsError,
			isSettingsRequesting,
			settings,
			activePlugins,
			isJetpackConnected: isJetpackConnected(),
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
)( Shipping );
