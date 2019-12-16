/**
 * External dependencies
 */
import TestRenderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import { usePriceConstraint } from '../use-price-constraints';

describe( 'usePriceConstraints', () => {
	const TestComponent = ( { price } ) => {
		const priceConstraint = usePriceConstraint( price );
		return <div priceConstraint={ priceConstraint } />;
	};

	it( 'price constraint should be updated when new price is set', () => {
		const renderer = TestRenderer.create( <TestComponent price={ 10 } /> );
		const container = renderer.root.findByType( 'div' );

		expect( container.props.priceConstraint ).toBe( 10 );

		renderer.update( <TestComponent price={ 20 } /> );

		expect( container.props.priceConstraint ).toBe( 20 );
	} );

	it( 'previous price constraint should be preserved when new price is not a infinite number', () => {
		const renderer = TestRenderer.create( <TestComponent price={ 10 } /> );
		const container = renderer.root.findByType( 'div' );

		expect( container.props.priceConstraint ).toBe( 10 );

		renderer.update( <TestComponent price={ Infinity } /> );

		expect( container.props.priceConstraint ).toBe( 10 );
	} );
} );
