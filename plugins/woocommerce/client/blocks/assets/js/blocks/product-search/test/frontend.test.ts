jest.mock( '@wordpress/api-fetch', () => jest.fn() );

/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { act, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { attach } from '../frontend';

const product = ( name: string ) => ( {
	name,
	permalink: `https://example.com/product/${ name.toLowerCase() }`,
	images: [],
	prices: {
		price: '123456',
		currency_code: 'EUR',
		currency_symbol: '€',
		currency_minor_unit: 2,
		currency_decimal_separator: ',',
		currency_thousand_separator: '.',
		currency_prefix: '',
		currency_suffix: ' €',
		price_range: null,
	},
} );

type Product = ReturnType< typeof product >;

const createDeferred = < T >() => {
	let resolve: ( value: T ) => void = () => {
		throw new Error(
			'Deferred promise was resolved before initialization.'
		);
	};
	const promise = new Promise< T >( ( promiseResolve ) => {
		resolve = promiseResolve;
	} );
	return { promise, resolve };
};

const getBlock = (): HTMLElement => {
	const block = document.querySelector< HTMLElement >(
		'.wc-block-product-search--live'
	);
	if ( ! block ) {
		throw new Error( 'Live Product Search block was not rendered.' );
	}
	return block;
};

const getInput = ( block: HTMLElement ): HTMLInputElement => {
	const input = block.querySelector< HTMLInputElement >( 'input' );
	if ( ! input ) {
		throw new Error( 'Live Product Search input was not rendered.' );
	}
	return input;
};

describe( 'Product Search live results', () => {
	const apiFetchMock = apiFetch as jest.Mock;

	beforeEach( () => {
		jest.useFakeTimers();
		document.body.innerHTML =
			'<div class="wc-block-product-search--live"><input type="search" name="s" /></div>';
	} );

	afterEach( () => {
		jest.useRealTimers();
		jest.clearAllMocks();
	} );

	it( 'does not show a stale response after the shopper changes the query', async () => {
		const firstResponse = createDeferred< Product[] >();
		const secondResponse = createDeferred< Product[] >();
		apiFetchMock
			.mockReturnValueOnce( firstResponse.promise )
			.mockReturnValueOnce( secondResponse.promise );

		const block = getBlock();
		const input = getInput( block );
		attach( block );

		fireEvent.input( input, { target: { value: 'shirt' } } );
		act( () => jest.advanceTimersByTime( 250 ) );

		fireEvent.input( input, { target: { value: 'shoes' } } );
		act( () => jest.advanceTimersByTime( 250 ) );

		await act( async () =>
			firstResponse.resolve( [ product( 'Shirt' ) ] )
		);
		expect( block ).not.toHaveTextContent( 'Shirt' );

		await act( async () =>
			secondResponse.resolve( [ product( 'Shoes' ) ] )
		);
		expect( block ).toHaveTextContent( 'Shoes' );
		expect( block ).toHaveTextContent( '1.234,56 €' );
	} );

	it( 'does not reopen results after the shopper dismisses them', async () => {
		const response = createDeferred< Product[] >();
		apiFetchMock.mockReturnValueOnce( response.promise );

		const block = getBlock();
		const input = getInput( block );
		attach( block );

		fireEvent.input( input, { target: { value: 'shoes' } } );
		act( () => jest.advanceTimersByTime( 250 ) );

		fireEvent.keyDown( input, { key: 'Escape' } );

		await act( async () => response.resolve( [ product( 'Shoes' ) ] ) );

		expect( block ).not.toHaveTextContent( 'Shoes' );
		expect( input ).toHaveAttribute( 'aria-expanded', 'false' );
	} );

	it( 'exposes results and keyboard selection to assistive technology', async () => {
		apiFetchMock.mockResolvedValue( [ product( 'Shoes' ) ] );
		const block = getBlock();
		const input = getInput( block );
		attach( block );

		fireEvent.input( input, { target: { value: 'shoes' } } );
		act( () => jest.advanceTimersByTime( 250 ) );
		await act( async () => Promise.resolve() );

		expect( input ).toHaveAttribute( 'role', 'combobox' );
		expect( input ).toHaveAttribute( 'aria-expanded', 'true' );
		fireEvent.keyDown( input, { key: 'ArrowDown' } );
		expect( input ).toHaveAttribute( 'aria-activedescendant' );
		expect( block.querySelector( '[role="option"]' ) ).toHaveAttribute(
			'aria-selected',
			'true'
		);
	} );
} );
