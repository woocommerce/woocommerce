import { render, screen } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import { ReportChart } from '../';
/**
 * Internal dependencies
 */
import { getChartMode, getSelectedFilter } from '../utils';

describe( 'ReportChart', () => {
	test( 'should set the mode prop depending on the active filter', () => {
		const filters = [
			{
				param: 'filter',
				showFilters: () => true,
				filters: [
					{
						value: 'lorem-ipsum',
						chartMode: 'item-comparison',
						settings: {
							param: 'filter2',
						},
					},
				],
			},
		];
		const query = { filter: 'lorem-ipsum', filter2: 'ipsum-lorem' };
		const selectedFilter = getSelectedFilter( filters, query );
		const mode = getChartMode( selectedFilter, query );
		expect( mode ).toEqual( 'item-comparison' );
	} );
} );

describe( 'currency chart completeness', () => {
	const report = ( missing ) => ( {
		data: { totals: { reporting_missing_orders: missing }, intervals: [] },
	} );
	beforeEach( () => {
		jest.spyOn(
			ReportChart.prototype,
			'renderTimeComparison'
		).mockReturnValue( <div>Chart plotted</div> );
		jest.spyOn(
			ReportChart.prototype,
			'renderItemComparison'
		).mockReturnValue( <div>Chart plotted</div> );
	} );
	afterEach( () => jest.restoreAllMocks() );

	test.each( [
		[ 1, 0 ],
		[ 0, 1 ],
	] )(
		'withholds an incomplete monetary comparison (%s, %s)',
		( primary, secondary ) => {
			render(
				<ReportChart
					mode="time-comparison"
					query={ {} }
					selectedChart={ { type: 'currency' } }
					primaryData={ report( primary ) }
					secondaryData={ report( secondary ) }
				/>
			);
			expect( screen.getByRole( 'status' ) ).toHaveTextContent(
				'Chart unavailable'
			);
			expect(
				screen.queryByText( 'Chart plotted' )
			).not.toBeInTheDocument();
		}
	);

	test( 'keeps count charts available', () => {
		render(
			<ReportChart
				mode="time-comparison"
				query={ {} }
				selectedChart={ { type: 'number' } }
				primaryData={ report( 1 ) }
				secondaryData={ report( 1 ) }
			/>
		);
		expect( screen.getByText( 'Chart plotted' ) ).toBeInTheDocument();
	} );

	test( 'updates when only completeness metadata changes', () => {
		const props = {
			mode: 'time-comparison',
			query: {},
			selectedChart: { type: 'currency' },
			primaryData: report( 0 ),
			secondaryData: report( 0 ),
		};
		const { rerender } = render( <ReportChart { ...props } /> );
		rerender( <ReportChart { ...props } primaryData={ report( 1 ) } /> );
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Chart unavailable'
		);
	} );

	test( 'item comparison ignores the secondary period', () => {
		render(
			<ReportChart
				mode="item-comparison"
				query={ {} }
				selectedChart={ { type: 'currency' } }
				primaryData={ report( 0 ) }
				secondaryData={ report( 1 ) }
			/>
		);
		expect( screen.getByText( 'Chart plotted' ) ).toBeInTheDocument();
	} );

	test.each( [
		{
			totals: {
				segments: [ { subtotals: { reporting_missing_orders: '1' } } ],
			},
			intervals: [],
		},
		{
			totals: {},
			intervals: [ { subtotals: { reporting_missing_orders: 1 } } ],
		},
	] )( 'withholds currency charts with incomplete nested data', ( data ) => {
		render(
			<ReportChart
				mode="item-comparison"
				query={ {} }
				selectedChart={ { type: 'currency' } }
				primaryData={ { data } }
				secondaryData={ report( 0 ) }
			/>
		);
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Chart unavailable'
		);
	} );

	test.each( [ 'isRequesting', 'isError' ] )(
		'preserves the existing %s rendering path',
		( flag ) => {
			render(
				<ReportChart
					mode="time-comparison"
					query={ {} }
					selectedChart={ { type: 'currency' } }
					primaryData={ { ...report( 1 ), [ flag ]: true } }
					secondaryData={ report( 0 ) }
				/>
			);
			expect( screen.getByText( 'Chart plotted' ) ).toBeInTheDocument();
		}
	);

	test( 'withholds unknown coupon allocations and restores a qualified chart', () => {
		const props = {
			mode: 'item-comparison',
			query: {},
			selectedChart: { type: 'currency' },
			primaryData: report( 0 ),
			secondaryData: report( 0 ),
		};
		const { rerender } = render( <ReportChart { ...props } /> );
		const incomplete = {
			data: {
				totals: {
					segments: [
						{
							subtotals: {
								amount: null,
								allocation_missing_orders: 1,
							},
						},
					],
				},
			},
		};
		rerender( <ReportChart { ...props } primaryData={ incomplete } /> );
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'historical coupon allocations are missing'
		);
		expect( screen.queryByText( 'Chart plotted' ) ).not.toBeInTheDocument();
		rerender(
			<ReportChart
				{ ...props }
				primaryData={ incomplete }
				selectedChart={ { type: 'number' } }
			/>
		);
		expect( screen.getByText( 'Chart plotted' ) ).toBeInTheDocument();
		rerender( <ReportChart { ...props } /> );
		expect( screen.getByText( 'Chart plotted' ) ).toBeInTheDocument();
	} );

	test( 'restores the chart when currency data becomes complete', () => {
		const props = {
			mode: 'time-comparison',
			query: {},
			selectedChart: { type: 'currency' },
			primaryData: report( 1 ),
			secondaryData: report( 0 ),
		};
		const { rerender } = render( <ReportChart { ...props } /> );
		rerender( <ReportChart { ...props } primaryData={ report( 0 ) } /> );
		expect( screen.getByText( 'Chart plotted' ) ).toBeInTheDocument();
	} );
} );
