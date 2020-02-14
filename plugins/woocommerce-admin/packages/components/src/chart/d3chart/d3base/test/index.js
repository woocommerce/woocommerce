/**
 * External dependencies
 *
 */
import { shallow, mount } from 'enzyme';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import D3Base from '../index';

describe( 'D3base', () => {
	const shallowWithoutLifecycle = ( arg ) =>
		shallow( arg, { disableLifecycleMethods: true } );

	test( 'should have d3Base class', () => {
		const base = shallowWithoutLifecycle( <D3Base drawChart={ noop } /> );
		expect( base.find( '.d3-base' ) ).toHaveLength( 1 );
	} );

	test( 'should render an svg', () => {
		const base = mount(
			<D3Base drawChart={ noop } height="100" width="100" />
		);
		expect( base.render().find( 'svg' ) ).toHaveLength( 1 );
	} );

	test( 'should render a result of the drawChart prop', () => {
		const drawChart = ( svg ) => {
			return svg.append( 'circle' );
		};
		const base = mount(
			<D3Base drawChart={ drawChart } height="100" width="100" />
		);
		expect( base.render().find( 'circle' ) ).toHaveLength( 1 );
	} );
} );
