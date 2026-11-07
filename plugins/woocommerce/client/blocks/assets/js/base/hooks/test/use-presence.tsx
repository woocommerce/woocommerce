/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { usePresence } from '../use-presence';

// The Jest matchMedia mock reports reduced motion, under which usePresence never sets a transition.
jest.mock( '@wordpress/compose', () => ( {
	...jest.requireActual( '@wordpress/compose' ),
	useReducedMotion: () => false,
} ) );

// jsdom doesn't animate, so each `getAnimations()` call returns one animation that the test finishes by hand.
let pendingFinishes: Array< () => void > = [];
const finishAnimation = async ( index: number ) => {
	await act( async () => {
		pendingFinishes[ index ]();
	} );
};

const Content = ( { isOpen, label }: { isOpen: boolean; label: string } ) => {
	const { isPresent, className, ref, data } = usePresence( isOpen, {
		data: label,
	} );
	return isPresent ? (
		<div ref={ ref } data-testid="content" className={ className }>
			{ data }
		</div>
	) : null;
};

describe( 'usePresence', () => {
	const getAnimations = Element.prototype.getAnimations;

	beforeEach( () => {
		pendingFinishes = [];
		Element.prototype.getAnimations = () => {
			const { promise, resolve } = Promise.withResolvers< Animation >();
			pendingFinishes.push( () => resolve( {} as Animation ) );
			return [
				{
					finished: promise,
					effect: { getComputedTiming: () => ( { iterations: 1 } ) },
				} as unknown as Animation,
			];
		};
	} );

	afterEach( () => {
		Element.prototype.getAnimations = getAnimations;
	} );

	it( 'marks content as entering until its animations finish', async () => {
		const { rerender } = render( <Content isOpen={ false } label="a" /> );

		rerender( <Content isOpen={ true } label="a" /> );

		expect( screen.getByTestId( 'content' ) ).toHaveClass( 'is-entering' );

		await finishAnimation( 0 );

		expect( screen.getByTestId( 'content' ) ).not.toHaveClass(
			'is-entering'
		);
	} );

	it( 'keeps closing content mounted, with the data it closed with, until its animations finish', async () => {
		const { rerender } = render( <Content isOpen={ true } label="a" /> );

		rerender( <Content isOpen={ false } label="b" /> );

		expect( screen.getByTestId( 'content' ) ).toHaveClass( 'is-exiting' );
		expect( screen.getByTestId( 'content' ) ).toHaveTextContent( 'a' );

		await finishAnimation( 0 );

		expect( screen.queryByTestId( 'content' ) ).not.toBeInTheDocument();
	} );

	it( 'stays mounted when reopened while closing, even after the closing animation ends', async () => {
		const { rerender } = render( <Content isOpen={ true } label="a" /> );

		rerender( <Content isOpen={ false } label="a" /> );
		rerender( <Content isOpen={ true } label="a" /> );
		await finishAnimation( 0 );

		expect( screen.getByTestId( 'content' ) ).toHaveClass( 'is-entering' );

		await finishAnimation( 1 );

		expect( screen.getByTestId( 'content' ) ).not.toHaveClass(
			'is-entering'
		);
	} );

	it( 'mounts and unmounts straight away when nothing animates', () => {
		Element.prototype.getAnimations = () => [];
		const { rerender } = render( <Content isOpen={ false } label="a" /> );

		rerender( <Content isOpen={ true } label="a" /> );

		expect( screen.getByTestId( 'content' ) ).not.toHaveClass(
			'is-entering'
		);

		rerender( <Content isOpen={ false } label="a" /> );

		expect( screen.queryByTestId( 'content' ) ).not.toBeInTheDocument();
	} );
} );
