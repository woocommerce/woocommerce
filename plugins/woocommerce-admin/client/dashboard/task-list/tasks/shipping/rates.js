/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import classnames from 'classnames';
import { Component, Fragment } from '@wordpress/element';
import { Button, FormToggle } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { Flag, Form, TextControl } from '@woocommerce/components';
import { CURRENCY, getSetting, setSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import { getCurrencyFormatString } from 'lib/currency-format';
import { recordEvent } from 'lib/tracks';

const { symbol, symbolPosition } = CURRENCY;

class ShippingRates extends Component {
	constructor() {
		super( ...arguments );

		this.updateShippingZones = this.updateShippingZones.bind( this );
	}

	async updateShippingZones( values ) {
		const { createNotice, shippingZones } = this.props;

		let restOfTheWorld = false;
		let shippingCost = false;
		shippingZones.map( zone => {
			if ( 0 === zone.id ) {
				restOfTheWorld = zone.toggleEnabled && values[ `${ zone.id }_enabled` ];
			} else {
				shippingCost =
					'' !== values[ `${ zone.id }_rate` ] &&
					parseFloat( values[ `${ zone.id }_rate` ] ) !== parseFloat( 0 );
			}

			const flatRateMethods = zone.methods
				? zone.methods.filter( method => 'flat_rate' === method.method_id )
				: [];

			if ( zone.toggleEnabled && ! values[ `${ zone.id }_enabled` ] ) {
				// Disable any flat rate methods that exist if toggled off.
				if ( flatRateMethods.length ) {
					flatRateMethods.map( method => {
						apiFetch( {
							method: 'POST',
							path: `/wc/v3/shipping/zones/${ zone.id }/methods/${ method.instance_id }`,
							data: {
								enabled: false,
							},
						} );
					} );
				}
				return;
			}

			if ( flatRateMethods.length ) {
				// Update the existing method.
				apiFetch( {
					method: 'POST',
					path: `/wc/v3/shipping/zones/${ zone.id }/methods/${ flatRateMethods[ 0 ].instance_id }`,
					data: {
						enabled: true,
						settings: { cost: values[ `${ zone.id }_rate` ] },
					},
				} );
			} else {
				// Add new method if one doesn't exist.
				apiFetch( {
					method: 'POST',
					path: `/wc/v3/shipping/zones/${ zone.id }/methods`,
					data: {
						method_id: 'flat_rate',
						settings: { cost: values[ `${ zone.id }_rate` ] },
					},
				} );
			}
		} );

		recordEvent( 'tasklist_shipping_set_costs', {
			shipping_cost: shippingCost,
			rest_world: restOfTheWorld,
		} );

		// @todo This is a workaround to force the task to mark as complete.
		// This should probably be updated to use wc-api so we can fetch shipping methods.
		setSetting( 'onboarding', {
			...getSetting( 'onboarding', {} ),
			shippingZonesCount: 1,
		} );

		createNotice( 'success', __( 'Your shipping rates have been updated.', 'woocommerce-admin' ) );

		this.props.onComplete();
	}

	renderInputPrefix() {
		if ( 0 === symbolPosition.indexOf( 'right' ) ) {
			return;
		}
		return <span className="woocommerce-shipping-rate__control-prefix">{ symbol }</span>;
	}

	renderInputSuffix( rate ) {
		if ( 0 === symbolPosition.indexOf( 'right' ) ) {
			return <span className="woocommerce-shipping-rate__control-suffix">{ symbol }</span>;
		}

		return parseFloat( rate ) === parseFloat( 0 ) ? (
			<span className="woocommerce-shipping-rate__control-suffix">
				{ __( 'Free shipping', 'woocommerce-admin' ) }
			</span>
		) : null;
	}

	getInitialValues() {
		const values = {};

		this.props.shippingZones.forEach( zone => {
			const flatRateMethods =
				zone.methods && zone.methods.length
					? zone.methods.filter( method => 'flat_rate' === method.method_id )
					: [];
			const rate = flatRateMethods.length
				? flatRateMethods[ 0 ].settings.cost.value
				: getCurrencyFormatString( 0 );
			values[ `${ zone.id }_rate` ] = rate;

			if ( flatRateMethods.length && flatRateMethods[ 0 ].enabled ) {
				values[ `${ zone.id }_enabled` ] = true;
			} else {
				values[ `${ zone.id }_enabled` ] = false;
			}
		} );

		return values;
	}

	validate( values ) {
		const errors = {};

		const rates = Object.keys( values ).filter( field => field.endsWith( '_rate' ) );

		rates.forEach( rate => {
			if ( values[ rate ] < 0 ) {
				errors[ rate ] = __( 'Shipping rates can not be negative numbers.', 'woocommerce-admin' );
			}
		} );

		return errors;
	}

	render() {
		const { buttonText, shippingZones } = this.props;

		if ( ! shippingZones.length ) {
			return null;
		}

		return (
			<Form
				initialValues={ this.getInitialValues() }
				onSubmitCallback={ this.updateShippingZones }
				validate={ this.validate }
			>
				{ ( { getInputProps, handleSubmit, setTouched, setValue, values } ) => {
					return (
						<Fragment>
							<div className="woocommerce-shipping-rates">
								{ shippingZones.map( zone => (
									<div className="woocommerce-shipping-rate" key={ zone.id }>
										<div className="woocommerce-shipping-rate__icon">
											{ zone.locations ? (
												zone.locations.map( location => (
													<Flag size={ 24 } code={ location.code } key={ location.code } />
												) )
											) : (
												// Icon used for zones without locations or "Rest of the world".
												<i className="material-icons-outlined">public</i>
											) }
										</div>
										<div className="woocommerce-shipping-rate__main">
											<div className="woocommerce-shipping-rate__name">
												{ zone.name }
												{ zone.toggleEnabled && (
													<FormToggle { ...getInputProps( `${ zone.id }_enabled` ) } />
												) }
											</div>
											{ ( ! zone.toggleEnabled || values[ `${ zone.id }_enabled` ] ) && (
												<div
													className={ classnames( 'woocommerce-shipping-rate__control-wrapper', {
														'has-value': values[ `${ zone.id }_rate` ],
													} ) }
												>
													{ this.renderInputPrefix() }
													<TextControl
														label={ __( 'Shipping cost', 'woocommerce-admin' ) }
														required
														{ ...getInputProps( `${ zone.id }_rate` ) }
														onBlur={ () => {
															setTouched( `${ zone.id }_rate` );
															setValue(
																`${ zone.id }_rate`,
																getCurrencyFormatString( values[ `${ zone.id }_rate` ] )
															);
														} }
													/>
													{ this.renderInputSuffix( values[ `${ zone.id }_rate` ] ) }
												</div>
											) }
										</div>
									</div>
								) ) }
							</div>

							<Button isPrimary onClick={ handleSubmit }>
								{ buttonText || __( 'Update', 'woocommerce-admin' ) }
							</Button>
						</Fragment>
					);
				} }
			</Form>
		);
	}
}

ShippingRates.propTypes = {
	/**
	 * Text displayed on the primary button.
	 */
	buttonText: PropTypes.string,
	/**
	 * Function used to mark the step complete.
	 */
	onComplete: PropTypes.func.isRequired,
	/**
	 * Function to create a transient notice in the store.
	 */
	createNotice: PropTypes.func.isRequired,
	/**
	 * Array of shipping zones returned from the WC REST API with added
	 * `methods` and `locations` properties appended from separate API calls.
	 */
	shippingZones: PropTypes.array,
};

ShippingRates.defaultProps = {
	shippingZones: [],
};

export default ShippingRates;
