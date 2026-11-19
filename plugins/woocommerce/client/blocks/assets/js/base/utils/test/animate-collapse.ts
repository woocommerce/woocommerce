/**
 * Internal dependencies
 */
import {
	animateCollapse,
	collapseCopy,
	removeCollapseCopies,
} from '../animate-collapse';

const NATURAL: Record< string, string > = {
	height: '40px',
	paddingTop: '0px',
	paddingBottom: '0px',
	marginTop: '16px',
	marginBottom: '0px',
	borderTopWidth: '1px',
	borderBottomWidth: '0px',
	opacity: '1',
};

// Jest has no layout, transitions, or Web Animations API. In the fake, the computed style of an element is the inline
// style, or its `natural` value when there is none. While the `inProgress` values of an element are set and it has the
// attribute of the helper, a transition is at those values. Each time the helper sets its attribute on an element, a
// new transition starts and replaces the running one of that element, as in a browser. `finish` ends the running
// transitions of all elements, including copies.
type FakeTransition = {
	playState: string;
	finished: Promise< void >;
	effect: { getComputedTiming: () => { endTime: number } };
	end: () => void;
	cancel: () => void;
};
const inProgress = new WeakMap< Element, Record< string, string > >();
const naturals = new WeakMap< Element, Record< string, string > >();
const transitions = new Map< Element, FakeTransition >();
const setAttribute = Element.prototype.setAttribute;

const startTransition = ( element: Element ) => {
	transitions.get( element )?.cancel();
	let end = () => undefined as void;
	let cancel = () => undefined as void;
	const finished = new Promise< void >( ( resolve, reject ) => {
		end = resolve;
		cancel = reject;
	} );
	finished.catch( () => undefined );
	const transition: FakeTransition = {
		playState: 'running',
		finished,
		effect: { getComputedTiming: () => ( { endTime: 300 } ) },
		end: () => {
			transition.playState = 'finished';
			end();
		},
		cancel: () => {
			transition.playState = 'idle';
			cancel();
		},
	};
	transitions.set( element, transition );
};

const setUp = () => {
	jest.spyOn( Element.prototype, 'setAttribute' ).mockImplementation(
		function ( this: Element, name: string, value: string ) {
			setAttribute.call( this, name, value );
			if ( name === 'data-wc-collapse' ) {
				startTransition( this );
			}
		}
	);
	HTMLElement.prototype.getAnimations = function ( this: HTMLElement ) {
		const transition = transitions.get( this );
		return this.hasAttribute( 'data-wc-collapse' ) &&
			transition?.playState === 'running'
			? [ transition as unknown as Animation ]
			: [];
	};
	jest.spyOn( window, 'getComputedStyle' ).mockImplementation(
		( element ) =>
			new Proxy( {} as CSSStyleDeclaration, {
				get: ( _, property: string ) =>
					( element.hasAttribute( 'data-wc-collapse' ) &&
						inProgress.get( element )?.[ property ] ) ||
					(
						( element as HTMLElement ).style as unknown as Record<
							string,
							string
						>
					 )[ property ] ||
					( naturals.get( element ) ?? NATURAL )[ property ],
			} )
	);
};

const createElement = () => {
	setUp();
	const element = document.createElement( 'div' );
	const values: Record< string, string > = {};
	inProgress.set( element, values );
	const natural = { ...NATURAL };
	naturals.set( element, natural );
	const finish = async () => {
		transitions.forEach( ( transition ) => {
			if ( transition.playState === 'running' ) {
				transition.end();
			}
		} );
		await new Promise( ( resolve ) => setTimeout( resolve ) );
	};
	return { element, inProgress: values, natural, finish };
};

afterEach( () => transitions.clear() );

describe( 'animateCollapse', () => {
	afterEach( () => {
		jest.restoreAllMocks();
		// @ts-expect-error The fake is not part of jsdom.
		delete HTMLElement.prototype.getAnimations;
	} );

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

	it( 'grows from the values in `from`, and the other values start open', () => {
		const { element } = createElement();
		const setHeight = jest.spyOn( element.style, 'height', 'set' );
		const setMarginTop = jest.spyOn( element.style, 'marginTop', 'set' );
		animateCollapse( element, true, true, { height: '20px' } );

		expect( setHeight.mock.calls ).toEqual( [
			[ '' ],
			[ '20px' ],
			[ '40px' ],
		] );
		expect( setMarginTop.mock.calls ).toEqual( [
			[ '' ],
			[ '16px' ],
			[ '16px' ],
		] );
	} );

	it( 'leaves nothing behind when the grow ends', async () => {
		const { element, finish } = createElement();
		animateCollapse( element, true );

		await finish();
		expect( element.getAttributeNames() ).toEqual( [] );
	} );

	it( 'grows on to the new size when the content changes size during the grow', async () => {
		const { element, natural, finish } = createElement();
		const setHeight = jest.spyOn( element.style, 'height', 'set' );
		animateCollapse( element, true );
		natural.height = '80px';
		setHeight.mockClear();

		await finish();
		expect( setHeight.mock.calls ).toEqual( [
			[ '' ],
			[ '40px' ],
			[ '80px' ],
		] );
		expect( element.style.height ).toBe( '80px' );
		expect( element.getAttribute( 'data-wc-collapse' ) ).toBe( 'in' );

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
		const { element, inProgress: values, finish } = createElement();
		const setHeight = jest.spyOn( element.style, 'height', 'set' );
		animateCollapse( element, true );
		Object.assign( values, { height: '10px', marginTop: '4px' } );
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

describe( 'collapseCopy', () => {
	afterEach( () => {
		jest.restoreAllMocks();
		document.body.innerHTML = '';
		// @ts-expect-error The fake is not part of jsdom.
		delete HTMLElement.prototype.getAnimations;
	} );

	const createForm = () => {
		const { element, inProgress: values, finish } = createElement();
		element.id = 'billing-fields';
		element.className = 'billing';
		element.innerHTML = `
			<label for="city">City</label>
			<input id="city" name="city" value="Berlin">
			<select name="country"><option>Austria</option><option>Germany</option></select>
		`;
		// React sets the selected option as a property, which a clone does not keep.
		(
			element.querySelector( 'select' ) as HTMLSelectElement
		 ).selectedIndex = 1;
		const parent = document.createElement( 'div' );
		parent.append( element, document.createElement( 'p' ) );
		document.body.append( parent );
		return { element, parent, inProgress: values, finish };
	};

	it( 'does nothing when the browser has no Web Animations API', () => {
		const parent = document.createElement( 'div' );
		const element = document.createElement( 'div' );
		parent.append( element );
		collapseCopy( element );
		collapseCopy( null );
		expect( parent.children ).toHaveLength( 1 );
	} );

	it( 'collapses an inert copy that is not part of the form, and removes it when the collapse ends', async () => {
		const { element, parent, finish } = createForm();
		collapseCopy( element );

		const copy = element.nextElementSibling as HTMLElement;
		expect( copy.getAttribute( 'data-wc-collapse' ) ).toBe( 'out' );
		expect( copy.hasAttribute( 'inert' ) ).toBe( true );
		expect( copy.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
		expect( copy.querySelectorAll( '[id], [name], [for]' ) ).toHaveLength(
			0
		);
		expect( copy.hasAttribute( 'id' ) ).toBe( false );
		expect( copy.querySelector( 'select' )?.selectedIndex ).toBe( 1 );
		expect( element.getAttributeNames() ).toEqual( [ 'id', 'class' ] );

		await finish();
		expect( parent.children ).toHaveLength( 2 );
		expect( parent.querySelector( '[data-wc-collapse-copy]' ) ).toBeNull();
	} );

	it( 'continues the motion of an element that still grows', () => {
		const { element, inProgress: values } = createForm();
		animateCollapse( element, true );
		Object.assign( values, { height: '10px' } );

		// The helper reads the layout once the start values are set.
		const startHeights: string[] = [];
		jest.spyOn(
			HTMLElement.prototype,
			'getBoundingClientRect'
		).mockImplementation( function ( this: HTMLElement ) {
			startHeights.push( this.style.height );
			return {} as DOMRect;
		} );
		collapseCopy( element );
		const copy = element.nextElementSibling as HTMLElement;
		expect( startHeights ).toEqual( [ '10px' ] );
		expect( copy.style.height ).toBe( '0px' );
	} );

	it( 'keeps the fields of the copy out of form validation', () => {
		const { element } = createForm();
		element.innerHTML = `
			<input required>
			<input type="email" value="not-an-email">
			<input type="checkbox" required>
			<select required><option value="">Select</option></select>
			<textarea required></textarea>
		`;
		const form = document.createElement( 'form' );
		element.before( form );
		form.append( element );
		collapseCopy( element );
		element.remove();

		expect(
			form.querySelector( '[data-wc-collapse-copy]' )
		).not.toBeNull();
		expect( form.checkValidity() ).toBe( true );
	} );

	it( 'turns around when the element grows again before the copy has collapsed', () => {
		const { element } = createForm();
		collapseCopy( element );
		const copy = element.nextElementSibling as HTMLElement;
		inProgress.set( copy, { height: '25px' } );

		// React mounts a new element after the copy.
		const next = document.createElement( 'div' );
		next.className = 'billing';
		inProgress.set( next, {} );
		element.remove();
		copy.after( next );
		const setHeight = jest.spyOn( next.style, 'height', 'set' );
		animateCollapse( next, true );

		expect( copy.isConnected ).toBe( false );
		expect( setHeight.mock.calls ).toEqual( [
			[ '' ],
			[ '25px' ],
			[ '40px' ],
		] );
	} );
} );

describe( 'removeCollapseCopies', () => {
	afterEach( () => {
		jest.restoreAllMocks();
		document.body.innerHTML = '';
		// @ts-expect-error The fake is not part of jsdom.
		delete HTMLElement.prototype.getAnimations;
	} );

	it( 'removes every copy that still collapses inside the container', () => {
		const { element: first } = createElement();
		const { element: second } = createElement();
		const firstOption = document.createElement( 'div' );
		const secondOption = document.createElement( 'div' );
		firstOption.append( first );
		secondOption.append( second );
		const container = document.createElement( 'div' );
		container.append( firstOption, secondOption );
		document.body.append( container );

		collapseCopy( first, { empty: true } );
		collapseCopy( second, { empty: true } );
		first.remove();
		second.remove();
		expect( container.querySelectorAll( '[inert]' ) ).toHaveLength( 2 );

		removeCollapseCopies( container );
		expect( container.querySelectorAll( '[inert]' ) ).toHaveLength( 0 );
	} );
} );
