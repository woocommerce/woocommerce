/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { StatusBadge } from '../status-badge';

describe( 'StatusBadge component', () => {
	it( 'renders the correct message for active status', () => {
		const { getByText } = render( <StatusBadge status="active" /> );
		expect( getByText( 'Active' ) ).toBeInTheDocument();
	} );

	it( 'renders the correct message for inactive status', () => {
		const { getByText } = render( <StatusBadge status="inactive" /> );
		expect( getByText( 'Inactive' ) ).toBeInTheDocument();
	} );

	it( 'renders the correct message for needs_setup status', () => {
		const { getByText } = render( <StatusBadge status="needs_setup" /> );
		expect( getByText( 'Action needed' ) ).toBeInTheDocument();
	} );

	it( 'renders the correct message for test_mode status', () => {
		const { getByText } = render( <StatusBadge status="test_mode" /> );
		expect( getByText( 'Test mode' ) ).toBeInTheDocument();
	} );

	it( 'renders the correct message for recommended status', () => {
		const { getByText } = render( <StatusBadge status="recommended" /> );
		expect( getByText( 'Recommended' ) ).toBeInTheDocument();
	} );

	it( 'renders the correct message for has_incentive status', () => {
		const { getByText } = render(
			<StatusBadge status="has_incentive" message={ 'Custom message' } />
		);
		expect( getByText( 'Custom message' ) ).toBeInTheDocument();
	} );

	it( 'applies the correct class for success statuses', () => {
		const { container } = render( <StatusBadge status="active" /> );
		expect( container.firstChild ).toHaveClass(
			'woocommerce-status-badge--success'
		);
	} );

	it( 'applies the correct class for warning statuses', () => {
		const { container } = render( <StatusBadge status="needs_setup" /> );
		expect( container.firstChild ).toHaveClass(
			'woocommerce-status-badge--warning'
		);
	} );

	it( 'applies the correct class for info statuses', () => {
		const { container } = render( <StatusBadge status="recommended" /> );
		expect( container.firstChild ).toHaveClass(
			'woocommerce-status-badge--info'
		);
	} );

	it( 'renders the correct custom message when message prop is passed', () => {
		const customMessage = 'Custom message';
		const { getByText } = render(
			<StatusBadge status="active" message={ customMessage } />
		);
		expect( getByText( customMessage ) ).toBeInTheDocument();
	} );
} );
