/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import AbandonedCartRecoveryRecommendations from '../abandoned-cart-recovery-recommendations';

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

describe( 'AbandonedCartRecoveryRecommendations', () => {
	beforeEach( () => {
		// The recommendations card calls useDispatch( pluginsStore ) for the
		// install hook and useDispatch( 'core/notices' ) inside MailPoetItem
		// for the success notice. A single stub covers both since neither test
		// exercises the install flow itself.
		( useDispatch as jest.Mock ).mockReturnValue( {
			installAndActivatePlugins: jest.fn().mockResolvedValue( undefined ),
			createSuccessNotice: jest.fn(),
		} );
	} );

	it( 'renders both items when neither plugin is active', () => {
		mockActivePlugins( [] );

		render( <AbandonedCartRecoveryRecommendations /> );

		expect( screen.queryByText( 'AutomateWoo' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'MailPoet' ) ).toBeInTheDocument();
	} );

	it( 'hides the AutomateWoo item when AutomateWoo is active', () => {
		mockActivePlugins( [ 'automatewoo' ] );

		render( <AbandonedCartRecoveryRecommendations /> );

		expect( screen.queryByText( 'AutomateWoo' ) ).not.toBeInTheDocument();
		expect( screen.queryByText( 'MailPoet' ) ).toBeInTheDocument();
	} );

	it( 'hides the MailPoet item when MailPoet is active', () => {
		mockActivePlugins( [ 'mailpoet' ] );

		render( <AbandonedCartRecoveryRecommendations /> );

		expect( screen.queryByText( 'MailPoet' ) ).not.toBeInTheDocument();
		expect( screen.queryByText( 'AutomateWoo' ) ).toBeInTheDocument();
	} );

	it( 'returns null when both plugins are already active', () => {
		mockActivePlugins( [ 'automatewoo', 'mailpoet' ] );

		const { container } = render(
			<AbandonedCartRecoveryRecommendations />
		);

		expect( container ).toBeEmptyDOMElement();
	} );
} );
