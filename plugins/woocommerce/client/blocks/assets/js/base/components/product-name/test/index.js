/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ProductName from '..';

describe( 'ProductName', () => {
	test( 'should not render a link if disabled is true', () => {
		const { container } = render(
			<ProductName disabled={ true } name="Test product" permalink="/" />
		);

		expect( container ).toMatchSnapshot();
	} );

	test( 'should render a link if disabled is false', () => {
		const { container } = render(
			<ProductName disabled={ false } name="Test product" permalink="/" />
		);

		expect( container ).toMatchSnapshot();
	} );

	test( 'should render a link if disabled is not defined', () => {
		const { container } = render(
			<ProductName name="Test product" permalink="/" />
		);

		expect( container ).toMatchSnapshot();
	} );

	test( 'should merge classes and props', () => {
		const { container } = render(
			<ProductName
				className="lorem-ipsum"
				name="Test product"
				permalink="/"
				rel="nofollow"
			/>
		);

		expect( container ).toMatchSnapshot();
	} );
} );
