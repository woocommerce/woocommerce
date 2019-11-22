/** @format */
/**
 * External dependencies
 */
import { mount } from 'enzyme';

/**
 * Internal dependencies
 */
import { ReportSummary } from '../';

describe( 'ReportSummary', () => {
	function renderChart(
		type,
		primaryValue,
		secondaryValue,
		isError = false,
		isRequesting = false,
		props
	) {
		const selectedChart = {
			key: 'total_sales',
			label: 'Total Sales',
			type,
		};
		const charts = [ selectedChart ];
		const endpoint = 'revenue';
		const query = {};
		const summaryData = {
			totals: {
				primary: {
					total_sales: primaryValue,
				},
				secondary: {
					total_sales: secondaryValue,
				},
			},
			isError,
			isRequesting,
		};
		return mount(
			<ReportSummary
				charts={ charts }
				endpoint={ endpoint }
				query={ query }
				selectedChart={ selectedChart }
				summaryData={ summaryData }
				{ ...props }
			/>
		);
	}

	test( 'should set the correct prop values for the SummaryNumber components', () => {
		const reportChart = renderChart( 'number', 1000.5, 500.25 );
		const summaryNumber = reportChart.find( 'SummaryNumber' );

		expect( summaryNumber.props().value ).toBe( '1,000.5' );
		expect( summaryNumber.props().prevValue ).toBe( '500.25' );
		expect( summaryNumber.props().delta ).toBe( 100 );
	} );

	test( 'should format currency numbers properly', () => {
		const reportChart = renderChart( 'currency', 1000.5, 500.25 );
		const summaryNumber = reportChart.find( 'SummaryNumber' );

		expect( summaryNumber.props().value ).toBe( '$1,000.50' );
		expect( summaryNumber.props().prevValue ).toBe( '$500.25' );
		expect( summaryNumber.props().delta ).toBe( 100 );
	} );

	test( 'should format average numbers properly', () => {
		const reportChart = renderChart( 'average', 1000.5, 500.25 );
		const summaryNumber = reportChart.find( 'SummaryNumber' );

		expect( summaryNumber.props().value ).toBe( 1001 );
		expect( summaryNumber.props().prevValue ).toBe( 500 );
		expect( summaryNumber.props().delta ).toBe( 100 );
	} );

	test( 'should not break if secondary value is 0', () => {
		const reportChart = renderChart( 'number', 1000.5, 0 );
		const summaryNumber = reportChart.find( 'SummaryNumber' );

		expect( summaryNumber.props().value ).toBe( '1,000.5' );
		expect( summaryNumber.props().prevValue ).toBe( '0' );
		expect( summaryNumber.props().delta ).toBe( 0 );
	} );

	test( 'should not break with null or undefined values', () => {
		const reportChart = renderChart( 'number', null, undefined );
		const summaryNumber = reportChart.find( 'SummaryNumber' );

		expect( summaryNumber.props().value ).toBe( null );
		expect( summaryNumber.props().prevValue ).toBe( null );
		expect( summaryNumber.props().delta ).toBe( null );
	} );

	test( 'should show 0s when displaying an empty search', () => {
		const reportChart = renderChart( 'number', null, undefined, false, false, {
			emptySearchResults: true,
		} );
		const summaryNumber = reportChart.find( 'SummaryNumber' );

		expect( summaryNumber.props().value ).toBe( '0' );
		expect( summaryNumber.props().prevValue ).toBe( '0' );
		expect( summaryNumber.props().delta ).toBe( 0 );
	} );

	test( 'should display ReportError when isError is true', () => {
		const reportChart = renderChart( 'number', null, null, true );
		const reportError = reportChart.find( 'ReportError' );
		const summaryNumber = reportChart.find( 'SummaryNumber' );

		expect( reportError ).toHaveLength( 1 );
		expect( summaryNumber ).toHaveLength( 0 );
	} );

	test( 'should display SummaryListPlaceholder when isRequesting is true', () => {
		const reportChart = renderChart( 'number', null, null, false, true );
		const summaryListPlaceholder = reportChart.find( 'SummaryListPlaceholder' );
		const summaryNumber = reportChart.find( 'SummaryNumber' );

		expect( summaryListPlaceholder ).toHaveLength( 1 );
		expect( summaryNumber ).toHaveLength( 0 );
	} );
} );
