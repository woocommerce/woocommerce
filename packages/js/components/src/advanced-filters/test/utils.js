/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { textContent } from '../utils';

describe( 'textContent()', () => {
	test( 'should get text `Hello World`', () => {
		const component = (
			<div>
				<h1>Hello</h1> World
			</div>
		);

		expect( textContent( component ) ).toBe( 'Hello World' );
	} );

	test( 'render variable', () => {
		const component = (
			<div>
				<h1>Hello</h1> { 'World' + '2' }
			</div>
		);

		expect( textContent( component ) ).toBe( 'Hello World2' );
	} );

	test( 'render variable2', () => {
		const component = (
			<div>
				<h1>Hello</h1> { 1 + 1 }
			</div>
		);

		expect( textContent( component ) ).toBe( 'Hello 2' );
	} );

	test( 'should output empty string', () => {
		const component = <div />;

		expect( textContent( component ) ).toBe( '' );
	} );

	test( 'array children', () => {
		const component = (
			<div>
				<h1>Hello</h1> World
				{ [ 'a', <h2 key="b">b</h2> ] }
			</div>
		);

		expect( textContent( component ) ).toBe( 'Hello Worldab' );
	} );

	test( 'array children with null', () => {
		const component = (
			<div>
				<h1>Hello</h1> World
				{ [ 'a', null ] }
			</div>
		);

		expect( textContent( component ) ).toBe( 'Hello Worlda' );
	} );

	test( 'array component', () => {
		const component = [
			<h1 key="1">a</h1>,
			'b',
			'c',
			<div key="2">
				<h2>x</h2>y
			</div>,
		];

		expect( textContent( component ) ).toBe( 'abcxy' );
	} );
} );
