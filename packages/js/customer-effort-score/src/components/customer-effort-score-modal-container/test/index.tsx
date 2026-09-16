/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useDispatch, useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { useUser } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ADMIN_INSTALL_TIMESTAMP_OPTION_NAME } from '../../../constants';
import { CustomerEffortScoreModalContainer } from '..';

const getVisibleCESModalData = jest.fn();
const getOption = jest.fn();
const hasFinishedResolution = jest.fn();
const currentUserCan = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn(),
	useSelect: jest.fn(),
} ) );

jest.mock( '@woocommerce/data', () => ( {
	optionsStore: 'options-store',
	useUser: jest.fn(),
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '../../../store', () => ( {
	__esModule: true,
	default: 'customer-effort-score-store',
} ) );

jest.mock( '../../customer-feedback-modal', () => ( {
	CustomerFeedbackModal: jest.fn( () => null ),
} ) );

describe( 'CustomerEffortScoreModalContainer', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		getOption.mockReturnValue( 123 );
		hasFinishedResolution.mockReturnValue( true );
		( useDispatch as jest.Mock ).mockReturnValue( {
			createSuccessNotice: jest.fn(),
			hideCesModal: jest.fn(),
		} );
		( useSelect as jest.Mock ).mockImplementation( ( callback ) =>
			callback( () => ( {
				getOption,
				getVisibleCESModalData,
				hasFinishedResolution,
			} ) )
		);
		( useUser as jest.Mock ).mockReturnValue( {
			currentUserCan,
		} );
	} );

	it( 'does not resolve the install timestamp for an unauthorized user', () => {
		currentUserCan.mockReturnValue( false );
		getVisibleCESModalData.mockReturnValue( {
			action: 'test_action',
			firstQuestion: 'How easy was this task?',
		} );

		render( <CustomerEffortScoreModalContainer /> );

		expect( getOption ).not.toHaveBeenCalled();
	} );

	it( 'does not resolve the install timestamp when no CES modal is visible', () => {
		currentUserCan.mockReturnValue( true );
		getVisibleCESModalData.mockReturnValue( undefined );

		render( <CustomerEffortScoreModalContainer /> );

		expect( getOption ).not.toHaveBeenCalled();
	} );

	it.each( [ 'manage_woocommerce', 'edit_others_shop_orders' ] )(
		'resolves the install timestamp for a visible modal when the user has %s',
		( capability ) => {
			currentUserCan.mockImplementation(
				( requestedCapability ) => requestedCapability === capability
			);
			getVisibleCESModalData.mockReturnValue( {
				action: 'test_action',
				firstQuestion: 'How easy was this task?',
			} );

			render( <CustomerEffortScoreModalContainer /> );

			expect( getOption ).toHaveBeenCalledWith(
				ADMIN_INSTALL_TIMESTAMP_OPTION_NAME
			);
		}
	);
} );
