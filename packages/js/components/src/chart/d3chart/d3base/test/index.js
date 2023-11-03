/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { noop } from 'lodash';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import D3Base from '../index';

describe( 'D3base', () => {
	test( 'should have d3Base class', () => {
		const { container } = render( <D3Base drawChart={ noop } /> );
		expect( container.getElementsByClassName( 'd3-base' ) ).toHaveLength(
			1
		);
	} );

	test( 'should render an svg', () => {
		const { container } = render(
			<D3Base drawChart={ noop } height="100" width="100" />
		);
		expect( container.getElementsByTagName( 'svg' ) ).toHaveLength( 1 );
	} );

	test( 'should render a result of the drawChart prop', () => {
		const drawChart = ( svg ) => {
			return svg.append( 'circle' );
		};
		const { container } = render(
			<D3Base drawChart={ drawChart } height="100" width="100" />
		);
		expect( container.getElementsByTagName( 'circle' ) ).toHaveLength( 1 );
	} );
} );
