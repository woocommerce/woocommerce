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
		const priceConstraint = usePriceConstraint( price, 2 );
		return <div priceConstraint={ priceConstraint } />;
	};

	it( 'price constraint should be updated when new price is set', () => {
		const renderer = TestRenderer.create(
			<TestComponent price={ 1000 } />
		);
		const container = renderer.root.findByType( 'div' );

		expect( container.props.priceConstraint ).toBe( 1000 );

		renderer.update( <TestComponent price={ 2000 } /> );

		expect( container.props.priceConstraint ).toBe( 2000 );
	} );

	it( 'previous price constraint should be preserved when new price is not a infinite number', () => {
		const renderer = TestRenderer.create(
			<TestComponent price={ 1000 } />
		);
		const container = renderer.root.findByType( 'div' );

		expect( container.props.priceConstraint ).toBe( 1000 );

		renderer.update( <TestComponent price={ Infinity } /> );

		expect( container.props.priceConstraint ).toBe( 1000 );
	} );
} );
