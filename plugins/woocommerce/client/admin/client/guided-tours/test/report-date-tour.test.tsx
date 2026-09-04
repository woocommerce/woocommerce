/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useDispatch, useSelect } from '@wordpress/data';
import { useUser } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ReportDateTour } from '../report-date-tour';

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

jest.mock( '@woocommerce/components', () => ( {
	TourKit: jest.fn( () => null ),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getAdminLink: jest.fn( ( path ) => path ),
} ) );

describe( 'ReportDateTour', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		getOption.mockReturnValue( undefined );
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

	it( 'does not resolve the protected options for an unauthorized user', () => {
		currentUserCan.mockReturnValue( false );

		render(
			<ReportDateTour
				optionName="woocommerce_revenue_report_date_tour_shown"
				headingText="Dates have changed"
			/>
		);

		expect( getOption ).not.toHaveBeenCalled();
	} );

	it.each( [ 'manage_woocommerce', 'edit_others_shop_orders' ] )(
		'resolves the tour options when the user has %s',
		( capability ) => {
			currentUserCan.mockImplementation(
				( requestedCapability ) => requestedCapability === capability
			);

			render(
				<ReportDateTour
					optionName="woocommerce_revenue_report_date_tour_shown"
					headingText="Dates have changed"
				/>
			);

			expect( getOption ).toHaveBeenCalledWith(
				'woocommerce_revenue_report_date_tour_shown'
			);
			expect( getOption ).toHaveBeenCalledWith( 'woocommerce_date_type' );
		}
	);
} );
