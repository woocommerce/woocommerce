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
