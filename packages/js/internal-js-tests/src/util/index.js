/* global afterEach, beforeEach, jest */

/**
 * External dependencies
 */
import { screen } from '@testing-library/react';

/**
 * For use with react-testing-library, like getText but allows the text to reside in multiple elements
 *
 * @param {Object} query - Original query.
 *
 * @return {Array} - Array of two arrays, first including truthy values, and second including falsy.
 */
const withMarkup = ( query ) => ( text ) =>
	query( ( content, node ) => {
		const hasText = ( domNode ) => domNode.textContent === text;
		const childrenDontHaveText = Array.from( node.children ).every(
			( child ) => ! hasText( child )
		);

		return hasText( node ) && childrenDontHaveText;
	} );

export const getByTextWithMarkup = withMarkup( screen.getByText );

/**
 * Observe one navigation operation in jsdom without attempting navigation.
 *
 * @param {'href'|'assign'|'reload'} operation Browser location operation.
 * @return {jest.Mock} Mock that records calls to the operation.
 */
export function mockBrowserLocation( operation ) {
	const mock = jest.fn();
	let spy;

	beforeEach( () => {
		mock.mockClear();

		// jsdom makes Location members non-configurable on the public wrapper.
		const { location } = window;
		const implementationKey = Object.getOwnPropertySymbols( location ).find(
			( key ) => key.description === 'impl'
		);
		if ( ! implementationKey ) {
			throw new Error( 'Expected a jsdom Location implementation.' );
		}
		const implementation = Reflect.get( location, implementationKey );
		spy =
			operation === 'href'
				? jest.spyOn( implementation, 'href', 'set' )
				: jest.spyOn( implementation, operation );
		spy.mockImplementation( mock );
	} );

	afterEach( () => {
		spy?.mockRestore();
	} );

	return mock;
}
