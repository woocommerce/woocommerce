import { describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { EmbeddedBodyLayout } from '../embedded-body-layout';
vi.mock( '@woocommerce/customer-effort-score', () => {
	const mock = {
		triggerExitPageCesSurvey: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		resolveSelect: vi.fn().mockReturnValue( {
			getOption: vi.fn(),
		} ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/data', () => {
	const mock = {
		useUser: () => ( {
			currentUserCan: vi.fn(),
		} ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../payments', () => {
	const mock = {
		PaymentRecommendations: ( {
			page,
			tab,
			section,
		}: {
			page: string;
			tab: string;
			section?: string;
		} ) => (
			<div>
				payment_recommendations
				<span>page:{ page }</span>
				<span>tab:{ tab }</span>
				<span>section:{ section || '' }</span>
			</div>
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const stubLocation = ( location: string ) => {
	vi.spyOn( window, 'location', 'get' ).mockReturnValue( {
		...window.location,
		search: location,
	} );
};
describe( 'Embedded layout', () => {
	it( 'should render a fill component with matching name, and provide query params', async () => {
		stubLocation( '?page=settings&tab=test' );
		const { queryByText } = render( <EmbeddedBodyLayout /> );
		expect( queryByText( 'payment_recommendations' ) ).toBeInTheDocument();
		expect( queryByText( 'page:settings' ) ).toBeInTheDocument();
		expect( queryByText( 'tab:test' ) ).toBeInTheDocument();
		expect( queryByText( 'section:' ) ).toBeInTheDocument();
	} );
	it( 'should render a component added through the filter - woocommerce_admin_embedded_layout_components', () => {
		addFilter(
			'woocommerce_admin_embedded_layout_components',
			'namespace',
			( components ) => {
				return [
					...components,
					() => {
						return <div>new_component</div>;
					},
				];
			}
		);
		stubLocation( '?page=settings&tab=test' );
		const { queryByText } = render( <EmbeddedBodyLayout /> );
		expect( queryByText( 'new_component' ) ).toBeInTheDocument();
	} );
} );
