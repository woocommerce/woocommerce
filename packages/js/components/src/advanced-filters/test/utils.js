/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getInterpolatedString, textContent } from '../utils';

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

describe( 'getInterpolatedString()', () => {
	test( 'should return string `<h1>my title</h1>`', () => {
		const interpolatedString = '{{h1}}my title{{/h1}}';

		expect( getInterpolatedString( interpolatedString ) ).toBe(
			'<h1>my title</h1>'
		);
	} );

	test( 'should return string `<tag1>text</tag1> - <tag2>text 2</tag2>`', () => {
		const interpolatedString =
			'{{tag1}}text{{/tag1}} - {{tag2}}text 2{{/tag2}}';

		expect( getInterpolatedString( interpolatedString ) ).toBe(
			'<tag1>text</tag1> - <tag2>text 2</tag2>'
		);
	} );

	test( 'should return string `<tag1><tag2> my text </tag2></tag1>`', () => {
		const interpolatedString =
			'{{tag1}}{{tag2}} my text {{/tag2}}{{/tag1}}';

		expect( getInterpolatedString( interpolatedString ) ).toBe(
			'<tag1><tag2> my text </tag2></tag1>'
		);
	} );

	test( 'should return string `<tag>my text</tag> <tag2 /> <tag3 />`', () => {
		const interpolatedString =
			'{{tag}}my text{{/tag}} {{tag2 /}} {{tag3 /}}';
		expect( getInterpolatedString( interpolatedString ) ).toBe(
			'<tag>my text</tag> <tag2 /> <tag3 />'
		);
	} );
} );
