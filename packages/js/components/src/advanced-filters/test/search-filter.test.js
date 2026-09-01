/**
 * @jest-environment jsdom
 */
/**
 * External dependencies
 */
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement, createRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SearchFilter from '../search-filter';

const getConfig = ( getLabels ) => ( {
	labels: {
		add: 'IP Address',
		filter: 'Select IP addresses',
		placeholder: 'Search IP address',
		rule: 'Select an IP address filter match',
		title: '<title>IP Address</title> <rule/> <filter/>',
	},
	rules: [ { value: 'includes', label: 'Includes' } ],
	input: {
		type: 'downloadIps',
		getLabels,
	},
} );

describe( 'SearchFilter', () => {
	test( 'normalizes string identifiers when an external change reloads labels', async () => {
		const getLabels = jest
			.fn()
			.mockResolvedValue( [ { id: '::1', label: '::1' } ] );
		const onFilterChange = jest.fn();
		const ref = createRef();
		const props = {
			config: getConfig( getLabels ),
			filter: { key: 'ip_address', rule: 'includes', value: '' },
			onFilterChange,
			query: {},
		};
		const { rerender } = render(
			<SearchFilter ref={ ref } { ...props } />
		);

		act( () => {
			ref.current.onSearchChange( [
				{ key: '127.0.0.1', label: '127.0.0.1' },
			] );
		} );
		onFilterChange.mockClear();

		rerender(
			<SearchFilter
				ref={ ref }
				{ ...props }
				filter={ { ...props.filter, value: '::1' } }
			/>
		);

		await waitFor( () =>
			expect( getLabels ).toHaveBeenCalledWith( '::1', {} )
		);
		expect( ref.current.state.selected ).toEqual( [
			{ id: '::1', key: '::1', label: '::1' },
		] );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Remove ::1' } )
		);

		expect(
			screen.queryByRole( 'button', { name: 'Remove ::1' } )
		).not.toBeInTheDocument();
		expect( onFilterChange ).toHaveBeenCalledWith( {
			property: 'value',
			value: '',
		} );
	} );

	test( 'does not reload a current string identifier with stale label data', async () => {
		const getLabels = jest
			.fn()
			.mockResolvedValue( [ { id: '::1', label: '::1' } ] );
		const onFilterChange = jest.fn();
		const ref = createRef();
		const props = {
			config: getConfig( getLabels ),
			filter: { key: 'ip_address', rule: 'includes', value: '' },
			onFilterChange,
			query: {},
		};
		const { rerender } = render(
			<SearchFilter ref={ ref } { ...props } />
		);

		act( () => {
			ref.current.onSearchChange( [ { key: '::1', label: '::1' } ] );
		} );
		onFilterChange.mockClear();
		rerender(
			<SearchFilter
				ref={ ref }
				{ ...props }
				filter={ { ...props.filter, value: '::1' } }
			/>
		);

		expect( getLabels ).not.toHaveBeenCalled();
		expect( ref.current.state.selected ).toEqual( [
			{ key: '::1', label: '::1' },
		] );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Remove ::1' } )
		);
		expect( onFilterChange ).toHaveBeenCalledWith( {
			property: 'value',
			value: '',
		} );
	} );

	test( 'keeps numeric identifiers without reloading their labels', () => {
		const getLabels = jest
			.fn()
			.mockResolvedValue( [ { id: 60, label: '#60' } ] );
		const ref = createRef();
		const props = {
			config: getConfig( getLabels ),
			filter: { key: 'order', rule: 'includes', value: '' },
			onFilterChange: jest.fn(),
			query: {},
		};
		const { rerender } = render(
			<SearchFilter ref={ ref } { ...props } />
		);

		act( () => {
			ref.current.onSearchChange( [ { key: 60, label: '#60' } ] );
		} );
		rerender(
			<SearchFilter
				ref={ ref }
				{ ...props }
				filter={ { ...props.filter, value: '60' } }
			/>
		);

		expect( getLabels ).not.toHaveBeenCalled();
		expect( ref.current.state.selected ).toEqual( [
			{ key: 60, label: '#60' },
		] );
	} );

	test( 'does not reload labels when an array filter value matches the selection', () => {
		const getLabels = jest.fn();
		const ref = createRef();
		const props = {
			config: getConfig( getLabels ),
			filter: { key: 'order', rule: 'includes', value: '' },
			onFilterChange: jest.fn(),
			query: {},
		};
		const { rerender } = render(
			<SearchFilter ref={ ref } { ...props } />
		);

		act( () => {
			ref.current.onSearchChange( [ { key: 60, label: '#60' } ] );
		} );
		rerender(
			<SearchFilter
				ref={ ref }
				{ ...props }
				filter={ { ...props.filter, value: [ 60 ] } }
			/>
		);

		expect( getLabels ).not.toHaveBeenCalled();
	} );

	test( 'handles null filter values', () => {
		const getLabels = jest.fn();
		const props = {
			config: getConfig( getLabels ),
			filter: { key: 'order', rule: 'includes', value: null },
			onFilterChange: jest.fn(),
			query: {},
		};
		const { rerender } = render( <SearchFilter { ...props } /> );

		rerender(
			<SearchFilter
				{ ...props }
				filter={ { ...props.filter, value: '' } }
			/>
		);
		rerender( <SearchFilter { ...props } /> );

		expect( getLabels ).not.toHaveBeenCalled();
	} );

	test( 'passes changed array filter values to getLabels as strings', async () => {
		const getLabels = jest
			.fn()
			.mockResolvedValue( [ { id: 60, label: '#60' } ] );
		const ref = createRef();
		const props = {
			config: getConfig( getLabels ),
			filter: { key: 'order', rule: 'includes', value: '' },
			onFilterChange: jest.fn(),
			query: {},
		};
		const { rerender } = render(
			<SearchFilter ref={ ref } { ...props } />
		);

		rerender(
			<SearchFilter
				ref={ ref }
				{ ...props }
				filter={ { ...props.filter, value: [ 60 ] } }
			/>
		);

		await waitFor( () =>
			expect( getLabels ).toHaveBeenCalledWith( '60', {} )
		);
	} );

	test( 'ignores stale label responses', async () => {
		let resolveFirstRequest;
		let resolveSecondRequest;
		const firstRequest = new Promise( ( resolve ) => {
			resolveFirstRequest = resolve;
		} );
		const secondRequest = new Promise( ( resolve ) => {
			resolveSecondRequest = resolve;
		} );
		const getLabels = jest
			.fn()
			.mockReturnValueOnce( firstRequest )
			.mockReturnValueOnce( secondRequest );
		const ref = createRef();
		const props = {
			config: getConfig( getLabels ),
			filter: { key: 'ip_address', rule: 'includes', value: '' },
			onFilterChange: jest.fn(),
			query: {},
		};
		const { rerender } = render(
			<SearchFilter ref={ ref } { ...props } />
		);

		rerender(
			<SearchFilter
				ref={ ref }
				{ ...props }
				filter={ { ...props.filter, value: '::1' } }
			/>
		);
		rerender(
			<SearchFilter
				ref={ ref }
				{ ...props }
				filter={ { ...props.filter, value: '127.0.0.1' } }
			/>
		);

		await act( async () => {
			resolveSecondRequest( [ { id: '127.0.0.1', label: '127.0.0.1' } ] );
			await secondRequest;
		} );
		await act( async () => {
			resolveFirstRequest( [ { id: '::1', label: '::1' } ] );
			await firstRequest;
		} );

		expect( ref.current.state.selected ).toEqual( [
			{ id: '127.0.0.1', key: '127.0.0.1', label: '127.0.0.1' },
		] );
	} );
} );
