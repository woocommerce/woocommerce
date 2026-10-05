import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useUser } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import RecommendationsEligibilityWrapper from '../recommendations-eligibility-wrapper';
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		useSelect: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/data', async () => {
	const mock = {
		...( await vi.importActual( '@woocommerce/data' ) ),
		useUser: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const RecommendationsEligibilityMock = () => (
	<RecommendationsEligibilityWrapper>
		<span>mocked children</span>
	</RecommendationsEligibilityWrapper>
);
describe( 'RecommendationsEligibilityWrapper', () => {
	beforeEach( () => {
		useUser.mockReturnValue( {
			currentUserCan: () => true,
		} );
		useSelect.mockImplementation( ( fn ) =>
			fn( () => ( {
				getOption: () => 'yes',
				hasFinishedResolution: () => true,
			} ) )
		);
	} );
	it( 'should not render its children when the user cannot install plugins', () => {
		const currentUserCanMock = vi.fn().mockReturnValue( false );
		useUser.mockReturnValue( {
			currentUserCan: currentUserCanMock,
		} );
		const { rerender } = render( <RecommendationsEligibilityMock /> );
		expect(
			screen.queryByText( 'mocked children' )
		).not.toBeInTheDocument();
		expect( currentUserCanMock ).toHaveBeenCalledWith( 'install_plugins' );

		// changing the "currentUserCanMock" to return `true` will render the children
		currentUserCanMock.mockReturnValue( true );
		rerender( <RecommendationsEligibilityMock /> );
		expect( screen.queryByText( 'mocked children' ) ).toBeInTheDocument();
	} );
	it( 'should not render its children when the marketplace suggestions are being loaded', () => {
		useSelect.mockImplementation( ( fn ) =>
			fn( () => ( {
				getOption: () => 'yes',
				hasFinishedResolution: () => false,
			} ) )
		);
		const { rerender } = render( <RecommendationsEligibilityMock /> );
		expect(
			screen.queryByText( 'mocked children' )
		).not.toBeInTheDocument();

		// changing the "hasFinishedResolution" to return `false` will render the children
		useSelect.mockImplementation( ( fn ) =>
			fn( () => ( {
				getOption: () => 'yes',
				hasFinishedResolution: () => true,
			} ) )
		);
		rerender( <RecommendationsEligibilityMock /> );
		expect( screen.queryByText( 'mocked children' ) ).toBeInTheDocument();
	} );
	it( 'should render its children', () => {
		useSelect.mockImplementation( ( fn ) =>
			fn( () => ( {
				getOption: () => 'yes',
				hasFinishedResolution: () => true,
			} ) )
		);
		useUser.mockReturnValue( {
			currentUserCan: () => true,
		} );
		render( <RecommendationsEligibilityMock /> );
		expect( screen.queryByText( 'mocked children' ) ).toBeInTheDocument();
	} );
} );
