/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, SelectControl, TextControl, CheckboxControl } from 'newspack-components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { decodeEntities } from '@wordpress/html-entities';
import { withDispatch } from '@wordpress/data';
import { recordEvent } from 'lib/tracks';

/**
 * Internal depdencies
 */
import { H, Card, Form } from '@woocommerce/components';
import withSelect from 'wc-api/with-select';

class StoreDetails extends Component {
	constructor() {
		super( ...arguments );

		this.state = {
			countryStateOptions: [],
		};

		this.initialValues = {
			addressLine1: '',
			addressLine2: '',
			city: '',
			countryState: '',
			postCode: '',
			isClient: false,
		};

		this.onContinue = this.onContinue.bind( this );
	}

	componentWillMount() {
		const countryStateOptions = this.getCountryStateOptions();
		this.setState( { countryStateOptions } );
	}

	validate( values ) {
		const errors = {};

		if ( ! values.addressLine1.length ) {
			errors.addressLine1 = __( 'Please add an address', 'woocommerce-admin' );
		}
		if ( ! values.countryState.length ) {
			errors.countryState = __( 'Please select a country and state', 'woocommerce-admin' );
		}
		if ( ! values.city.length ) {
			errors.city = __( 'Please add a city', 'woocommerce-admin' );
		}
		if ( ! values.postCode.length ) {
			errors.postCode = __( 'Please add a post code', 'woocommerce-admin' );
		}

		return errors;
	}

	async onContinue( values ) {
		const {
			createNotice,
			goToNextStep,
			isSettingsError,
			updateSettings,
			updateProfileItems,
			isProfileItemsError,
		} = this.props;

		recordEvent( 'storeprofiler_store_details_continue', {
			store_country: values.countryState.split( ':' )[ 0 ],
			setup_client: values.isClient,
		} );

		await updateSettings( {
			general: {
				woocommerce_store_address: values.addressLine1,
				woocommerce_store_address_2: values.addressLine2,
				woocommerce_default_country: values.countryState,
				woocommerce_store_city: values.city,
				woocommerce_store_postcode: values.postCode,
			},
		} );

		await updateProfileItems( { setup_client: values.isClient } );

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
		const { countryStateOptions } = this.state;

		return (
			<Fragment>
				<H className="woocommerce-profile-wizard__header-title">
					{ __( 'Where is your store based?', 'woocommerce-admin' ) }
				</H>
				<H className="woocommerce-profile-wizard__header-subtitle">
					{ __(
						'This will help us configure your store and get you started quickly',
						'woocommerce-admin'
					) }
				</H>

				<Card>
					<Form
						initialValues={ this.initialValues }
						onSubmitCallback={ this.onContinue }
						validate={ this.validate }
					>
						{ ( { getInputProps, handleSubmit } ) => (
							<Fragment>
								<TextControl
									label={ __( 'Address line 1', 'woocommerce-admin' ) }
									required
									{ ...getInputProps( 'addressLine1' ) }
								/>

								<TextControl
									label={ __( 'Address line 2 (optional)', 'woocommerce-admin' ) }
									required
									{ ...getInputProps( 'addressLine2' ) }
								/>

								<SelectControl
									label={ __( 'Country / State', 'woocommerce-admin' ) }
									required
									options={ countryStateOptions }
									{ ...getInputProps( 'countryState' ) }
								/>

								<TextControl
									label={ __( 'City', 'woocommerce-admin' ) }
									required
									{ ...getInputProps( 'city' ) }
								/>

								<TextControl
									label={ __( 'Post code', 'woocommerce-admin' ) }
									required
									{ ...getInputProps( 'postCode' ) }
								/>

								<CheckboxControl
									label={ __( "I'm setting up a store for a client", 'woocommerce-admin' ) }
									{ ...getInputProps( 'isClient' ) }
								/>

								<Button isPrimary onClick={ handleSubmit }>
									{ __( 'Continue', 'woocommerce-admin' ) }
								</Button>
							</Fragment>
						) }
					</Form>
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
