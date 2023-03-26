/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { EmbeddedBodyLayout } from '../embedded-body-layout';

jest.mock( '@woocommerce/customer-effort-score', () => ( {
	triggerExitPageCesSurvey: jest.fn(),
} ) );
jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	resolveSelect: jest.fn().mockReturnValue( {
		getOption: jest.fn(),
	} ),
} ) );
jest.mock( '@woocommerce/data', () => ( {
	useUser: () => ( {
		currentUserCan: jest.fn(),
	} ),
} ) );
jest.mock( '../../payments', () => ( {
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
} ) );

const stubLocation = ( location: string ) => {
	jest.spyOn( window, 'location', 'get' ).mockReturnValue( {
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
