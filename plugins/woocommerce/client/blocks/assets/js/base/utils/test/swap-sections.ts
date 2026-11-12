/**
 * Internal dependencies
 */
import {
	captureSwap,
	getSwapGroups,
	playSwap,
	stopSwap,
	type SwapSnapshot,
} from '../swap-sections';

describe( 'getSwapGroups', () => {
	it( 'groups the sections from the first change to the last, with the sections between them that stay', () => {
		expect(
			getSwapGroups(
				[ 'contact', 'method', 'shipping', 'billing', 'rates', 'pay' ],
				[ 'contact', 'method', 'pickup', 'billing', 'pay' ]
			)
		).toEqual( {
			leaving: [ 'shipping', 'billing', 'rates' ],
			entering: [ 'pickup', 'billing' ],
			after: [ 'pay' ],
		} );
	} );

	it( 'includes a section that only one of the two states has', () => {
		expect(
			getSwapGroups(
				[ 'method', 'shipping', 'rates', 'pay' ],
				[ 'method', 'pickup', 'billing', 'pay' ]
			)
		).toEqual( {
			leaving: [ 'shipping', 'rates' ],
			entering: [ 'pickup', 'billing' ],
			after: [ 'pay' ],
		} );
	} );

	it( 'includes the sections between a change in the old order and a change in the new order', () => {
		expect(
			getSwapGroups( [ 'a', 'gone', 'b', 'c' ], [ 'a', 'b', 'c', 'new' ] )
		).toEqual( {
			leaving: [ 'gone', 'b', 'c' ],
			entering: [ 'b', 'c', 'new' ],
			after: [],
		} );
	} );

	it( 'has no group when nothing changes', () => {
		expect( getSwapGroups( [ 'a', 'b' ], [ 'a', 'b' ] ) ).toEqual( {
			leaving: [],
			entering: [],
			after: [],
		} );
	} );
} );

// Jest has no layout or Web Animations API. In the fake, sections stack in their order, `translate` moves them, and
// the copies and the entering sections animate until the test finishes their animations.
const heights = new Map< Element, number >();

const createAnimation = () => {
	let resolve = () => undefined as void;
	const animation = {
		playState: 'running',
		finished: new Promise< void >( ( done ) => {
			resolve = done;
		} ),
		effect: { getComputedTiming: () => ( { endTime: 300 } ) },
	};
	return {
		animation,
		finish: () => {
			animation.playState = 'finished';
			resolve();
		},
	};
};

let copyFade = createAnimation();
let entrance = createAnimation();

const flush = () => new Promise( ( resolve ) => setTimeout( resolve ) );

const section = ( id: string, height: number ) => {
	const element = document.createElement( 'fieldset' );
	element.id = id;
	element.innerHTML = `<label for="${ id }-input">Label</label><input id="${ id }-input" name="${ id }" />`;
	heights.set( element, height );
	return element;
};

const topOf = ( element: Element ) => element.getBoundingClientRect().top;

// The form is 430px tall before the swap.
const style = document.createElement( 'style' );
style.textContent = 'form { height: 430px; }';

describe( 'playSwap', () => {
	let container: HTMLFormElement;
	let method: HTMLElement;
	let shipping: HTMLElement;
	let rates: HTMLElement;
	let pay: HTMLElement;

	beforeEach( () => {
		copyFade = createAnimation();
		entrance = createAnimation();
		const running = ( fake: typeof copyFade ) =>
			fake.animation.playState === 'running'
				? [ fake.animation as unknown as Animation ]
				: [];
		HTMLElement.prototype.getAnimations = function ( this: HTMLElement ) {
			if ( this.hasAttribute( 'data-wc-swap-copy' ) ) {
				return running( copyFade );
			}
			if ( this.hasAttribute( 'data-wc-swap-in' ) ) {
				return running( entrance );
			}
			return [];
		};
		jest.spyOn(
			Element.prototype,
			'getBoundingClientRect'
		).mockImplementation( function ( this: Element ) {
			let top = 0;
			for ( const sibling of Array.from(
				this.parentElement?.children ?? []
			) ) {
				if ( sibling === this ) {
					break;
				}
				if ( ! sibling.hasAttribute( 'data-wc-swap-copy' ) ) {
					top += heights.get( sibling ) ?? 0;
				}
			}
			top += parseFloat(
				( this as HTMLElement ).style.translate.split( ' ' )[ 1 ] ?? '0'
			);
			return {
				top,
				left: 0,
				width: 300,
				height: heights.get( this ) ?? 0,
			} as DOMRect;
		} );

		method = section( 'shipping-method', 50 );
		shipping = section( 'shipping-fields', 200 );
		rates = section( 'shipping-option', 100 );
		pay = section( 'payment', 80 );
		container = document.createElement( 'form' );
		container.append( method, shipping, rates, pay );
		document.body.append( container );
		document.head.append( style );
	} );

	afterEach( () => {
		jest.restoreAllMocks();
		jest.useRealTimers();
		container.remove();
		style.remove();
		// @ts-expect-error The fake is not part of jsdom.
		delete HTMLElement.prototype.getAnimations;
	} );

	const capture = ( direction: number ) =>
		captureSwap( container, direction ) as SwapSnapshot;

	// Like React: the shipping sections unmount and the pickup section mounts.
	const switchToPickup = () => {
		const pickup = section( 'pickup-options', 60 );
		shipping.remove();
		rates.remove();
		method.after( pickup );
		return pickup;
	};

	const copies = () =>
		Array.from( container.querySelectorAll( '[data-wc-swap-copy]' ) );

	it( 'does nothing when the browser has no Web Animations API', () => {
		// @ts-expect-error The fake is not part of jsdom.
		delete HTMLElement.prototype.getAnimations;
		expect( captureSwap( container, 1 ) ).toBeNull();
	} );

	it( 'fades out inert copies of the old sections without ids, names, or labels for fields', () => {
		const snapshot = capture( 1 );
		switchToPickup();
		playSwap( snapshot );

		expect( copies() ).toHaveLength( 2 );
		copies().forEach( ( copy ) => {
			expect( copy.getAttribute( 'data-wc-swap-copy' ) ).toBe( 'right' );
			expect( copy.hasAttribute( 'inert' ) ).toBe( true );
			expect( copy.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
			expect( copy.id ).toBe( '' );
			expect( copy.querySelector( '[id], [name], [for]' ) ).toBeNull();
		} );
		expect( ( copies()[ 1 ] as HTMLElement ).style.top ).toBe( '250px' );
		expect( document.getElementById( 'shipping-fields' ) ).toBeNull();
	} );

	it( 'slides the new sections in from the other side', () => {
		const snapshot = capture( -1 );
		const pickup = switchToPickup();
		playSwap( snapshot );

		expect( pickup.getAttribute( 'data-wc-swap-in' ) ).toBe( 'left' );
		expect( method.hasAttribute( 'data-wc-swap-in' ) ).toBe( false );
		expect( pay.hasAttribute( 'data-wc-swap-in' ) ).toBe( false );
	} );

	it( 'holds the sections after the group in place, also when content loads, until the copies have gone', async () => {
		// Fake timers run the animation frames in order.
		jest.useFakeTimers();
		const snapshot = capture( 1 );
		const pickup = switchToPickup();
		playSwap( snapshot );

		expect( topOf( pay ) ).toBe( 350 );
		expect( pay.style.translate ).toBe( '0 240px' );
		expect( container.style.height ).toBe( '430px' );

		heights.set( pickup, 120 );
		jest.advanceTimersByTime( 20 );
		expect( topOf( pay ) ).toBe( 350 );

		copyFade.finish();
		await jest.advanceTimersByTimeAsync( 1 );
		expect( copies() ).toHaveLength( 0 );
		expect( pay.hasAttribute( 'data-wc-swap-after' ) ).toBe( true );
		expect( pay.style.translate ).toBe( '' );
		expect( container.hasAttribute( 'style' ) ).toBe( false );
	} );

	it( 'leaves nothing behind when the motion ends', async () => {
		const snapshot = capture( 1 );
		const pickup = switchToPickup();
		playSwap( snapshot );
		copyFade.finish();
		await flush();
		entrance.finish();
		await flush();

		expect( copies() ).toHaveLength( 0 );
		[ container, method, pickup, pay ].forEach( ( element ) => {
			expect(
				element.getAttributeNames().filter( ( name ) => name !== 'id' )
			).toEqual( [] );
		} );
	} );

	it( 'releases the hold after a time if the animations never end', () => {
		jest.useFakeTimers();
		const snapshot = capture( 1 );
		const pickup = switchToPickup();
		playSwap( snapshot );
		jest.advanceTimersByTime( 1000 );

		expect( copies() ).toHaveLength( 0 );
		expect( pickup.hasAttribute( 'data-wc-swap-in' ) ).toBe( false );
		expect( pay.hasAttribute( 'style' ) ).toBe( false );
		expect( container.hasAttribute( 'data-wc-swapping' ) ).toBe( false );
		expect( container.hasAttribute( 'style' ) ).toBe( false );
	} );

	it( 'ends at once for a change that applies immediately, such as after a key press', () => {
		const snapshot = capture( 1 );
		const pickup = switchToPickup();
		playSwap( snapshot );
		stopSwap( container );

		expect( copies() ).toHaveLength( 0 );
		[ container, method, pickup, pay ].forEach( ( element ) => {
			expect(
				element.getAttributeNames().filter( ( name ) => name !== 'id' )
			).toEqual( [] );
		} );
	} );

	it( 'turns around from the current position, and leaves nothing behind', async () => {
		const first = capture( 1 );
		const pickup = switchToPickup();
		playSwap( first );

		const second = capture( -1 );
		const newShipping = section( 'shipping-fields', 200 );
		const newRates = section( 'shipping-option', 100 );
		pickup.replaceWith( newShipping, newRates );
		playSwap( second );

		// The sections after the group stay where they were, which is where they are now.
		expect( topOf( pay ) ).toBe( 350 );
		expect( pay.style.translate ).toBe( '0 0px' );
		// The first copies still fade out, and a copy of the pickup section joins them.
		expect(
			copies().map( ( copy ) => copy.getAttribute( 'data-wc-swap-copy' ) )
		).toEqual( [ 'right', 'right', 'left' ] );
		expect( pickup.hasAttribute( 'data-wc-swap-in' ) ).toBe( false );
		expect( newShipping.getAttribute( 'data-wc-swap-in' ) ).toBe( 'left' );

		copyFade.finish();
		await flush();
		entrance.finish();
		await flush();

		expect( copies() ).toHaveLength( 0 );
		[ container, method, newShipping, newRates, pay ].forEach(
			( element ) => {
				expect(
					element
						.getAttributeNames()
						.filter( ( name ) => name !== 'id' )
				).toEqual( [] );
			}
		);
	} );
} );
