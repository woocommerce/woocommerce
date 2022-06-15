/**
 * External dependencies
 */
import { Story, Meta } from '@storybook/react';
import {
	useValidationContext,
	ValidationContextProvider,
} from '@woocommerce/base-context';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CountryInput, CountryInputWithCountriesProps } from '..';
import { countries } from './countries-filler';

type CountryCode = keyof typeof countries;

export default {
	title: 'WooCommerce Blocks/@base-components/CountryInput',
	component: CountryInput,
	args: {
		countries,
		autoComplete: 'off',
		id: 'country',
		label: 'Countries: ',
		required: false,
	},
	argTypes: {
		countries: { control: false },
		options: { table: { disable: true } },
		value: { control: false },
	},
	decorators: [
		( StoryComponent ) => (
			<ValidationContextProvider>
				<StoryComponent />
			</ValidationContextProvider>
		),
	],
} as Meta< CountryInputWithCountriesProps >;

const Template: Story< CountryInputWithCountriesProps > = ( args ) => {
	const [ selectedCountry, selectCountry ] = useState< CountryCode | '' >(
		''
	);
	const { clearValidationError, showValidationError } =
		useValidationContext();

	useEffect( () => {
		showValidationError( 'country' );
	}, [ showValidationError ] );

	function updateCountry( country: CountryCode ) {
		clearValidationError( 'country' );
		selectCountry( country );
	}

	return (
		<CountryInput
			{ ...args }
			onChange={ ( value ) => updateCountry( value as CountryCode ) }
			value={ selectedCountry }
		/>
	);
};

export const Default = Template.bind( {} );

export const WithError = Template.bind( {} );
WithError.args = {
	errorId: 'country',
	errorMessage: 'Please select a country',
	required: true,
};
