/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import CheckoutRecoveryRecommendations from '../checkout-recovery-recommendations';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
} ) );

jest.mock( '../../settings-recommendations/dismissable-list', () => ( {
	DismissableList: ( { children }: { children: React.ReactNode } ) =>
		children,
	DismissableListHeading: ( { children }: { children: React.ReactNode } ) =>
		children,
} ) );

const mockActivePlugins = ( plugins: string[] ) => {
	( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
		fn( () => ( {
			getActivePlugins: () => plugins,
		} ) )
	);
};

describe( 'CheckoutRecoveryRecommendations', () => {
	it( 'renders both items when neither plugin is active', () => {
		mockActivePlugins( [] );

		render( <CheckoutRecoveryRecommendations /> );

		expect( screen.queryByText( 'AutomateWoo' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'MailPoet' ) ).toBeInTheDocument();
	} );

	it( 'hides the AutomateWoo item when AutomateWoo is active', () => {
		mockActivePlugins( [ 'automatewoo' ] );

		render( <CheckoutRecoveryRecommendations /> );

		expect( screen.queryByText( 'AutomateWoo' ) ).not.toBeInTheDocument();
		expect( screen.queryByText( 'MailPoet' ) ).toBeInTheDocument();
	} );

	it( 'hides the MailPoet item when MailPoet is active', () => {
		mockActivePlugins( [ 'mailpoet' ] );

		render( <CheckoutRecoveryRecommendations /> );

		expect( screen.queryByText( 'MailPoet' ) ).not.toBeInTheDocument();
		expect( screen.queryByText( 'AutomateWoo' ) ).toBeInTheDocument();
	} );

	it( 'returns null when both plugins are already active', () => {
		mockActivePlugins( [ 'automatewoo', 'mailpoet' ] );

		const { container } = render( <CheckoutRecoveryRecommendations /> );

		expect( container ).toBeEmptyDOMElement();
	} );
} );
