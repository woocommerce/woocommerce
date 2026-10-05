/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import CustomersReportTable from '../table';

const captured = { getRowsContent: null, getHeadersContent: null };

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

const mockCountriesStore = ( countries ) => {
	useSelect.mockReturnValue( {
		countries,
		loadingCountries: false,
	} );
};

jest.mock( '@woocommerce/data', () => ( {
	...jest.requireActual( '@woocommerce/data' ),
	COUNTRIES_STORE_NAME: 'wc/admin/countries',
} ) );

jest.mock( '@woocommerce/currency', () => {
	const React = require( 'react' );
	const config = {
		formatAmount: ( v ) => String( v ),
		formatDecimal: ( v ) => v,
		getCurrencyConfig: () => ( {} ),
	};
	return {
		CurrencyContext: React.createContext( config ),
		CurrencyFactory: () => config,
	};
} );

jest.mock( '~/utils/admin-settings', () => ( {
	getAdminSetting: ( _key, fallback ) => fallback,
} ) );

jest.mock( '../../../components/report-table', () => ( {
	__esModule: true,
	default: ( props ) => {
		captured.getRowsContent = props.getRowsContent;
		captured.getHeadersContent = props.getHeadersContent;
		return null;
	},
} ) );

const baseCustomer = {
	id: 1,
	name: 'Alice',
	username: 'alice',
	email: 'alice@example.com',
	user_id: null,
	date_last_active: null,
	date_registered: null,
	orders_count: 0,
	total_spend: 0,
	avg_order_value: 0,
	postcode: '',
	city: '',
	state: '',
	country: '',
};

// getHeadersContent in table.js, in order. This is also the browser-side CSV
// export order, so it must match Controller::get_export_columns() in
// src/Admin/API/Reports/Customers/Controller.php.
const HEADER_KEYS = [
	'name',
	'username',
	'date_last_active',
	'date_registered',
	'email',
	'orders_count',
	'total_spend',
	'avg_order_value',
	'country',
	'city',
	'state',
	'postcode',
	'billing_phone',
	'shipping_phone',
	'role',
];

const col = ( key ) => HEADER_KEYS.indexOf( key );

const COUNTRY_COL = col( 'country' );

function getCountryCell( customer ) {
	captured.getRowsContent = null;
	render( <CustomersReportTable query={ {} } /> );
	const rows = captured.getRowsContent( [ customer ] );
	return rows[ 0 ][ COUNTRY_COL ];
}

function renderCellDisplay( display ) {
	return render(
		<table>
			<tbody>
				<tr>
					<td>{ display }</td>
				</tr>
			</tbody>
		</table>
	);
}

describe( 'CustomersReportTable country cell', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'renders the decoded country name for a known country code', () => {
		mockCountriesStore( [
			{ code: 'FR', name: 'France', states: [] },
			{ code: 'IT', name: 'Italy', states: [] },
		] );
		const cell = getCountryCell( { ...baseCustomer, country: 'FR' } );

		expect( cell.value ).toBe( 'FR' );

		const { getByText, getAllByText } = renderCellDisplay( cell.display );
		// The aria-hidden span shows the ISO code.
		expect( getByText( 'FR' ) ).toBeInTheDocument();
		// The screen-reader span shows the human-readable name.
		expect( getAllByText( 'France' ).length ).toBeGreaterThan( 0 );
	} );

	it( 'decodes HTML entities in country names', () => {
		mockCountriesStore( [
			{ code: 'CI', name: 'C&ocirc;te d&#039;Ivoire', states: [] },
		] );
		const cell = getCountryCell( { ...baseCustomer, country: 'CI' } );

		const { getAllByText } = renderCellDisplay( cell.display );
		expect( getAllByText( "Côte d'Ivoire" ).length ).toBeGreaterThan( 0 );
	} );

	it( 'renders without crashing when the country code is unknown', () => {
		mockCountriesStore( [ { code: 'FR', name: 'France', states: [] } ] );
		const cell = getCountryCell( { ...baseCustomer, country: 'XX' } );

		expect( () => renderCellDisplay( cell.display ) ).not.toThrow();
	} );

	// Regression for woocommerce/woocommerce#64555. Before the fix, getCountryName
	// did `countries[ code ]`, which on an Array treats the key as an index.
	// A customer record with country = "0" therefore resolved to the first
	// country object, which React then refused to render as a child.
	it( 'does not return a country object when the country code coerces to an array index (#64555)', () => {
		mockCountriesStore( [
			{
				code: 'FR',
				name: 'France',
				states: [],
				_links: { self: [ { href: '' } ] },
			},
		] );
		const cell = getCountryCell( { ...baseCustomer, country: '0' } );

		expect( () => renderCellDisplay( cell.display ) ).not.toThrow();
	} );

	it( 'does not return an Array prototype value when the country code is a method name (#64555)', () => {
		mockCountriesStore( [ { code: 'FR', name: 'France', states: [] } ] );
		const cell = getCountryCell( {
			...baseCustomer,
			country: 'find',
		} );

		expect( () => renderCellDisplay( cell.display ) ).not.toThrow();
	} );
} );

describe( 'CustomersReportTable column order', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	// The same Download button exports the table client-side for single-page
	// reports and server-side for larger ones, so drift between the two orders
	// makes identical reports produce differently ordered CSVs.
	it( 'keeps the header order in sync with the server-side CSV export', () => {
		mockCountriesStore( [] );
		captured.getHeadersContent = null;
		render( <CustomersReportTable query={ {} } /> );

		expect(
			captured.getHeadersContent().map( ( header ) => header.key )
		).toEqual( HEADER_KEYS );
	} );

	it( 'emits every cell under its own header', () => {
		mockCountriesStore( [] );
		captured.getRowsContent = null;
		render( <CustomersReportTable query={ {} } /> );

		// One distinct value per field, so a cell landing under the wrong
		// header surfaces as a mismatch instead of passing by coincidence.
		const customer = {
			name: 'Alice',
			username: 'alice',
			date_last_active: '2026-08-01T00:00:00',
			date_registered: '2026-07-01T00:00:00',
			email: 'alice@example.com',
			orders_count: 7,
			total_spend: 123,
			avg_order_value: 45,
			country: 'FR',
			city: 'Paris',
			state: 'IDF',
			postcode: '75001',
			billing_phone: '555-32123',
			shipping_phone: '555-99887',
			role: 'Customer',
		};

		const row = captured.getRowsContent( [ customer ] )[ 0 ];

		expect( row ).toHaveLength( HEADER_KEYS.length );
		expect(
			Object.fromEntries(
				HEADER_KEYS.map( ( key, index ) => [ key, row[ index ].value ] )
			)
		).toEqual( customer );
	} );
} );

describe( 'CustomersReportTable phone cells', () => {
	const BILLING_PHONE_COL = col( 'billing_phone' );
	const SHIPPING_PHONE_COL = col( 'shipping_phone' );

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'maps billing and shipping phone into their cells', () => {
		mockCountriesStore( [] );
		captured.getRowsContent = null;
		render( <CustomersReportTable query={ {} } /> );
		const rows = captured.getRowsContent( [
			{
				...baseCustomer,
				billing_phone: '555-32123',
				shipping_phone: '555-99887',
			},
		] );

		expect( rows[ 0 ][ BILLING_PHONE_COL ].value ).toBe( '555-32123' );
		expect( rows[ 0 ][ SHIPPING_PHONE_COL ].value ).toBe( '555-99887' );
	} );

	it( 'keeps the phone headers aligned with the phone cells', () => {
		mockCountriesStore( [] );
		captured.getHeadersContent = null;
		render( <CustomersReportTable query={ {} } /> );
		const headers = captured.getHeadersContent();

		expect( headers[ BILLING_PHONE_COL ].key ).toBe( 'billing_phone' );
		expect( headers[ SHIPPING_PHONE_COL ].key ).toBe( 'shipping_phone' );
	} );
} );
