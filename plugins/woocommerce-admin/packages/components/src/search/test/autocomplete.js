/** @format */
/**
 * External dependencies
 */
import { mount } from 'enzyme';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Autocomplete } from '../autocomplete';

describe( 'Autocomplete', () => {
	const suggestionClassname = 'autocomplete-result';
	const search = 'lorem';
	const options = [
		{ key: '1', label: 'lorem 1', value: { id: '1' } },
		{ key: '2', label: 'lorem 2', value: { id: '2' } },
		{ key: '3', label: 'bar', value: { id: '3' } },
	];

	it( 'returns matching elements', () => {
		const autocomplete = mount(
			<Autocomplete
				children={ () => null }
				completer={ {
					className: suggestionClassname,
				} }
			/>
		);
		autocomplete.setState( {
			options,
			query: {},
			search,
		} );

		autocomplete.instance().search( { target: { value: search } } );
		autocomplete.update();

		expect( autocomplete.find( Button ).filter( '.' + suggestionClassname ).length ).toBe( 2 );
	} );

	it( 'doesn\'t return matching excluded elements', () => {
		const exclude = [ '2' ];
		const autocomplete = mount(
			<Autocomplete
				children={ () => null }
				completer={ {
					className: suggestionClassname,
				} }
				selected={ exclude }
			/>
		);
		autocomplete.setState( {
			options,
			query: {},
			search,
		} );

		autocomplete.instance().search( { target: { value: search } } );
		autocomplete.update();

		expect( autocomplete.find( Button ).filter( '.' + suggestionClassname ).length ).toBe( 1 );
	} );

	it( 'trims spaces from input', () => {
		const autocomplete = mount(
			<Autocomplete
				children={ () => null }
				completer={ {
					className: suggestionClassname,
				} }
			/>
		);
		autocomplete.setState( {
			options,
			query: {},
			search,
		} );

		autocomplete.instance().search( { target: { value: '    ' + search + ' ' } } );
		autocomplete.update();

		expect( autocomplete.find( Button ).filter( '.' + suggestionClassname ).length ).toBe( 2 );
	} );
} );
