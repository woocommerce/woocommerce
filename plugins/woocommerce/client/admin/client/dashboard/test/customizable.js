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

const DEFAULT_SECTIONS = [
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
];

// Reassigned by the tests that need the `woocommerce_dashboard_default_sections`
// filter to have returned something else. Read through a getter so the mock is
// resolved when the dashboard dereferences it, not when the module is imported.
let mockDefaultSections = DEFAULT_SECTIONS;

jest.mock( '../default-sections', () => ( {
	__esModule: true,
	DEFAULT_SECTIONS_FILTER: 'woocommerce_dashboard_default_sections',
	get default() {
		return mockDefaultSections;
	},
} ) );

jest.mock( '../section', () => ( { title, onRemove, onTitleUpdate } ) => (
	<div>
		{ title }
		<button title={ `Hide ${ title }` } onClick={ onRemove } />
		<button
			title={ `Rename ${ title }` }
			onClick={ () => onTitleUpdate( { rendered: 'Renamed' } ) }
		/>
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

afterEach( () => {
	mockDefaultSections = DEFAULT_SECTIONS;
} );

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

	it( 'drops stored keys that no longer exist', () => {
		const sections = mergeSectionsWithDefaults( [ { key: 'gone' } ] );

		expect( sections.map( ( section ) => section.key ) ).toEqual( [
			'store-performance',
			'charts',
		] );
	} );

	it( 'ignores a stored hiddenBlocks that is not an array', () => {
		// Every section component calls `hiddenBlocks.includes()` on it.
		const [ charts ] = mergeSectionsWithDefaults( [
			{ key: 'charts', title: 'My charts', hiddenBlocks: null },
		] );

		expect( charts.hiddenBlocks ).toEqual( [ 'coupons_amount' ] );
		expect( charts.title ).toBe( 'My charts' );
	} );

	it( 'ignores a stored isVisible that is not a boolean', () => {
		// A non boolean hides the section without listing it under "Add more
		// sections", so the merchant cannot bring it back.
		const [ charts ] = mergeSectionsWithDefaults( [
			{ key: 'charts', title: 'My charts', isVisible: 0 },
		] );

		expect( charts.isVisible ).toBe( true );
		expect( charts.title ).toBe( 'My charts' );
	} );

	it( 'ignores a stored title that is not a string', () => {
		// The title is rendered as a React child by every section header.
		const [ charts ] = mergeSectionsWithDefaults( [
			{
				key: 'charts',
				title: { rendered: 'My charts' },
				isVisible: false,
			},
		] );

		expect( charts.title ).toBe( 'Charts' );
		expect( charts.isVisible ).toBe( false );
	} );

	it( 'drops a default section that no section can be built from', () => {
		// The default sections filter is a third party surface too.
		mockDefaultSections = [
			null,
			'charts',
			{ component: () => null, title: 'No key', isVisible: true },
			{ key: 'no-component', title: 'No component', isVisible: true },
			...DEFAULT_SECTIONS,
		];

		const sections = mergeSectionsWithDefaults( undefined );

		expect( sections.map( ( section ) => section.key ) ).toEqual( [
			'store-performance',
			'charts',
		] );
	} );

	it( 'fills in a default hiddenBlocks that is not an array', () => {
		// Nothing sits behind a default, and every section component calls
		// `hiddenBlocks.includes()` on it.
		mockDefaultSections = [
			{ ...DEFAULT_SECTIONS[ 1 ], hiddenBlocks: null },
		];

		const [ charts ] = mergeSectionsWithDefaults( [
			{ key: 'charts', hiddenBlocks: 'coupons_amount' },
		] );

		expect( charts.hiddenBlocks ).toEqual( [] );
	} );

	it( 'falls back to a readable value for a corrupted default title', () => {
		// The title is rendered as a React child by every section header.
		mockDefaultSections = [ { ...DEFAULT_SECTIONS[ 1 ], title: {} } ];

		const [ charts ] = mergeSectionsWithDefaults( [
			{ key: 'charts', title: { rendered: 'My charts' } },
		] );

		expect( charts.title ).toBe( '' );
	} );

	it( 'keeps a default section that registers a truthy isVisible', () => {
		// The filter never enforced a boolean and the dashboard rendered any
		// truthy value, so dropping it would make the section unreachable.
		mockDefaultSections = [
			{ ...DEFAULT_SECTIONS[ 0 ], isVisible: 1 },
			{ ...DEFAULT_SECTIONS[ 1 ], isVisible: 'yes' },
		];

		const sections = mergeSectionsWithDefaults( undefined );

		expect( sections.map( ( section ) => section.isVisible ) ).toEqual( [
			true,
			true,
		] );
	} );

	it( 'offers a default section that registers a falsy isVisible', () => {
		// `undefined` is neither visible nor listed under "Add more sections".
		mockDefaultSections = [
			{ ...DEFAULT_SECTIONS[ 0 ], isVisible: 0 },
			{ ...DEFAULT_SECTIONS[ 1 ], isVisible: undefined },
		];

		const sections = mergeSectionsWithDefaults( undefined );

		expect( sections.map( ( section ) => section.isVisible ) ).toEqual( [
			false,
			false,
		] );
	} );

	it( 'keeps a default section that registers a numeric title', () => {
		// React prints a number, so the header rendered it before.
		mockDefaultSections = [ { ...DEFAULT_SECTIONS[ 1 ], title: 2026 } ];

		const [ charts ] = mergeSectionsWithDefaults( undefined );

		expect( charts.title ).toBe( '2026' );
	} );

	it( 'keeps a default section keyed by a number', () => {
		// The key is matched by strict equality and round trips through the
		// stored JSON, so a number ties the section back to its default.
		mockDefaultSections = [ { ...DEFAULT_SECTIONS[ 1 ], key: 42 } ];

		const sections = mergeSectionsWithDefaults( [
			{ key: 42, title: 'My charts' },
		] );

		expect( sections ).toHaveLength( 1 );
		expect( sections[ 0 ] ).toMatchObject( {
			key: 42,
			title: 'My charts',
		} );
	} );
} );

describe( 'CustomizableDashboard', () => {
	const updateUserPreferences = jest.fn();

	const renderDashboard = ( dashboardSections ) => {
		// The stored preference is JSON parsed on every render, so the dashboard
		// gets a new reference each time and the repair has to guard itself.
		useUserPreferences.mockImplementation( () => ( {
			updateUserPreferences,
			dashboard_sections: Array.isArray( dashboardSections )
				? [ ...dashboardSections ]
				: dashboardSections,
		} ) );

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

	it( 'renders a registered section that is visible by a truthy value', () => {
		// An extension section registered the way the released dashboard
		// accepted it, on a store that never customized the dashboard.
		mockDefaultSections = [
			...DEFAULT_SECTIONS,
			{
				key: 'my-extension',
				component: () => null,
				title: 'Mine',
				isVisible: 1,
				hiddenBlocks: [],
			},
		];

		const { getByText } = renderDashboard( undefined );

		expect( getByText( 'Mine' ) ).toBeInTheDocument();
	} );

	it( 'offers a hidden section the filter registered without an icon', () => {
		// `Icon` clones the icon it is handed, so anything but a React element
		// throws where the merchant goes to bring the section back.
		mockDefaultSections = [
			...DEFAULT_SECTIONS,
			{
				key: 'my-extension',
				component: () => null,
				title: 'Mine',
				isVisible: 0,
				hiddenBlocks: [],
			},
		];

		const { getByTitle } = renderDashboard( undefined );
		fireEvent.click( getByTitle( 'Add more sections' ) );

		expect( getByTitle( 'Add Mine section' ) ).toBeInTheDocument();
	} );

	it( 'renders the icon of a hidden section that provides one', () => {
		mockDefaultSections = [
			...DEFAULT_SECTIONS,
			{
				key: 'my-extension',
				component: () => null,
				title: 'Mine',
				isVisible: false,
				icon: <svg />,
				hiddenBlocks: [],
			},
		];

		// The dropdown renders in a popover, so it lands outside `container`.
		const { baseElement, getByTitle } = renderDashboard( undefined );
		fireEvent.click( getByTitle( 'Add more sections' ) );

		expect(
			baseElement.querySelector( '.my-extension__icon' )
		).toBeInTheDocument();
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

	it( 'repairs a preference holding a corrupted hiddenBlocks', () => {
		renderDashboard( [
			{ key: 'charts', isVisible: true, hiddenBlocks: null },
		] );

		expect( updateUserPreferences ).toHaveBeenCalledTimes( 1 );
		expect( updateUserPreferences ).toHaveBeenCalledWith( {
			dashboard_sections: expect.arrayContaining( [
				expect.objectContaining( {
					key: 'charts',
					hiddenBlocks: [ 'coupons_amount' ],
				} ),
			] ),
		} );
	} );

	it( 'repairs a preference holding a corrupted title', () => {
		renderDashboard( [ { key: 'charts', isVisible: true, title: {} } ] );

		expect( updateUserPreferences ).toHaveBeenCalledTimes( 1 );
		expect( updateUserPreferences ).toHaveBeenCalledWith( {
			dashboard_sections: expect.arrayContaining( [
				expect.objectContaining( { key: 'charts', title: 'Charts' } ),
			] ),
		} );
	} );

	it( 'repairs a preference holding a corrupted isVisible', () => {
		renderDashboard( [ { key: 'charts', isVisible: 0 } ] );

		expect( updateUserPreferences ).toHaveBeenCalledTimes( 1 );
		expect( updateUserPreferences ).toHaveBeenCalledWith( {
			dashboard_sections: expect.arrayContaining( [
				expect.objectContaining( { key: 'charts', isVisible: true } ),
			] ),
		} );
	} );

	it( 'keeps a stored section the dashboard does not know about', () => {
		// A section registered by an extension that is currently deactivated.
		renderDashboard( [
			null,
			{ key: 'my-extension', title: 'Mine', isVisible: false },
		] );

		expect( updateUserPreferences ).toHaveBeenCalledWith( {
			dashboard_sections: expect.arrayContaining( [
				{ key: 'my-extension', title: 'Mine', isVisible: false },
			] ),
		} );
	} );

	it( 'keeps an unknown section that holds a corrupted field', () => {
		// There is no default to patch the field up from, so it is dropped and
		// the rest of the entry survives.
		renderDashboard( [
			null,
			{
				key: 'my-extension',
				title: 'Mine',
				isVisible: false,
				hiddenBlocks: null,
			},
		] );

		expect( updateUserPreferences ).toHaveBeenCalledWith( {
			dashboard_sections: expect.arrayContaining( [
				{ key: 'my-extension', title: 'Mine', isVisible: false },
			] ),
		} );
	} );

	it( 'does not store anything when there is nothing usable to fall back to', () => {
		// Storing an empty list would only be repaired again on the next visit.
		mockDefaultSections = [];

		renderDashboard( [ null, null ] );

		expect( updateUserPreferences ).not.toHaveBeenCalled();
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

	it( 'sanitizes a value a section component hands back', () => {
		// The update callbacks are passed to third party section components.
		const { getByTitle } = renderDashboard( [
			{ key: 'charts', title: 'Charts', isVisible: true },
		] );

		fireEvent.click( getByTitle( 'Rename Charts' ) );

		expect( updateUserPreferences ).toHaveBeenCalledWith( {
			dashboard_sections: expect.arrayContaining( [
				{
					key: 'charts',
					isVisible: true,
					hiddenBlocks: [ 'coupons_amount' ],
				},
			] ),
		} );
	} );

	it( 'does not store anything when the dashboard was never customized', () => {
		renderDashboard( '' );

		expect( updateUserPreferences ).not.toHaveBeenCalled();
	} );

	it( 'does not store anything when the stored preference is empty', () => {
		// An empty list means the defaults are in use, same as no preference.
		renderDashboard( [] );

		expect( updateUserPreferences ).not.toHaveBeenCalled();
	} );
} );
