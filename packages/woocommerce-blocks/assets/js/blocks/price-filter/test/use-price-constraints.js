/**
 * External dependencies
 */
import TestRenderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import { usePriceConstraint } from '../use-price-constraints';
import { ROUND_UP, ROUND_DOWN } from '../constants';

describe( 'usePriceConstraints', () => {
	const TestComponent = ( { price } ) => {
		const maxPriceConstraint = usePriceConstraint( price, ROUND_UP );
		const minPriceConstraint = usePriceConstraint( price, ROUND_DOWN );
		return (
			<div
				minPriceConstraint={ minPriceConstraint }
				maxPriceConstraint={ maxPriceConstraint }
			/>
		);
	};

	it( 'max price constraint should be updated when new price is set', () => {
		const renderer = TestRenderer.create( <TestComponent price={ 10 } /> );
		const container = renderer.root.findByType( 'div' );

		expect( container.props.maxPriceConstraint ).toBe( 10 );

		renderer.update( <TestComponent price={ 20 } /> );

		expect( container.props.maxPriceConstraint ).toBe( 20 );
	} );

	it( 'min price constraint should be updated when new price is set', () => {
		const renderer = TestRenderer.create( <TestComponent price={ 10 } /> );
		const container = renderer.root.findByType( 'div' );

		expect( container.props.minPriceConstraint ).toBe( 10 );

		renderer.update( <TestComponent price={ 20 } /> );

		expect( container.props.minPriceConstraint ).toBe( 20 );
	} );

	it( 'previous price constraint should be preserved when new price is not a infinite number', () => {
		const renderer = TestRenderer.create( <TestComponent price={ 10 } /> );
		const container = renderer.root.findByType( 'div' );

		expect( container.props.maxPriceConstraint ).toBe( 10 );

		renderer.update( <TestComponent price={ Infinity } /> );

		expect( container.props.maxPriceConstraint ).toBe( 10 );
	} );

	it( 'max price constraint should be higher if the price is decimal', () => {
		const renderer = TestRenderer.create(
			<TestComponent price={ 10.99 } />
		);
		const container = renderer.root.findByType( 'div' );

		expect( container.props.maxPriceConstraint ).toBe( 20 );

		renderer.update( <TestComponent price={ 19.99 } /> );

		expect( container.props.maxPriceConstraint ).toBe( 20 );
	} );

	it( 'min price constraint should be lower if the price is decimal', () => {
		const renderer = TestRenderer.create(
			<TestComponent price={ 9.99 } />
		);
		const container = renderer.root.findByType( 'div' );

		expect( container.props.minPriceConstraint ).toBe( 0 );

		renderer.update( <TestComponent price={ 19.99 } /> );

		expect( container.props.minPriceConstraint ).toBe( 10 );
	} );
} );
