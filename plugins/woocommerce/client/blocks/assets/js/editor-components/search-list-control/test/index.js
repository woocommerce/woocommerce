/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { SearchListControl } from '../';
import { isLatestMinusOneWordPress } from '@woocommerce/jest-wordpress-version-compat';

const noop = () => {};

const SELECTORS = {
	listItems: '.woocommerce-search-list__list > li',
	searchInput: '.components-text-control__input[type="search"]',
};

const list = [
	{ id: 1, name: 'Apricots' },
	{ id: 2, name: 'Clementine' },
	{ id: 3, name: 'Elderberry' },
	{ id: 4, name: 'Guava' },
	{ id: 5, name: 'Lychee' },
	{ id: 6, name: 'Mulberry' },
];

describe( 'SearchListControl', () => {
	test( 'should render a search box and list of options', () => {
		render(
			<SearchListControl
				instanceId={ 1 }
				list={ list }
				selected={ [] }
				onChange={ noop }
			/>
		);

		if ( isLatestMinusOneWordPress() ) {
			// wp-6.8: upstream @wordpress/* deprecation warnings that we cannot
			// opt out of without changing the visual output.
			// eslint-disable-next-line jest/no-conditional-expect
			expect( console ).toHaveWarned();
		}
	} );

	test( 'should render a search box with a search term, and only matching options, regardless of case sensitivity', () => {
		const component = render(
			<SearchListControl
				instanceId={ 1 }
				list={ list }
				selected={ [] }
				onChange={ noop }
				debouncedSpeak={ noop }
			/>
		);

		fireEvent.change(
			component.container.querySelector( SELECTORS.searchInput ),
			{ target: { value: 'BeRrY' } }
		);

		const $listItems = component.container.querySelectorAll(
			SELECTORS.listItems
		);

		expect( $listItems ).toHaveLength( 2 );
	} );

	// @see https://github.com/woocommerce/woocommerce-blocks/issues/6524
	test( "should render search results in their original case regardless of user's input case", () => {
		const EXPECTED = [ 'Elderberry', 'Mulberry' ];

		const component = render(
			<SearchListControl
				instanceId={ 1 }
				list={ list }
				selected={ [] }
				onChange={ noop }
				debouncedSpeak={ noop }
			/>
		);

		fireEvent.change(
			component.container.querySelector( SELECTORS.searchInput ),
			{ target: { value: 'BeRrY' } }
		);

		const listItems = Array.from(
			component.container.querySelectorAll( SELECTORS.listItems )
		).map( ( $el ) => $el.textContent );

		expect( listItems ).toEqual( expect.arrayContaining( EXPECTED ) );
	} );
} );
