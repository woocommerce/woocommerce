/** @format */
/**
 * External dependencies
 */
import { mount } from 'enzyme';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Autocomplete } from '../index';

describe( 'Autocomplete', () => {
	const optionClassname = 'woocommerce-autocomplete__option';
	const query = 'lorem';
	const options = [
		{ key: '1', label: 'lorem 1', value: { id: '1' } },
		{ key: '2', label: 'lorem 2', value: { id: '2' } },
		{ key: '3', label: 'bar', value: { id: '3' } },
	];

	it( 'returns matching elements', () => {
		const autocomplete = mount(
			<Autocomplete
				options={ options }
			/>
		);
		autocomplete.setState( {
			query,
		} );

		autocomplete.instance().search( query );
		autocomplete.update();

		expect( autocomplete.find( Button ).filter( '.' + optionClassname ).length ).toBe( 2 );
	} );

	it( 'doesn\'t return matching excluded elements', () => {
		const autocomplete = mount(
			<Autocomplete
				options={ options }
				selected={ [ options[ 1 ] ] }
				excludeSelectedOptions
				multiple
			/>
		);
		autocomplete.setState( {
			query,
		} );

		autocomplete.instance().search( query );
		autocomplete.update();

		expect( autocomplete.find( Button ).filter( '.' + optionClassname ).length ).toBe( 1 );
	} );

	it( 'trims spaces from input', () => {
		const autocomplete = mount(
			<Autocomplete
				options={ options }
			/>
		);
		autocomplete.setState( {
			query,
		} );

		autocomplete.instance().search( '    ' + query + ' ' );
		autocomplete.update();

		expect( autocomplete.find( Button ).filter( '.' + optionClassname ).length ).toBe( 2 );
	} );

	it( 'limits results', () => {
		const autocomplete = mount(
			<Autocomplete
				options={ options }
				maxResults={ 1 }
			/>
		);
		autocomplete.setState( {
			query,
		} );

		autocomplete.instance().search( query );
		autocomplete.update();

		expect( autocomplete.find( Button ).filter( '.' + optionClassname ).length ).toBe( 1 );
	} );

	it( 'shows options initially', () => {
		const autocomplete = mount(
			<Autocomplete
				options={ options }
			/>
		);

		autocomplete.instance().search( '' );
		autocomplete.update();

		expect( autocomplete.find( Button ).filter( '.' + optionClassname ).length ).toBe( 3 );
	} );

	it( 'shows options after query', () => {
		const autocomplete = mount(
			<Autocomplete
				options={ options }
				hideBeforeSearch
			/>
		);

		autocomplete.instance().search( '' );
		autocomplete.update();

		expect( autocomplete.find( Button ).filter( '.' + optionClassname ).length ).toBe( 0 );

		autocomplete.instance().search( query );
		autocomplete.update();

		expect( autocomplete.find( Button ).filter( '.' + optionClassname ).length ).toBe( 2 );
	} );

	it( 'appends an option after filtering', () => {
		const autocomplete = mount(
			<Autocomplete
				options={ options }
				onFilter={ ( filteredOptions ) => filteredOptions.concat( [ { key: 'new-option', label: 'New options' } ] ) }
			/>
		);

		autocomplete.instance().search( query );
		autocomplete.update();

		expect( autocomplete.find( Button ).filter( '.' + optionClassname ).length ).toBe( 3 );
	} );

	it( 'changes the options on search', () => {
		const queriedOptions = [];
		const queryOptions = ( searchedQuery ) => {
			if ( searchedQuery === 'test' ) {
				queriedOptions.push( { key: 'test-option', label: 'Test option' } );
			}
		};
		const autocomplete = mount(
			<Autocomplete
				options={ queriedOptions }
				onSearch={ queryOptions }
				onFilter={ () => queriedOptions }
			/>
		);

		autocomplete.instance().search( '' );
		autocomplete.update();

		expect( autocomplete.find( Button ).filter( '.' + optionClassname ).length ).toBe( 0 );

		autocomplete.instance().search( 'test' );
		autocomplete.update();

		expect( autocomplete.find( Button ).filter( '.' + optionClassname ).length ).toBe( 1 );
	} );
} );
