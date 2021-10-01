/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Component } from '@wordpress/element';
import { Card, CardBody } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { difference, filter } from 'lodash';
import interpolateComponents from 'interpolate-components';
import { withDispatch, withSelect } from '@wordpress/data';
import { Link, Stepper, Plugins } from '@woocommerce/components';
import { getAdminLink, getSetting } from '@woocommerce/wc-admin-settings';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { SETTINGS_STORE_NAME, PLUGINS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { registerPlugin } from '@wordpress/plugins';
import { WooOnboardingTask } from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import Connect from '../../../dashboard/components/connect';
import { getCountryCode } from '../../../dashboard/utils';
import StoreLocation from '../steps/location';
import ShippingRates from './rates';
import { createNoticesFromResponse } from '../../../lib/notices';

export class Shipping extends Component {
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
		// @woocommerce/data to make these methods and states more readily available.
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
		const { countryCode, settings } = this.props;
		const {
			woocommerce_store_address: storeAddress,
			woocommerce_default_country: defaultCountry,
			woocommerce_store_postcode: storePostCode,
		} = settings;
		const { step } = this.state;

		if (
			step === 'rates' &&
			( prevProps.countryCode !== countryCode ||
				prevState.step !== 'rates' )
		) {
			this.fetchShippingZones();
		}

		const isCompleteAddress = Boolean(
			storeAddress && defaultCountry && storePostCode
		);

		if ( step === 'store_location' && isCompleteAddress ) {
			this.completeStep();
		}
	}

	completeStep() {
		const { createNotice, onComplete } = this.props;
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
					"📦 Shipping is done! Don't worry, you can always change it later",
					'woocommerce-admin'
				)
			);
			onComplete();
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
		const { countryCode, isJetpackConnected, settings } = this.props;
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
				visible:
					settings.woocommerce_ship_to_countries === 'disabled'
						? false
						: true,
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
										href="https://woocommerce.com/products/shipstation-integration?utm_medium=product"
										target="_blank"
										type="external"
									/>
								),
							},
					  } )
					: __(
							'With WooCommerce Shipping you can save time ' +
								'by printing your USPS and DHL Express shipping labels at home',
							'woocommerce-admin'
					  ),
				content: (
					<Plugins
						onComplete={ ( plugins, response ) => {
							createNoticesFromResponse( response );
							recordEvent( 'tasklist_shipping_label_printing', {
								install: true,
								plugins_to_activate: pluginsToActivate,
							} );
							this.completeStep();
						} }
						onError={ ( errors, response ) =>
							createNoticesFromResponse( response )
						}
						onSkip={ () => {
							recordEvent( 'tasklist_shipping_label_printing', {
								install: false,
								plugins_to_activate: pluginsToActivate,
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
		const { isUpdateSettingsRequesting } = this.props;

		return (
			<div className="woocommerce-task-shipping">
				<Card className="woocommerce-task-card">
					<CardBody>
						<Stepper
							isPending={
								isPending || isUpdateSettingsRequesting
							}
							isVertical
							currentStep={ step }
							steps={ this.getSteps() }
						/>
					</CardBody>
				</Card>
			</div>
		);
	}
}

const ShippingWrapper = compose(
	withSelect( ( select ) => {
		const { getSettings, isUpdateSettingsRequesting } = select(
			SETTINGS_STORE_NAME
		);
		const { getActivePlugins, isJetpackConnected } = select(
			PLUGINS_STORE_NAME
		);

		const { general: settings = {} } = getSettings( 'general' );
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
			isUpdateSettingsRequesting: isUpdateSettingsRequesting( 'general' ),
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

registerPlugin( 'wc-admin-onboarding-task-shipping', {
	scope: 'woocommerce-tasks',
	render: () => (
		<WooOnboardingTask id="shipping">
			{ ( { onComplete } ) => {
				return <ShippingWrapper onComplete={ onComplete } />;
			} }
		</WooOnboardingTask>
	),
} );
