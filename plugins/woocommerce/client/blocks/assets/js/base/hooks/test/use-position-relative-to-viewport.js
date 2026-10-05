import { describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render, screen, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { usePositionRelativeToViewport } from '../use-position-relative-to-viewport';
describe( 'usePositionRelativeToViewport', () => {
	function setup() {
		const TestComponent = () => {
			const [ referenceElement, positionRelativeToViewport ] =
				usePositionRelativeToViewport();
			return (
				<>
					{ referenceElement }
					{ positionRelativeToViewport === 'below' && (
						<p data-testid="below"></p>
					) }
					{ positionRelativeToViewport === 'visible' && (
						<p data-testid="visible"></p>
					) }
					{ positionRelativeToViewport === 'above' && (
						<p data-testid="above"></p>
					) }
				</>
			);
		};
		return render( <TestComponent /> );
	}
	it( "calls IntersectionObserver's `observe` and `unobserve` events", async () => {
		const observe = vi.fn();
		const unobserve = vi.fn();

		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		IntersectionObserver = vi.fn( function () {
			return {
				observe,
				unobserve,
			};
		} );
		const { unmount } = setup();
		expect( observe ).toHaveBeenCalled();
		unmount();
		expect( unobserve ).toHaveBeenCalled();
	} );
	it.each`
		position       | isIntersecting | top
		${ 'visible' } | ${ true }      | ${ 0 }
		${ 'below' }   | ${ false }     | ${ 10 }
		${ 'above' }   | ${ false }     | ${ 0 }
		${ 'above' }   | ${ false }     | ${ -10 }
	`(
		"position relative to viewport is '$position' with isIntersecting=$isIntersecting and top=$top",
		( { position, isIntersecting, top } ) => {
			let intersectionObserverCallback = ( entries ) => entries;

			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
			IntersectionObserver = vi.fn( function ( callback ) {
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore
				intersectionObserverCallback = callback;
				return {
					observe: () => void null,
					unobserve: () => void null,
				};
			} );
			setup();
			act( () => {
				intersectionObserverCallback( [
					{
						isIntersecting,
						boundingClientRect: {
							top,
						},
					},
				] );
			} );
			expect( screen.getAllByTestId( position ) ).toHaveLength( 1 );
		}
	);
} );
