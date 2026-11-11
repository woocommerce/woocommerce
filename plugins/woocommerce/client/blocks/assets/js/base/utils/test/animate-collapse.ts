/**
 * Internal dependencies
 */
import { animateCollapse } from '../animate-collapse';

const NATURAL: Record< string, string > = {
	height: '40px',
	paddingTop: '0px',
	paddingBottom: '0px',
	marginTop: '16px',
	marginBottom: '0px',
	opacity: '1',
};

// Jest has no layout, transitions, or Web Animations API. In the fake, the computed style is the inline style, or the
// natural value when there is none. While `inProgress` has values and the element has the attribute of the helper,
// a transition is at those values. `finish` ends the transitions.
const createElement = () => {
	const element = document.createElement( 'div' );
	const inProgress: Record< string, string > = {};
	let resolveFinished = () => undefined as void;
	const transition = {
		playState: 'running',
		finished: new Promise< void >( ( resolve ) => {
			resolveFinished = resolve;
		} ),
		effect: { getComputedTiming: () => ( { endTime: 300 } ) },
	};
	element.getAnimations = () =>
		element.hasAttribute( 'data-wc-collapse' ) &&
		transition.playState === 'running'
			? [ transition as unknown as Animation ]
			: [];
	jest.spyOn( window, 'getComputedStyle' ).mockImplementation(
		() =>
			new Proxy( {} as CSSStyleDeclaration, {
				get: ( _, property: string ) =>
					( element.hasAttribute( 'data-wc-collapse' ) &&
						inProgress[ property ] ) ||
					( element.style as unknown as Record< string, string > )[
						property
					] ||
					NATURAL[ property ],
			} )
	);
	const finish = async () => {
		transition.playState = 'finished';
		resolveFinished();
		await new Promise( ( resolve ) => setTimeout( resolve ) );
	};
	return { element, inProgress, finish };
};

describe( 'animateCollapse', () => {
	afterEach( () => jest.restoreAllMocks() );

	it( 'does nothing when the browser has no Web Animations API', () => {
		const element = document.createElement( 'div' );
		animateCollapse( element, true );
		animateCollapse( null, false );
		expect( element.getAttributeNames() ).toEqual( [] );
	} );

	it( 'grows the space and the opacity from nothing to the natural values', () => {
		const { element } = createElement();
		const setProperty = jest.spyOn( element.style, 'height', 'set' );
		animateCollapse( element, true );

		expect( setProperty.mock.calls ).toEqual( [
			[ '' ],
			[ '0px' ],
			[ '40px' ],
		] );
		expect( element.style.marginTop ).toBe( '16px' );
		expect( element.style.opacity ).toBe( '1' );
		expect( element.getAttribute( 'data-wc-collapse' ) ).toBe( 'in' );
	} );

	it( 'leaves nothing behind when the grow ends', async () => {
		const { element, finish } = createElement();
		animateCollapse( element, true );

		await finish();
		expect( element.getAttributeNames() ).toEqual( [] );
	} );

	it( 'collapses to nothing and stays collapsed when the collapse ends', async () => {
		const { element, finish } = createElement();
		animateCollapse( element, false );
		expect( element.getAttribute( 'data-wc-collapse' ) ).toBe( 'out' );

		await finish();
		expect( element.style.height ).toBe( '0px' );
		expect( element.style.marginTop ).toBe( '0px' );
		expect( element.style.opacity ).toBe( '0' );
	} );

	it( 'turns around from the current position', async () => {
		const { element, inProgress, finish } = createElement();
		const setHeight = jest.spyOn( element.style, 'height', 'set' );
		animateCollapse( element, true );
		Object.assign( inProgress, { height: '10px', marginTop: '4px' } );
		setHeight.mockClear();

		animateCollapse( element, false );
		expect( setHeight.mock.calls ).toEqual( [
			[ '' ],
			[ '10px' ],
			[ '0px' ],
		] );
		expect( element.getAttribute( 'data-wc-collapse' ) ).toBe( 'out' );

		// The end of the grow must not remove the styles of the collapse.
		await finish();
		expect( element.style.height ).toBe( '0px' );
	} );

	it( 'removes the motion and its styles when animate is false', () => {
		const { element } = createElement();
		animateCollapse( element, false );

		animateCollapse( element, true, false );
		expect( element.getAttributeNames() ).toEqual( [] );
	} );
} );
