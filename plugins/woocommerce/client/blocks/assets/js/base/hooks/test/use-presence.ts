/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { usePresence } from '../use-presence';

type Props = { value: string[] | null; exit?: boolean };

const renderUsePresence = ( initialProps: Props ) =>
	renderHook(
		( { value, exit = true }: Props ) => usePresence( value, { exit } ),
		{ initialProps }
	);

// An element with one running animation. Like a browser animation, it reports `finished` once `finish` is called.
const createAnimatingElement = () => {
	let resolveFinished = () => undefined as void;
	const animation = {
		playState: 'running',
		finished: new Promise< void >( ( resolve ) => {
			resolveFinished = resolve;
		} ),
		effect: { getComputedTiming: () => ( { endTime: 200 } ) },
	};
	const element = document.createElement( 'div' );
	element.getAnimations = () => [ animation as unknown as Animation ];
	const finish = () => {
		animation.playState = 'finished';
		resolveFinished();
	};
	return { element, finish };
};

describe( 'usePresence', () => {
	const open = [ 'a', 'b' ];

	it( 'shows the value while it is present', () => {
		const { result } = renderUsePresence( { value: open } );

		expect( result.current.value ).toBe( open );
		expect( result.current.isExiting ).toBe( false );
	} );

	it( 'removes the value immediately when nothing animates', () => {
		const { result, rerender } = renderUsePresence( { value: open } );

		rerender( { value: null } );
		expect( result.current.value ).toBeNull();
		expect( result.current.isExiting ).toBe( false );
	} );

	it( 'keeps the last value while the element animates out', async () => {
		const { result, rerender } = renderUsePresence( { value: open } );
		const { element, finish } = createAnimatingElement();
		result.current.ref.current = element;

		rerender( { value: null } );
		expect( result.current.value ).toBe( open );
		expect( result.current.isExiting ).toBe( true );

		await act( async () => finish() );
		expect( result.current.value ).toBeNull();
		expect( result.current.isExiting ).toBe( false );
	} );

	it( 'turns around when a value arrives during the exit', async () => {
		const { result, rerender } = renderUsePresence( { value: open } );
		const { element, finish } = createAnimatingElement();
		result.current.ref.current = element;
		const reopened = [ 'c' ];

		rerender( { value: null } );
		rerender( { value: reopened } );
		expect( result.current.value ).toBe( reopened );
		expect( result.current.isExiting ).toBe( false );

		// The cancelled exit must not remove the new value.
		await act( async () => finish() );
		expect( result.current.value ).toBe( reopened );
	} );

	it( 'removes the value immediately when exit is false', () => {
		const { result, rerender } = renderUsePresence( { value: open } );
		result.current.ref.current = createAnimatingElement().element;

		rerender( { value: null, exit: false } );
		expect( result.current.value ).toBeNull();
		expect( result.current.isExiting ).toBe( false );
	} );
} );
