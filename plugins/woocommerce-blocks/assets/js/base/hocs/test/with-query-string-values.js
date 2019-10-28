/**
 * External dependencies
 */
import TestRenderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import withQueryStringValues from '../with-query-string-values';

delete global.window.location;

describe( 'withQueryStringValues Component', () => {
	let TestComponent;
	let render;

	beforeEach( () => {
		TestComponent = withQueryStringValues( [ 'name' ] )( ( props ) => {
			return (
				<div
					name={ props.name }
					updateQueryStringValues={ props.updateQueryStringValues }
				/>
			);
		} );
	} );

	it( 'reads the correct query string value for each instance', () => {
		global.window.location = {
			href:
				'https://www.wooocommerce.com/?name=Alice&name_2=Bob&name_3=Carol',
		};

		const renderer = TestRenderer.create(
			<main>
				<TestComponent />
				<TestComponent />
				<TestComponent />
			</main>
		);

		const elements = renderer.root.findAllByType( 'div' );
		expect( elements[ 0 ].props.name ).toBe( 'Alice' );
		expect( elements[ 1 ].props.name ).toBe( 'Bob' );
		expect( elements[ 2 ].props.name ).toBe( 'Carol' );
	} );

	describe( 'lifecycle methods', () => {
		beforeEach( () => {
			render = () => TestRenderer.create( <TestComponent /> );
			window.addEventListener = jest.fn();
			window.removeEventListener = jest.fn();
		} );

		afterEach( () => {
			window.addEventListener.mockReset();
			window.removeEventListener.mockReset();
		} );

		it( 'subscribes to popstate events on mount', () => {
			render();

			const { calls } = window.addEventListener.mock;
			const addedPopStateEventListener = calls.reduce(
				( acc, call ) => acc || call[ 0 ] === 'popstate',
				false
			);

			expect( addedPopStateEventListener ).toBe( true );
		} );

		it( 'unsubscribes from popstate events on unmount', () => {
			const renderer = render();
			renderer.unmount();

			const { calls } = window.removeEventListener.mock;
			const removedPopStateEventListener = calls.reduce(
				( acc, call ) => acc || call[ 0 ] === 'popstate',
				false
			);

			expect( removedPopStateEventListener ).toBe( true );
		} );
	} );

	describe( 'state', () => {
		beforeEach( () => {
			render = () => TestRenderer.create( <TestComponent /> );

			global.window.location = {
				href: 'https://www.wooocommerce.com/?name=Alice',
			};

			window.history.pushState = jest.fn();
		} );

		afterEach( () => {
			window.history.pushState.mockReset();
		} );

		it( 'gets state from location', () => {
			const renderer = render();
			const props = renderer.root.findByType( 'div' ).props;
			expect( props.name ).toBe( 'Alice' );
		} );

		it( 'pushes to history on values update', () => {
			const renderer = render();
			const initialProps = renderer.root.findByType( 'div' ).props;

			initialProps.updateQueryStringValues( { name: 'Bob' } );

			const finalProps = renderer.root.findByType( 'div' ).props;
			expect( finalProps.name ).toBe( 'Bob' );
			expect( window.history.pushState ).toHaveBeenCalledTimes( 1 );
			expect(
				window.history.pushState.mock.calls[ 0 ][ 2 ].endsWith(
					'?name=Bob'
				)
			).toBe( true );
		} );
	} );
} );
