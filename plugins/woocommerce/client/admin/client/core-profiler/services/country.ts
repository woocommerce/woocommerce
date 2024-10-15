/**
 * External dependencies
 */
import { Country } from '@woocommerce/data';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Type definitions
 */
export type CountryStateOption = {
	key: string;
	label: string;
};

/**
 * Get all country and state combinations used for select dropdowns.
 *
 * @return {Object} Select options, { value: 'US:GA', label: 'United States - Georgia' }
 * @param  countries {Country[]} Countries object.
 */
export function getCountryStateOptions(
	countries: Country[]
): CountryStateOption[] {
	const countryStateOptions: CountryStateOption[] = countries.reduce(
		( acc, country ) => {
			if ( ! country.states.length ) {
				acc.push( {
					key: country.code,
					label: decodeEntities( country.name ),
				} );
				return acc;
			}

			const countryStates = country.states.map( ( state ) => {
				return {
					key: country.code + ':' + state.code,
					label:
						decodeEntities( country.name ) +
						' — ' +
						decodeEntities( state.name ),
				};
			} );
			acc.push( ...countryStates );

			return acc;
		},
		[] as CountryStateOption[]
	);

	return countryStateOptions;
}
