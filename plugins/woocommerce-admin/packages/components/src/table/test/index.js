/** @format */
/**
 * External dependencies
 */
import fetch from 'node-fetch';
import { mount, shallow } from 'enzyme';

/**
 * Internal dependencies
 */
import TableCard from '../index';
import mockHeaders from '../__mocks__/table-mock-headers';
import mockData from '../__mocks__/table-mock-data';

window.fetch = fetch;

describe( 'TableCard', () => {
	test( 'should render placeholder table while loading', () => {
		const tableCard = shallow(
			<TableCard
				title="Revenue"
				headers={ mockHeaders }
				isLoading={ true }
				rows={ [] }
				rowsPerPage={ 5 }
				totalRows={ 5 }
			/>
		);

		expect( tableCard.find( 'TablePlaceholder' ).length ).toBe( 1 );
	} );

	test( 'should not render placeholder table when not loading', () => {
		const tableCard = mount(
			<TableCard
				title="Revenue"
				headers={ mockHeaders }
				isLoading={ false }
				rows={ mockData }
				rowsPerPage={ 5 }
				totalRows={ 5 }
			/>
		);

		expect( tableCard.find( 'Table' ).length ).toBe( 1 );
		expect( tableCard.find( 'TablePlaceholder' ).length ).toBe( 0 );
	} );
} );
