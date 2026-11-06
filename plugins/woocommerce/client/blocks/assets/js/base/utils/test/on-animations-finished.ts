/**
 * Internal dependencies
 */
import { onAnimationsFinished } from '../on-animations-finished';

type FakeAnimation = {
	animation: Animation;
	finish: () => void;
	cancel: () => void;
};

const createAnimation = ( iterations = 1 ): FakeAnimation => {
	const { promise, resolve, reject } = Promise.withResolvers< Animation >();
	return {
		animation: {
			finished: promise,
			effect: { getComputedTiming: () => ( { iterations } ) },
		} as unknown as Animation,
		finish: () => resolve( {} as Animation ),
		cancel: () => reject( new DOMException( 'Aborted', 'AbortError' ) ),
	};
};

const elementWith = ( ...animations: FakeAnimation[] ) =>
	( {
		getAnimations: () => animations.map( ( { animation } ) => animation ),
	} ) as unknown as Element;

const flushPromises = () => {
	const { promise, resolve } = Promise.withResolvers< void >();
	setTimeout( resolve );
	return promise;
};

describe( 'onAnimationsFinished', () => {
	it( 'calls back straight away when there is no element or nothing is animating', () => {
		const callback = jest.fn();

		onAnimationsFinished( null, callback );
		onAnimationsFinished( document.createElement( 'div' ), callback );
		onAnimationsFinished( elementWith(), callback );

		expect( callback ).toHaveBeenCalledTimes( 3 );
	} );

	it( 'waits for every finite animation to finish', async () => {
		const first = createAnimation();
		const second = createAnimation();
		const callback = jest.fn();

		onAnimationsFinished( elementWith( first, second ), callback );
		first.finish();
		await flushPromises();

		expect( callback ).not.toHaveBeenCalled();

		second.finish();
		await flushPromises();

		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'ignores looping animations, which never finish', async () => {
		const spinner = createAnimation( Infinity );
		const fade = createAnimation();
		const callback = jest.fn();

		onAnimationsFinished( elementWith( spinner ), callback );

		expect( callback ).toHaveBeenCalledTimes( 1 );

		onAnimationsFinished( elementWith( spinner, fade ), callback );
		fade.finish();
		await flushPromises();

		expect( callback ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'still calls back when an animation is cancelled instead of finishing', async () => {
		const animation = createAnimation();
		const callback = jest.fn();

		onAnimationsFinished( elementWith( animation ), callback );
		animation.cancel();
		await flushPromises();

		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'never calls back once cancelled', async () => {
		const animation = createAnimation();
		const callback = jest.fn();

		const cancel = onAnimationsFinished(
			elementWith( animation ),
			callback
		);
		cancel();
		animation.finish();
		await flushPromises();

		expect( callback ).not.toHaveBeenCalled();
	} );
} );
