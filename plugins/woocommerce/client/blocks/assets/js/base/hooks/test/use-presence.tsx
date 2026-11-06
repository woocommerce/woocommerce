/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import { useReducedMotion } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { usePresence } from '../use-presence';

jest.mock( '@wordpress/compose', () => ( {
	...jest.requireActual( '@wordpress/compose' ),
	useReducedMotion: jest.fn(),
} ) );

// jsdom doesn't animate, so each `getAnimations()` call returns one animation that the test finishes by hand.
let pendingFinishes: Array< () => void > = [];
const finishAnimation = async ( index: number ) => {
	await act( async () => {
		pendingFinishes[ index ]();
	} );
};

const Content = ( { isOpen }: { isOpen: boolean } ) => {
	const { isPresent, transition, ref } =
		usePresence< HTMLDivElement >( isOpen );
	return isPresent ? (
		<div ref={ ref } data-testid="content">
			{ transition ?? 'idle' }
		</div>
	) : null;
};

describe( 'usePresence', () => {
	const getAnimations = Element.prototype.getAnimations;

	beforeEach( () => {
		pendingFinishes = [];
		jest.mocked( useReducedMotion ).mockReturnValue( false );
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

	it( 'renders open content without a transition on first render', () => {
		render( <Content isOpen={ true } /> );

		expect( screen.getByTestId( 'content' ) ).toHaveTextContent( 'idle' );
	} );

	it( 'marks content as entering until its animations finish', async () => {
		const { rerender } = render( <Content isOpen={ false } /> );

		rerender( <Content isOpen={ true } /> );

		expect( screen.getByTestId( 'content' ) ).toHaveTextContent(
			'entering'
		);

		await finishAnimation( 0 );

		expect( screen.getByTestId( 'content' ) ).toHaveTextContent( 'idle' );
	} );

	it( 'keeps closing content mounted until its animations finish', async () => {
		const { rerender } = render( <Content isOpen={ true } /> );

		rerender( <Content isOpen={ false } /> );

		expect( screen.getByTestId( 'content' ) ).toHaveTextContent(
			'exiting'
		);

		await finishAnimation( 0 );

		expect( screen.queryByTestId( 'content' ) ).not.toBeInTheDocument();
	} );

	it( 'stays mounted when reopened while closing, even after the closing animation ends', async () => {
		const { rerender } = render( <Content isOpen={ true } /> );

		rerender( <Content isOpen={ false } /> );
		rerender( <Content isOpen={ true } /> );
		await finishAnimation( 0 );

		expect( screen.getByTestId( 'content' ) ).toHaveTextContent(
			'entering'
		);

		await finishAnimation( 1 );

		expect( screen.getByTestId( 'content' ) ).toHaveTextContent( 'idle' );
	} );

	it( 'mounts and unmounts straight away with reduced motion', () => {
		jest.mocked( useReducedMotion ).mockReturnValue( true );
		const { rerender } = render( <Content isOpen={ false } /> );

		rerender( <Content isOpen={ true } /> );

		expect( screen.getByTestId( 'content' ) ).toHaveTextContent( 'idle' );

		rerender( <Content isOpen={ false } /> );

		expect( screen.queryByTestId( 'content' ) ).not.toBeInTheDocument();
	} );
} );
