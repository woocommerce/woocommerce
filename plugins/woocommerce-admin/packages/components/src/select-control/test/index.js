/**
 * External dependencies
 */
import { mount } from 'enzyme';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SelectControl } from '../index';

describe( 'SelectControl', () => {
	const optionClassname = 'woocommerce-select-control__option';
	const query = 'lorem';
	const options = [
		{ key: '1', label: 'lorem 1', value: { id: '1' } },
		{ key: '2', label: 'lorem 2', value: { id: '2' } },
		{ key: '3', label: 'bar', value: { id: '3' } },
	];

	it( 'returns all elements', () => {
		const selectControl = mount( <SelectControl options={ options } /> );
		selectControl.setState( {
			query,
		} );

		selectControl.instance().search( query );
		selectControl.update();

		setTimeout( function () {
			expect(
				selectControl.find( Button ).filter( '.' + optionClassname )
					.length
			).toBe( 3 );
		}, 0 );
	} );

	it( 'returns matching elements', () => {
		const selectControl = mount(
			<SelectControl isSearchable options={ options } />
		);
		selectControl.setState( {
			query,
		} );

		selectControl.instance().search( query );
		selectControl.update();

		setTimeout( function () {
			expect(
				selectControl.find( Button ).filter( '.' + optionClassname )
					.length
			).toBe( 2 );
		}, 0 );
	} );

	it( "doesn't return matching excluded elements", () => {
		const selectControl = mount(
			<SelectControl
				isSearchable
				options={ options }
				selected={ [ options[ 1 ] ] }
				excludeSelectedOptions
				multiple
			/>
		);
		selectControl.setState( {
			query,
		} );

		selectControl.instance().search( query );
		selectControl.update();

		setTimeout( function () {
			expect(
				selectControl.find( Button ).filter( '.' + optionClassname )
					.length
			).toBe( 1 );
		}, 0 );
	} );

	it( 'trims spaces from input', () => {
		const selectControl = mount(
			<SelectControl isSearchable options={ options } />
		);
		selectControl.setState( {
			query,
		} );

		selectControl.instance().search( '    ' + query + ' ' );
		selectControl.update();

		setTimeout( function () {
			expect(
				selectControl.find( Button ).filter( '.' + optionClassname )
					.length
			).toBe( 2 );
		}, 0 );
	} );

	it( 'limits results', () => {
		const selectControl = mount(
			<SelectControl isSearchable options={ options } maxResults={ 1 } />
		);
		selectControl.setState( {
			query,
		} );

		selectControl.instance().search( query );
		selectControl.update();

		setTimeout( function () {
			expect(
				selectControl.find( Button ).filter( '.' + optionClassname )
					.length
			).toBe( 1 );
		}, 0 );
	} );

	it( 'shows options initially', () => {
		const selectControl = mount(
			<SelectControl isSearchable options={ options } />
		);

		selectControl.instance().search( '' );
		selectControl.update();

		setTimeout( function () {
			expect(
				selectControl.find( Button ).filter( '.' + optionClassname )
					.length
			).toBe( 3 );
		}, 0 );
	} );

	it( 'shows options after query', () => {
		const selectControl = mount(
			<SelectControl isSearchable options={ options } hideBeforeSearch />
		);

		selectControl.instance().search( '' );
		selectControl.update();

		expect(
			selectControl.find( Button ).filter( '.' + optionClassname ).length
		).toBe( 0 );

		selectControl.instance().search( query );
		selectControl.update();

		setTimeout( function () {
			expect(
				selectControl.find( Button ).filter( '.' + optionClassname )
					.length
			).toBe( 2 );
		}, 0 );
	} );

	it( 'appends an option after filtering', () => {
		const selectControl = mount(
			<SelectControl
				options={ options }
				onFilter={ ( filteredOptions ) =>
					filteredOptions.concat( [
						{ key: 'new-option', label: 'New options' },
					] )
				}
			/>
		);

		selectControl.instance().search( query );
		selectControl.update();

		setTimeout( function () {
			expect(
				selectControl.find( Button ).filter( '.' + optionClassname )
					.length
			).toBe( 3 );
		}, 0 );
	} );

	it( 'changes the options on search', () => {
		const queriedOptions = [];
		const queryOptions = ( searchedQuery ) => {
			if ( searchedQuery === 'test' ) {
				queriedOptions.push( {
					key: 'test-option',
					label: 'Test option',
				} );
			}
		};
		const selectControl = mount(
			<SelectControl
				isSearchable
				options={ queriedOptions }
				onSearch={ queryOptions }
				onFilter={ () => queriedOptions }
			/>
		);

		selectControl.instance().search( '' );
		selectControl.update();

		setTimeout( function () {
			expect(
				selectControl.find( Button ).filter( '.' + optionClassname )
					.length
			).toBe( 0 );
		}, 0 );

		selectControl.instance().search( 'test' );
		selectControl.update();

		setTimeout( function () {
			expect(
				selectControl.find( Button ).filter( '.' + optionClassname )
					.length
			).toBe( 1 );
		}, 0 );
	} );

	it( "doesn't show options with an empty search", () => {
		const optionsEmptyValue = [
			{ key: '1', label: 'lorem 1', value: { id: '' } },
		];
		const optionControlClassname = 'woocommerce-select-control__listbox';
		const selectControl = mount(
			<SelectControl options={ optionsEmptyValue } />
		);

		selectControl.instance().search( '' );
		selectControl.update();

		setTimeout( function () {
			expect(
				selectControl.find( '.' + optionControlClassname ).length
			).toBe( 0 );
		}, 0 );
	} );
} );
