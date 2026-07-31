/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useDispatch, useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { useUser } from '@woocommerce/data';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { TaskListCompletedHeader } from '../task-list-completed-header';

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
	WEEK: 604800000,
} ) );

jest.mock( '@woocommerce/customer-effort-score', () => ( {
	ADMIN_INSTALL_TIMESTAMP_OPTION_NAME: 'woocommerce_admin_install_timestamp',
	ALLOW_TRACKING_OPTION_NAME: 'woocommerce_allow_tracking',
	SHOWN_FOR_ACTIONS_OPTION_NAME: 'woocommerce_ces_shown_for_actions',
	CustomerFeedbackModal: jest.fn( () => null ),
	CustomerFeedbackSimple: jest.fn( () => null ),
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '@woocommerce/experimental', () => ( {
	Text: ( { children }: { children: ReactNode } ) => (
		<span>{ children }</span>
	),
} ) );

jest.mock( '@wordpress/components', () => ( {
	Card: ( { children }: { children: ReactNode } ) => <div>{ children }</div>,
	CardHeader: ( { children }: { children: ReactNode } ) => (
		<div>{ children }</div>
	),
	DropdownMenu: jest.fn( () => null ),
} ) );

jest.mock( '@wordpress/icons', () => ( {
	moreVertical: 'more-vertical',
} ) );

describe( 'TaskListCompletedHeader', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		getOption.mockImplementation( ( option ) => {
			switch ( option ) {
				case 'woocommerce_allow_tracking':
					return 'yes';
				case 'woocommerce_admin_install_timestamp':
					return 123;
				case 'woocommerce_ces_shown_for_actions':
					return [];
				default:
					return undefined;
			}
		} );
		hasFinishedResolution.mockReturnValue( true );
		( useDispatch as jest.Mock ).mockReturnValue( {
			updateOptions: jest.fn(),
		} );
		( useSelect as jest.Mock ).mockImplementation( ( callback ) =>
			callback( () => ( {
				getOption,
				hasFinishedResolution,
			} ) )
		);
		( useUser as jest.Mock ).mockReturnValue( {
			currentUserCan,
		} );
	} );

	it( 'keeps the completed header but does not resolve CES options for an unauthorized user', () => {
		currentUserCan.mockReturnValue( false );

		render(
			<TaskListCompletedHeader
				hideTasks={ jest.fn() }
				customerEffortScore
			/>
		);

		expect(
			screen.getByText( 'You’ve completed store setup' )
		).toBeInTheDocument();
		expect( getOption ).not.toHaveBeenCalled();
	} );

	it.each( [ 'manage_woocommerce', 'edit_others_shop_orders' ] )(
		'resolves CES options when the user has %s',
		( capability ) => {
			currentUserCan.mockImplementation(
				( requestedCapability ) => requestedCapability === capability
			);

			render(
				<TaskListCompletedHeader
					hideTasks={ jest.fn() }
					customerEffortScore
				/>
			);

			expect( getOption ).toHaveBeenCalledWith(
				'woocommerce_admin_install_timestamp'
			);
		}
	);
} );
