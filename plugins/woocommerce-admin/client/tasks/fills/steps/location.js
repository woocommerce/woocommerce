/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { COUNTRIES_STORE_NAME } from '@woocommerce/data';
import { Fragment } from '@wordpress/element';
import { Form, Spinner } from '@woocommerce/components';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	StoreAddress,
	getStoreAddressValidator,
} from '../../../dashboard/components/settings/general/store-address';

const StoreLocation = ( {
	onComplete,
	createNotice,
	isSettingsError,
	isSettingsRequesting,
	updateAndPersistSettingsForGroup,
	settings,
	buttonText = __( 'Continue', 'woocommerce' ),
} ) => {
	const { getLocale, hasFinishedResolution } = useSelect( ( select ) => {
		const countryStore = select( COUNTRIES_STORE_NAME );
		countryStore.getCountries();
		return {
			getLocale: countryStore.getLocale,
			locales: countryStore.getLocales(),
			hasFinishedResolution:
				countryStore.hasFinishedResolution( 'getLocales' ) &&
				countryStore.hasFinishedResolution( 'getCountries' ),
		};
	} );
	const onSubmit = async ( values ) => {
		await updateAndPersistSettingsForGroup( 'general', {
			general: {
				...settings,
				woocommerce_store_address: values.addressLine1,
				woocommerce_store_address_2: values.addressLine2,
				woocommerce_default_country: values.countryState,
				woocommerce_store_city: values.city,
				woocommerce_store_postcode: values.postCode,
			},
		} );

		if ( ! isSettingsError ) {
			onComplete( values );
		} else {
			createNotice(
				'error',
				__(
					'There was a problem saving your store location',
					'woocommerce'
				)
			);
		}
	};

	const getInitialValues = () => {
		const {
			woocommerce_store_address: storeAddress,
			woocommerce_store_address_2: storeAddress2,
			woocommerce_store_city: storeCity,
			woocommerce_default_country: defaultCountry,
			woocommerce_store_postcode: storePostcode,
		} = settings;

		return {
			addressLine1: storeAddress || '',
			addressLine2: storeAddress2 || '',
			city: storeCity || '',
			countryState: defaultCountry || '',
			postCode: storePostcode || '',
		};
	};

	const validate = ( values ) => {
		const locale = getLocale( values.countryState );
		const validator = getStoreAddressValidator( locale );
		return validator( values );
	};

	if ( isSettingsRequesting || ! hasFinishedResolution ) {
		return <Spinner />;
	}

	return (
		<Form
			initialValues={ getInitialValues() }
			onSubmit={ onSubmit }
			validate={ validate }
		>
			{ ( { getInputProps, handleSubmit, setValue } ) => (
				<Fragment>
					<StoreAddress
						getInputProps={ getInputProps }
						setValue={ setValue }
					/>
					<Button isPrimary onClick={ handleSubmit }>
						{ buttonText }
					</Button>
				</Fragment>
			) }
		</Form>
	);
};

export default StoreLocation;
