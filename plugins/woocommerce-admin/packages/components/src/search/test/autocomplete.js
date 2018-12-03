/** @format */
/**
 * External dependencies
 */
import { mount } from 'enzyme';

/**
 * Internal dependencies
 */
import { Autocomplete } from '../autocomplete';

describe( 'Autocomplete', () => {
	it( 'doesn\'t return matching excluded elements', () => {
		const suggestionClassname = 'autocomplete-result';
		const search = 'lorem';
		const options = [
			{ key: '1', label: 'lorem 1', value: { id: '1' } },
			{ key: '2', label: 'lorem 2', value: { id: '2' } },
		];
		const exclude = [ '2' ];
		const autocomplete = mount(
			<Autocomplete
				children={ () => null }
				className={ suggestionClassname }
				completer={ {} }
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

		expect( autocomplete.find( '.' + suggestionClassname ).length ).toBe( 1 );
	} );
} );
