/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import CustomersReportTable from '../table';

const captured = { getRowsContent: null };

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

// Country cell is the 9th column (0-indexed: 8) per getHeadersContent in table.js.
const COUNTRY_COL = 8;

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
