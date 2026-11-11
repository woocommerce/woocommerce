/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { ValidationInputError } from '../index';

describe( 'ValidationInputError', () => {
	let finish: () => void;

	// Gives every element one running animation, which ends when `finish` is called.
	beforeEach( () => {
		const { promise, resolve } = Promise.withResolvers< void >();
		const animation = {
			playState: 'running',
			finished: promise,
			effect: { getComputedTiming: () => ( { endTime: 200 } ) },
		};
		finish = () => {
			animation.playState = 'finished';
			resolve();
		};
		Element.prototype.getAnimations = () => [
			animation as unknown as Animation,
		];
	} );

	afterEach( () => {
		// @ts-expect-error jsdom has no Web Animations API.
		delete Element.prototype.getAnimations;
	} );

	it( 'keeps the last message, hidden and inert, while it collapses', async () => {
		const { rerender } = render(
			<ValidationInputError errorMessage="Enter a postcode" />
		);
		const alert = screen.getByRole( 'alert' );
		expect( alert ).not.toHaveAttribute( 'aria-hidden' );

		rerender( <ValidationInputError errorMessage="" /> );
		expect( alert ).toHaveTextContent( 'Enter a postcode' );
		expect( alert ).toHaveAttribute( 'aria-hidden', 'true' );
		expect( alert ).toHaveAttribute( 'inert' );
		expect( alert.querySelector( 'p' ) ).not.toHaveAttribute( 'id' );

		await act( async () => finish() );
		expect( alert ).not.toBeInTheDocument();
	} );

	it( 'is announced and interactive again when the message returns during the collapse', () => {
		const { rerender } = render(
			<ValidationInputError errorMessage="Enter a postcode" />
		);
		const alert = screen.getByRole( 'alert' );
		rerender( <ValidationInputError errorMessage="" /> );
		const exitingMessage = alert.querySelector( 'p' );
		rerender( <ValidationInputError errorMessage="Enter a postcode" /> );

		// The same element turns around, and the message inside it is new, so the alert announces it.
		expect( screen.getByRole( 'alert' ) ).toBe( alert );
		expect( alert.querySelector( 'p' ) ).not.toBe( exitingMessage );
		expect( alert ).not.toHaveAttribute( 'aria-hidden' );
		expect( alert ).not.toHaveAttribute( 'inert' );
	} );
} );
