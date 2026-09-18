/*
 * @jest-environment-options {"url": "http://woo.local/"}
 */

/**
 * External dependencies
 */
import { render, screen, waitFor } from '@testing-library/react';
import { dispatch, select } from '@wordpress/data';
import { QUERY_STATE_STORE_KEY } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import PriceFilterBlock from '../block';
import { Attributes } from '../types';

// The default query-state context a filter uses when no collection block
// provides one.
const QUERY_CONTEXT = 'page';

// A two-decimal currency priced from $5 to $90, so the $15 to $40 range in the
// URL sits strictly inside the constraints the slider derives from it.
const mockPriceRange = {
	currency_code: 'USD',
	currency_symbol: '$',
	currency_minor_unit: 2,
	currency_decimal_separator: '.',
	currency_thousand_separator: ',',
	currency_prefix: '$',
	currency_suffix: '',
	min_price: '500',
	max_price: '9000',
};

// When the price range reaches the block. A cold Store API request answers one
// render after mount, which is the default; a page whose collection data is
// already loaded answers on the first render.
let mockPriceRangeOnFirstRender = false;

jest.mock( '@woocommerce/base-context/hooks', () => ( {
	__esModule: true,
	...jest.requireActual( '@woocommerce/base-context/hooks' ),
	useCollectionData: () => {
		const { useEffect, useState } =
			jest.requireActual( '@wordpress/element' );
		const [ hasResponded, setHasResponded ] = useState(
			mockPriceRangeOnFirstRender
		);

		useEffect( () => {
			setHasResponded( true );
		}, [] );

		return hasResponded
			? { data: { price_range: mockPriceRange }, isLoading: false }
			: { data: {}, isLoading: true };
	},
} ) );

const attributes: Attributes = {
	showFilterButton: true,
	showInputFields: true,
	inlineInput: true,
	heading: 'Filter by price',
	headingLevel: 3,
};

/*
 * jsdom (>= 21) makes `window.location` non-configurable, so navigate via the
 * History API instead of replacing the object. Same-origin only (see the
 * `@jest-environment-options` url above).
 */
const renderBlockAt = ( url: string ) => {
	window.history.replaceState( {}, '', url );

	return render(
		<PriceFilterBlock attributes={ attributes } isEditor={ false } />
	);
};

const getMinPriceInput = () =>
	screen.getByRole( 'textbox', {
		name: 'Filter products by minimum price',
	} );

const getMaxPriceInput = () =>
	screen.getByRole( 'textbox', {
		name: 'Filter products by maximum price',
	} );

const getPriceQueryValue = ( queryKey: 'min_price' | 'max_price' ) =>
	select( QUERY_STATE_STORE_KEY ).getValueForQueryKey(
		QUERY_CONTEXT,
		queryKey
	);

// Captured before any test navigates, so each test starts from the env URL.
const initialUrl = window.location.href;

beforeEach( () => {
	mockPriceRangeOnFirstRender = false;
	// The query state store outlives a single render, so clear the context the
	// tests write to.
	dispatch( QUERY_STATE_STORE_KEY ).setValueForQueryContext(
		QUERY_CONTEXT,
		{}
	);
} );

afterEach( () => {
	window.history.replaceState( {}, '', initialUrl );
} );

describe( 'Filter by Price block', () => {
	test( 'takes the price range in the URL into the query state and the slider', async () => {
		renderBlockAt( 'http://woo.local/?min_price=15&max_price=40' );

		// The seeding effect runs inside render()'s act, so the query state is
		// already written here. It carries minor units, so $15 and $40 are 1500
		// and 4000.
		expect( getPriceQueryValue( 'min_price' ) ).toBe( 1500 );
		expect( getPriceQueryValue( 'max_price' ) ).toBe( 4000 );

		await waitFor( () => {
			expect( getMinPriceInput() ).toHaveValue( '$15' );
			expect( getMaxPriceInput() ).toHaveValue( '$40' );
		} );
	} );

	test( 'writes no numeric price into the query state and shows the full range when the URL carries no prices', async () => {
		renderBlockAt( 'http://woo.local/' );

		// The block writes both keys unconditionally, with a non-numeric value
		// the store serialises to null.
		expect( getPriceQueryValue( 'min_price' ) ).toBeNull();
		expect( getPriceQueryValue( 'max_price' ) ).toBeNull();

		await waitFor( () => {
			expect( getMinPriceInput() ).toHaveValue( '$5' );
			expect( getMaxPriceInput() ).toHaveValue( '$90' );
		} );
	} );

	test( 'keeps the slider at the collection constraints when the price range is already there on the first render', async () => {
		mockPriceRangeOnFirstRender = true;

		renderBlockAt( 'http://woo.local/?min_price=15&max_price=40' );

		// The query state still takes the URL, but the slider does not: the
		// effect that matches the slider to the query state runs once with the
		// constraints already in hand and before the URL values land, and
		// nothing brings it back afterwards.
		expect( getPriceQueryValue( 'min_price' ) ).toBe( 1500 );
		expect( getPriceQueryValue( 'max_price' ) ).toBe( 4000 );
		expect( getMinPriceInput() ).toHaveValue( '$5' );
		expect( getMaxPriceInput() ).toHaveValue( '$90' );
	} );
} );
