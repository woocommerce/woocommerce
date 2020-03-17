/**
 * External dependencies
 */
import { text } from '@storybook/addon-knobs';
import { useState, useEffect } from '@wordpress/element';
import {
	ValidationContextProvider,
	useValidationContext,
} from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import { CountryInput } from '../';
import { countries as exampleCountries } from './countries-filler';

export default {
	title: 'WooCommerce Blocks/@base-components/CountryInput',
	component: CountryInput,
};

const StoryComponent = ( { label, errorMessage } ) => {
	const [ selectedCountry, selectCountry ] = useState();
	const {
		setValidationErrors,
		clearValidationError,
	} = useValidationContext();
	useEffect( () => {
		setValidationErrors( { country: errorMessage } );
	}, [ errorMessage ] );
	const updateCountry = ( country ) => {
		clearValidationError( 'country' );
		selectCountry( country );
	};
	return (
		<CountryInput
			countries={ exampleCountries }
			label={ label }
			value={ selectedCountry }
			onChange={ updateCountry }
		/>
	);
};

export const Default = () => {
	const label = text( 'Input Label', 'Countries:' );
	const errorMessage = text( 'Error Message', '' );
	return (
		<ValidationContextProvider>
			<StoryComponent label={ label } errorMessage={ errorMessage } />
		</ValidationContextProvider>
	);
};
