/**
 * External dependencies
 */
import { shallow } from 'enzyme';

/**
 * Internal dependencies
 */
import { Search } from '../index';
import { computeSuggestionMatch } from '../autocompleters/utils';

describe( 'Search', () => {
	it( 'shows the free text search option', () => {
		const search = shallow(
			<Search type="products" allowFreeTextSearch />
		);
		const options = search
			.instance()
			.appendFreeTextSearch( [], 'Product Query' );

		expect( options.length ).toBe( 1 );
	} );

	it( "doesn't show options with an empty search", () => {
		const search = shallow(
			<Search type="products" allowFreeTextSearch />
		);
		const options = search.instance().appendFreeTextSearch( [], '' );

		expect( options.length ).toBe( 0 );
	} );

	it( 'returns an object with decoded text', () => {
		const decodedText = computeSuggestionMatch(
			'A test &amp; a &#116;&#101;&#115;&#116;',
			'test'
		);
		const expected =
			'{"suggestionBeforeMatch":"A ","suggestionMatch":"test","suggestionAfterMatch":" & a test"}';
		expect( JSON.stringify( decodedText ) ).toBe( expected );
	} );
} );
