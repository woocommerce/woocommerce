/**
 * Internal dependencies
 */
import {
	whenAnimationsEnd,
	whenAllAnimationsEnd,
} from '../when-animations-end';

// Jest has no Web Animations API. Like a browser animation, this fake reports `finished` or `idle` when it ends.
const createAnimation = (
	playState: Animation[ 'playState' ] = 'running',
	endTime = 200
) => {
	let resolveFinished = () => undefined as void;
	let rejectFinished = () => undefined as void;
	const animation = {
		playState,
		finished: new Promise< void >( ( resolve, reject ) => {
			resolveFinished = resolve;
			rejectFinished = () => reject( new Error( 'AbortError' ) );
		} ),
		effect: { getComputedTiming: () => ( { endTime } ) },
	};
	return {
		animation: animation as unknown as Animation,
		finish: () => {
			animation.playState = 'finished';
			resolveFinished();
		},
		cancel: () => {
			animation.playState = 'idle';
			rejectFinished();
		},
	};
};

// The element returns the animations in the array when it is asked, so a test can add one later.
const createElement = ( animations: Animation[] ) => {
	const element = document.createElement( 'div' );
	element.getAnimations = () => animations;
	return element;
};

// Lets promise callbacks run.
const flushPromises = () => new Promise( ( resolve ) => setTimeout( resolve ) );

describe( 'whenAnimationsEnd', () => {
	it( 'calls back immediately when the browser has no Web Animations API', () => {
		const callback = jest.fn();
		whenAnimationsEnd( document.createElement( 'div' ), callback );
		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'calls back immediately when there is no element', () => {
		const callback = jest.fn();
		whenAnimationsEnd( null, callback );
		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'calls back immediately when no animation is in progress', () => {
		const callback = jest.fn();
		const element = createElement( [
			createAnimation( 'finished' ).animation,
			createAnimation( 'idle' ).animation,
			createAnimation( 'running', Infinity ).animation,
		] );
		whenAnimationsEnd( element, callback );
		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'waits for every running and paused animation to end', async () => {
		const callback = jest.fn();
		const running = createAnimation( 'running' );
		const paused = createAnimation( 'paused' );
		whenAnimationsEnd(
			createElement( [ running.animation, paused.animation ] ),
			callback
		);

		running.finish();
		await flushPromises();
		expect( callback ).not.toHaveBeenCalled();

		paused.finish();
		await flushPromises();
		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'treats a cancelled animation as ended', async () => {
		const callback = jest.fn();
		const animation = createAnimation();
		whenAnimationsEnd( createElement( [ animation.animation ] ), callback );

		animation.cancel();
		await flushPromises();
		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'waits again for a transition that turns around', async () => {
		const callback = jest.fn();
		const first = createAnimation();
		const reversed = createAnimation();
		const animations = [ first.animation ];
		whenAnimationsEnd( createElement( animations ), callback );

		// A transition that turns around cancels the old transition and starts a new one.
		animations.push( reversed.animation );
		first.cancel();
		await flushPromises();
		expect( callback ).not.toHaveBeenCalled();

		reversed.finish();
		await flushPromises();
		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not call back after it is cancelled', async () => {
		const callback = jest.fn();
		const animation = createAnimation();
		const cancel = whenAnimationsEnd(
			createElement( [ animation.animation ] ),
			callback
		);

		cancel();
		animation.finish();
		await flushPromises();
		expect( callback ).not.toHaveBeenCalled();
	} );
} );

describe( 'whenAllAnimationsEnd', () => {
	it( 'calls back immediately when there are no elements', () => {
		const callback = jest.fn();
		whenAllAnimationsEnd( [], callback );
		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'waits for the animations of every element to end', async () => {
		const callback = jest.fn();
		const first = createAnimation();
		const second = createAnimation();
		whenAllAnimationsEnd(
			[
				createElement( [ first.animation ] ),
				createElement( [ second.animation ] ),
			],
			callback
		);

		first.finish();
		await flushPromises();
		expect( callback ).not.toHaveBeenCalled();

		second.finish();
		await flushPromises();
		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not call back after it is cancelled', async () => {
		const callback = jest.fn();
		const animation = createAnimation();
		const cancel = whenAllAnimationsEnd(
			[ createElement( [ animation.animation ] ) ],
			callback
		);

		cancel();
		animation.finish();
		await flushPromises();
		expect( callback ).not.toHaveBeenCalled();
	} );
} );
