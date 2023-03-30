/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { COUNTRIES_STORE_NAME } from '@woocommerce/data';
import { Fragment } from '@wordpress/element';
import { Form, FormContext, Spinner } from '@woocommerce/components';
import { useSelect } from '@wordpress/data';
import { Status, Options } from 'wordpress__notices';
/**
 * Internal dependencies
 */
import {
	StoreAddress,
	getStoreAddressValidator,
} from '../../../dashboard/components/settings/general/store-address';

type FormValues = {
	addressLine1: string;
	addressLine2: string;
	countryState: string;
	city: string;
	postCode: string;
};

type StoreLocationProps = {
	onComplete: ( values: FormValues ) => void;
	createNotice: (
		status: Status | undefined,
		content: string,
		options?: Partial< Options >
	) => void;
	isSettingsError: boolean;
	isSettingsRequesting: boolean;
	buttonText?: string;
	updateAndPersistSettingsForGroup: (
		group: string,
		data: {
			[ key: string ]: unknown;
		} & {
			general?: {
				[ key: string ]: string;
			};
			tax?: {
				[ key: string ]: string;
			};
		}
	) => void;
	settings?: {
		[ key: string ]: string;
	};
};

const StoreLocation = ( {
	onComplete,
	createNotice,
	isSettingsError,
	isSettingsRequesting,
	updateAndPersistSettingsForGroup,
	settings,
	buttonText = __( 'Continue', 'woocommerce' ),
}: StoreLocationProps ) => {
	const { hasFinishedResolution } = useSelect( ( select ) => {
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
	const onSubmit = async ( values: FormValues ) => {
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
		return {
			addressLine1: settings?.woocommerce_store_address || '',
			addressLine2: settings?.woocommerce_store_address_2 || '',
			city: settings?.woocommerce_store_city || '',
			countryState: settings?.woocommerce_default_country || '',
			postCode: settings?.woocommerce_store_postcode || '',
		};
	};

	const validate = ( values: FormValues ) => {
		const validator = getStoreAddressValidator();
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
			{ ( {
				getInputProps,
				handleSubmit,
				setValue,
			}: FormContext< FormValues > ) => (
				<Fragment>
					<StoreAddress
						// @ts-expect-error return type doesn't match, but they do work. We should revisit and refactor them in a follow up issue.
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
