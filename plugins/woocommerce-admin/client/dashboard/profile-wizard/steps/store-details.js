/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, SelectControl, TextControl, CheckboxControl } from 'newspack-components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { decodeEntities } from '@wordpress/html-entities';
import { pickBy } from 'lodash';
import { withDispatch } from '@wordpress/data';
import { recordEvent } from 'lib/tracks';

/**
 * Internal depdencies
 */
import { H, Card } from '@woocommerce/components';
import withSelect from 'wc-api/with-select';

class StoreDetails extends Component {
	constructor() {
		super( ...arguments );

		this.state = {
			countryStateOptions: [],
			errors: {},
			fields: {
				addressLine1: '',
				addressLine2: '',
				city: '',
				countryState: '',
				postCode: '',
				isClient: false,
			},
		};

		this.onContinue = this.onContinue.bind( this );
		this.updateValue = this.updateValue.bind( this );
	}

	componentWillMount() {
		const countryStateOptions = this.getCountryStateOptions();
		this.setState( { countryStateOptions } );
	}

	validateField( name ) {
		const { errors, fields } = this.state;

		switch ( name ) {
			case 'addressLine1':
				errors.addressLine1 = fields.addressLine1.length
					? null
					: __( 'Please add an address', 'woocommerce-admin' );
				break;
			case 'countryState':
				errors.countryState = fields.countryState.length
					? null
					: __( 'Please select a country and state', 'woocommerce-admin' );
				break;
			case 'city':
				errors.city = fields.city.length ? null : __( 'Please add a city', 'woocommerce-admin' );
				break;
			case 'postCode':
				errors.postCode = fields.postCode.length
					? null
					: __( 'Please add a post code', 'woocommerce-admin' );
				break;
		}

		this.setState( { errors: pickBy( errors ) } );
	}

	updateValue( name, value ) {
		const fields = { ...this.state.fields, [ name ]: value };
		this.setState( { fields }, () => this.validateField( name ) );
	}

	async validateForm() {
		const { fields } = this.state;
		Object.keys( fields ).forEach( fieldName => this.validateField( fieldName ) );
	}

	async onContinue() {
		await this.validateForm();
		if ( Object.keys( this.state.errors ).length ) {
			return;
		}

		const {
			createNotice,
			goToNextStep,
			isSettingsError,
			updateSettings,
			updateProfileItems,
			isProfileItemsError,
		} = this.props;
		const {
			addressLine1,
			addressLine2,
			city,
			countryState,
			postCode,
			isClient,
		} = this.state.fields;

		recordEvent( 'storeprofiler_store_details_continue', {
			store_country: countryState.split( ':' )[ 0 ],
			setup_client: isClient,
		} );

		await updateSettings( {
			general: {
				woocommerce_store_address: addressLine1,
				woocommerce_store_address_2: addressLine2,
				woocommerce_default_country: countryState,
				woocommerce_store_city: city,
				woocommerce_store_postcode: postCode,
			},
		} );

		await updateProfileItems( { setup_client: isClient } );

		if ( ! isSettingsError && ! isProfileItemsError ) {
			goToNextStep();
		} else {
			createNotice(
				'error',
				__( 'There was a problem saving your store details.', 'woocommerce-admin' )
			);
		}
	}

	getCountryStateOptions() {
		const countries = ( wcSettings.dataEndpoints && wcSettings.dataEndpoints.countries ) || [];

		const countryStateOptions = countries.reduce( ( acc, country ) => {
			if ( ! country.states.length ) {
				acc.push( {
					value: country.code,
					label: decodeEntities( country.name ),
				} );

				return acc;
			}

			const countryStates = country.states.map( state => {
				return {
					value: country.code + ':' + state.code,
					label: decodeEntities( country.name ) + ' -- ' + decodeEntities( state.name ),
				};
			} );

			acc.push( ...countryStates );

			return acc;
		}, [] );

		countryStateOptions.unshift( { value: '', label: '' } );

		return countryStateOptions;
	}

	render() {
		const { countryStateOptions, errors, fields } = this.state;
		const { addressLine1, addressLine2, city, countryState, postCode } = fields;

		return (
			<Fragment>
				<H className="woocommerce-profile-wizard__header-title">
					{ __( 'Store Details', 'woocommerce-admin' ) }
				</H>
				<H className="woocommerce-profile-wizard__header-subtitle">
					{ __( 'Tell us about your store', 'woocommerce-admin' ) }
				</H>

				<Card>
					<TextControl
						label={ __( 'Address line 1', 'woocommerce-admin' ) }
						onChange={ value => this.updateValue( 'addressLine1', value ) }
						required
						value={ addressLine1 }
						help={ errors.addressLine1 }
						className={ errors.addressLine1 ? 'has-error' : null }
					/>

					<TextControl
						label={ __( 'Address line 2 (optional)', 'woocommerce-admin' ) }
						onChange={ value => this.updateValue( 'addressLine2', value ) }
						required
						value={ addressLine2 }
						help={ errors.addressLine2 }
						className={ errors.addressLine2 ? 'has-error' : null }
					/>

					<SelectControl
						label={ __( 'Country / State', 'woocommerce-admin' ) }
						onChange={ value => this.updateValue( 'countryState', value ) }
						options={ countryStateOptions }
						value={ countryState }
						required
						help={ errors.countryState }
						className={ errors.countryState ? 'has-error' : null }
					/>

					<TextControl
						label={ __( 'City', 'woocommerce-admin' ) }
						onChange={ value => this.updateValue( 'city', value ) }
						required
						value={ city }
						help={ errors.city }
						className={ errors.city ? 'has-error' : null }
					/>

					<TextControl
						label={ __( 'Post code', 'woocommerce-admin' ) }
						onChange={ value => this.updateValue( 'postCode', value ) }
						required
						value={ postCode }
						help={ errors.postCode }
						className={ errors.postCode ? 'has-error' : null }
					/>

					<CheckboxControl
						label={ __( 'This store is being set up for a client', 'woocommerce-admin' ) }
						onChange={ value => this.updateValue( 'isClient', value ) }
					/>

					<Button isPrimary onClick={ this.onContinue }>
						{ __( 'Continue', 'woocommerce-admin' ) }
					</Button>
				</Card>
			</Fragment>
		);
	}
}

export default compose(
	withSelect( select => {
		const { getSettings, getSettingsError, isGetSettingsRequesting, getProfileItemsError } = select(
			'wc-api'
		);

		const settings = getSettings( 'general' );
		const isSettingsError = Boolean( getSettingsError( 'general' ) );
		const isSettingsRequesting = isGetSettingsRequesting( 'general' );
		const isProfileItemsError = Boolean( getProfileItemsError() );

		return { getSettings, isProfileItemsError, isSettingsError, isSettingsRequesting, settings };
	} ),
	withDispatch( dispatch => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateSettings, updateProfileItems } = dispatch( 'wc-api' );

		return {
			createNotice,
			updateSettings,
			updateProfileItems,
		};
	} )
)( StoreDetails );
