/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { ProductBreadcrumbs } from '../product-breadcrumbs';

describe( 'ProductBreadcrumbs', () => {
	it( 'should render a breadcrumb', () => {
		const { getByText } = render(
			<ProductBreadcrumbs
				breadcrumbs={ [
					{
						href: '/test-url',
						title: 'Test breadcrumb',
					},
				] }
			/>
		);
		const breadcrumb = getByText( 'Test breadcrumb' ) as HTMLAnchorElement;
		expect( breadcrumb ).toBeInTheDocument();
		expect( breadcrumb.href ).toBe( 'http://localhost/test-url' );
	} );

	it( 'should render multiple breadcrumbs', () => {
		const { getByText } = render(
			<ProductBreadcrumbs
				breadcrumbs={ [
					{
						href: '/test-url',
						title: 'Breadcrumb 1',
					},
					{
						href: '/test-url',
						title: 'Breadcrumb 2',
					},
				] }
			/>
		);
		expect( getByText( 'Breadcrumb 1' ) ).toBeInTheDocument();
		expect( getByText( 'Breadcrumb 2' ) ).toBeInTheDocument();
	} );

	it( 'should truncate breadcrumbs when more than 3 exist', () => {
		const { getByText, queryByText } = render(
			<ProductBreadcrumbs
				breadcrumbs={ [
					{
						href: '/test-url',
						title: 'Breadcrumb 1',
					},
					{
						href: '/test-url',
						title: 'Breadcrumb 2',
					},
					{
						href: '/test-url',
						title: 'Breadcrumb 3',
					},
					{
						href: '/test-url',
						title: 'Breadcrumb 4',
					},
				] }
			/>
		);
		expect( getByText( 'Breadcrumb 1' ) ).toBeInTheDocument();
		expect( queryByText( 'Breadcrumb 2' ) ).not.toBeInTheDocument();
		expect( queryByText( 'Breadcrumb 3' ) ).not.toBeInTheDocument();
		expect( getByText( 'Breadcrumb 4' ) ).toBeInTheDocument();
	} );
} );
