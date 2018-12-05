/** @format */
/**
 * External dependencies
 */
import { shallow } from 'enzyme';

/**
 * Internal dependencies
 */
import { ReportChart } from '../';

jest.mock( '@woocommerce/components', () => ( {
	...require.requireActual( '@woocommerce/components' ),
	Chart: () => null,
} ) );

const path = '/analytics/revenue';
const data = {
	data: {
		intervals: [],
	},
	isEmpty: false,
	isError: false,
	isRequesting: false,
};
const selectedChart = {
	key: 'gross_revenue',
	label: 'Gross Revenue',
	type: 'currency',
};

describe( 'ReportChart', () => {
	test( 'should not set the mode prop by default', () => {
		const reportChart = shallow(
			<ReportChart
				path={ path }
				primaryData={ data }
				query={ {} }
				secondaryData={ data }
				selectedChart={ selectedChart }
			/>
		);
		const chart = reportChart.find( 'Chart' );

		expect( chart.props().mode ).toBeUndefined();
	} );

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
		const reportChart = shallow(
			<ReportChart
				filters={ filters }
				path={ path }
				primaryData={ data }
				query={ query }
				secondaryData={ data }
				selectedChart={ selectedChart }
			/>
		);

		const chart = reportChart.find( 'Chart' );

		expect( chart.props().mode ).toEqual( 'item-comparison' );
	} );
} );
