/**
 * External dependencies
 */
import TestRenderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import withComponentId from '../with-component-id';

const TestComponent = withComponentId( ( props ) => {
	return <div componentId={ props.componentId } />;
} );

const render = () => {
	return TestRenderer.create( <TestComponent /> );
};

describe( 'withComponentId Component', () => {
	let renderer;
	it( 'initializes with expected id on initial render', () => {
		renderer = render();
		const props = renderer.root.findByType( 'div' ).props;
		expect( props.componentId ).toBe( 0 );
	} );
	it( 'does not increment on re-render', () => {
		renderer.update( <TestComponent someValue={ 3 } /> );
		const props = renderer.root.findByType( 'div' ).props;
		expect( props.componentId ).toBe( 0 );
	} );
	it( 'increments on a new component instance', () => {
		renderer.update( <TestComponent key={ 42 } /> );
		const props = renderer.root.findByType( 'div' ).props;
		expect( props.componentId ).toBe( 1 );
	} );
} );
