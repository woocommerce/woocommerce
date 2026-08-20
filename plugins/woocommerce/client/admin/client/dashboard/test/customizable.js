/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';
import { useUserPreferences } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import CustomizableDashboard, {
	mergeSectionsWithDefaults,
} from '../customizable';

jest.mock( '../default-sections', () => ( {
	__esModule: true,
	DEFAULT_SECTIONS_FILTER: 'woocommerce_dashboard_default_sections',
	default: [
		{
			key: 'store-performance',
			component: () => null,
			title: 'Performance',
			isVisible: true,
			icon: 'arrow-right',
			hiddenBlocks: [ 'taxes/order_tax' ],
		},
		{
			key: 'charts',
			component: () => null,
			title: 'Charts',
			isVisible: true,
			icon: 'chart-bar',
			hiddenBlocks: [ 'coupons_amount' ],
		},
	],
} ) );

jest.mock( '../section', () => ( { title, onRemove } ) => (
	<div>
		{ title }
		<button title={ `Hide ${ title }` } onClick={ onRemove } />
	</div>
) );

jest.mock( '../../analytics/components/report-header', () => ( {
	ReportHeader: () => null,
} ) );

jest.mock( '@woocommerce/data', () => ( {
	settingsStore: 'wc/admin/settings',
	useUserPreferences: jest.fn(),
} ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	withSelect: () => ( Component ) => ( props ) => (
		<Component { ...props } defaultDateRange="period=month" />
	),
} ) );

describe( 'mergeSectionsWithDefaults', () => {
	it( 'returns the defaults when nothing is stored', () => {
		expect( mergeSectionsWithDefaults( undefined ) ).toHaveLength( 2 );
		expect( mergeSectionsWithDefaults( '' ) ).toHaveLength( 2 );
		expect( mergeSectionsWithDefaults( [] ) ).toHaveLength( 2 );
	} );

	it( 'returns the defaults when the stored preference is not an array', () => {
		// `[,,]` is not valid JSON, so it reaches the dashboard as a raw string.
		expect( mergeSectionsWithDefaults( '[,,]' ) ).toHaveLength( 2 );
		expect( mergeSectionsWithDefaults( {} ) ).toHaveLength( 2 );
	} );

	it( 'returns the defaults when every stored section is malformed', () => {
		expect( mergeSectionsWithDefaults( [ null, null ] ) ).toHaveLength( 2 );
		expect(
			mergeSectionsWithDefaults( [ undefined, 'charts', 42, {} ] )
		).toHaveLength( 2 );
	} );

	it( 'ignores a stored icon, which is a stale React node', () => {
		const [ charts ] = mergeSectionsWithDefaults( [
			{ key: 'charts', icon: { props: {} } },
		] );

		expect( charts.icon ).toBe( 'chart-bar' );
	} );

	it( 'keeps the well formed sections and drops the malformed ones', () => {
		const sections = mergeSectionsWithDefaults( [
			null,
			{ key: 'charts', title: 'My charts', isVisible: false },
		] );

		expect( sections ).toHaveLength( 2 );
		expect( sections[ 0 ] ).toMatchObject( {
			key: 'charts',
			title: 'My charts',
			isVisible: false,
		} );
		expect( sections[ 1 ] ).toMatchObject( {
			key: 'store-performance',
			title: 'Performance',
		} );
	} );

	it( 'returns the defaults when no stored key matches a default section', () => {
		const sections = mergeSectionsWithDefaults( [ { key: 'gone' } ] );

		expect( sections.map( ( section ) => section.key ) ).toEqual( [
			'store-performance',
			'charts',
		] );
	} );
} );

describe( 'CustomizableDashboard', () => {
	const updateUserPreferences = jest.fn();

	const renderDashboard = ( dashboardSections ) => {
		useUserPreferences.mockReturnValue( {
			updateUserPreferences,
			dashboard_sections: dashboardSections,
		} );

		return render(
			<CustomizableDashboard path="/analytics/overview" query={ {} } />
		);
	};

	beforeEach( () => {
		updateUserPreferences.mockReset();
	} );

	it( 'renders the default sections when the stored preference is corrupted', () => {
		const { getByText } = renderDashboard( [ null, null ] );

		expect( getByText( 'Performance' ) ).toBeInTheDocument();
		expect( getByText( 'Charts' ) ).toBeInTheDocument();
	} );

	it( 'repairs a corrupted preference once, without the React nodes', () => {
		const { rerender } = renderDashboard( [ null, null ] );

		expect( updateUserPreferences ).toHaveBeenCalledTimes( 1 );
		expect( updateUserPreferences ).toHaveBeenCalledWith( {
			dashboard_sections: [
				{
					key: 'store-performance',
					title: 'Performance',
					isVisible: true,
					hiddenBlocks: [ 'taxes/order_tax' ],
				},
				{
					key: 'charts',
					title: 'Charts',
					isVisible: true,
					hiddenBlocks: [ 'coupons_amount' ],
				},
			],
		} );

		// The preference is re-read on every render, so the repair must not loop.
		rerender(
			<CustomizableDashboard path="/analytics/overview" query={ {} } />
		);
		expect( updateUserPreferences ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'repairs a preference stored as a raw string', () => {
		renderDashboard( '[,,]' );

		expect( updateUserPreferences ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'leaves a well formed preference alone', () => {
		renderDashboard( [ { key: 'charts', isVisible: true } ] );

		expect( updateUserPreferences ).not.toHaveBeenCalled();
	} );

	it( 'never stores the React nodes when a section is customized', () => {
		const { getByTitle } = renderDashboard( [
			{ key: 'charts', title: 'Charts', isVisible: true },
			{
				key: 'store-performance',
				title: 'Performance',
				isVisible: true,
			},
		] );

		fireEvent.click( getByTitle( 'Hide Charts' ) );

		const [ [ { dashboard_sections: stored } ] ] =
			updateUserPreferences.mock.calls;
		stored.forEach( ( section ) => {
			expect( section ).not.toHaveProperty( 'icon' );
			expect( section ).not.toHaveProperty( 'component' );
		} );
	} );

	it( 'does not store anything when the dashboard was never customized', () => {
		renderDashboard( '' );

		expect( updateUserPreferences ).not.toHaveBeenCalled();
	} );
} );
