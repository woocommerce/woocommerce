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

const flushPromises = () =>
	new Promise< void >( ( resolve ) => setTimeout( resolve ) );

describe( 'onAnimationsFinished', () => {
	it( 'calls back straight away when nothing finite is animating', () => {
		const callback = jest.fn();

		onAnimationsFinished( null, callback );
		onAnimationsFinished( document.createElement( 'div' ), callback );
		onAnimationsFinished(
			elementWith( createAnimation( Infinity ) ),
			callback
		);

		expect( callback ).toHaveBeenCalledTimes( 3 );
	} );

	it( 'waits for every finite animation to finish or be cancelled, ignoring loops', async () => {
		const first = createAnimation();
		const second = createAnimation();
		const callback = jest.fn();

		onAnimationsFinished(
			elementWith( first, createAnimation( Infinity ), second ),
			callback
		);
		first.finish();
		await flushPromises();

		expect( callback ).not.toHaveBeenCalled();

		second.cancel();
		await flushPromises();

		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'never calls back once cancelled', async () => {
		const animation = createAnimation();
		const callback = jest.fn();

		onAnimationsFinished( elementWith( animation ), callback )();
		animation.finish();
		await flushPromises();

		expect( callback ).not.toHaveBeenCalled();
	} );
} );
