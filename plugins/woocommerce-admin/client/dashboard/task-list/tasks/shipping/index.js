/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { filter } from 'lodash';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { Card, Stepper } from '@woocommerce/components';
import { getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import Connect from './connect';
import StoreLocation from './location';
import ShippingLabels from './labels';
import ShippingRates from './rates';
import withSelect from 'wc-api/with-select';

class Shipping extends Component {
	constructor() {
		super( ...arguments );

		this.initialState = {
			isPending: false,
			step: 'store_location',
			shippingZones: [],
		};

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
			zones.map( async zone => {
				// "Rest of the world zone"
				if ( 0 === zone.id ) {
					zone.methods = await apiFetch( { path: `/wc/v3/shipping/zones/${ zone.id }/methods` } );
					zone.name = __( 'Rest of the world', 'woocommerce-admin' );
					zone.toggleEnabled = true;
					shippingZones.push( zone );
					return;
				}

				// Return any zone with a single location matching the country zone.
				zone.locations = await apiFetch( { path: `/wc/v3/shipping/zones/${ zone.id }/locations` } );
				const countryLocation = zone.locations.find( location => countryCode === location.code );
				if ( countryLocation ) {
					zone.methods = await apiFetch( { path: `/wc/v3/shipping/zones/${ zone.id }/methods` } );
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
			woocommerce_store_address,
			woocommerce_default_country,
			woocommerce_store_postcode,
		} = settings;
		const { step } = this.state;

		if (
			'store_location' === step &&
			woocommerce_store_address &&
			woocommerce_default_country &&
			woocommerce_store_postcode
		) {
			this.completeStep();
		}

		if (
			'rates' === step &&
			( prevProps.countryCode !== countryCode || 'rates' !== prevState.step )
		) {
			this.fetchShippingZones();
		}
	}

	completeStep() {
		const { step } = this.state;
		const steps = this.getSteps();
		const currentStepIndex = steps.findIndex( s => s.key === step );
		const nextStep = steps[ currentStepIndex + 1 ];

		if ( nextStep ) {
			this.setState( { step: nextStep.key } );
		} else {
			getHistory().push( getNewPath( {}, '/', {} ) );
		}
	}

	getSteps() {
		const { countryCode } = this.props;

		const steps = [
			{
				key: 'store_location',
				label: __( 'Set store location', 'woocommerce-admin' ),
				description: __( 'The address from which your business operates', 'woocommerce-admin' ),
				content: <StoreLocation completeStep={ this.completeStep } { ...this.props } />,
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
						shippingZones={ this.state.shippingZones }
						completeStep={ this.completeStep }
						{ ...this.props }
					/>
				),
				visible: true,
			},
			{
				key: 'label_printing',
				label: __( 'Enable shipping label printing', 'woocommerce-admin' ),
				description: __(
					'With WooCommerce Services and Jetpack you can save time at the' +
						'Post Office by printing your shipping labels at home',
					'woocommerce-admin'
				),
				content: <ShippingLabels completeStep={ this.completeStep } { ...this.props } />,
				visible: [ 'US', 'GB', 'CA', 'AU' ].includes( countryCode ),
			},
			{
				key: 'connect',
				label: __( 'Connect your store', 'woocommerce-admin' ),
				description: __(
					'Connect your store to WordPress.com to enable label printing',
					'woocommerce-admin'
				),
				content: <Connect completeStep={ this.completeStep } { ...this.props } />,
				visible: 'US' === countryCode,
			},
		];

		return filter( steps, step => step.visible );
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
	withSelect( select => {
		const { getSettings, getSettingsError, isGetSettingsRequesting } = select( 'wc-api' );

		const settings = getSettings( 'general' );
		const isSettingsError = Boolean( getSettingsError( 'general' ) );
		const isSettingsRequesting = isGetSettingsRequesting( 'general' );

		const countryCode = settings.woocommerce_default_country
			? settings.woocommerce_default_country.split( ':' )[ 0 ]
			: null;
		const countries = ( wcSettings.dataEndpoints && wcSettings.dataEndpoints.countries ) || [];
		const country = countryCode ? countries.find( c => c.code === countryCode ) : null;
		const countryName = country ? country.name : null;

		return { countryCode, countryName, isSettingsError, isSettingsRequesting, settings };
	} ),
	withDispatch( dispatch => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateSettings } = dispatch( 'wc-api' );

		return {
			createNotice,
			updateSettings,
		};
	} )
)( Shipping );
