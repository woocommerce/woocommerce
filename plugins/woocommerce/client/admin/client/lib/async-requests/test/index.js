import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { getCustomerLabels } from '../index';
vi.mock( '@wordpress/api-fetch', () => {
	const mock = vi.fn();
	return {
		default: mock,
		...mock,
	};
} );
describe( 'getCustomerLabels', async () => {
	beforeEach( () => {
		apiFetch.mockReset();
	} );
	it( 'labels a customer with their name', async () => {
		apiFetch.mockResolvedValue( [
			{
				id: 1,
				name: 'Zoe Bloggs',
				username: 'bloggs',
				email: 'zoe@example.test',
			},
		] );
		await expect( getCustomerLabels( '1' ) ).resolves.toEqual( [
			{
				key: 1,
				label: 'Zoe Bloggs',
			},
		] );
	} );
	it( 'falls back to the username, then the email, for customers with no name', async () => {
		apiFetch.mockResolvedValue( [
			{
				id: 2,
				name: '',
				username: 'zoemarketing',
				email: 'hello@zoeshop.test',
			},
			{
				id: 3,
				name: '',
				username: '',
				email: 'nobody@zoeshop.test',
			},
		] );
		await expect( getCustomerLabels( '2,3' ) ).resolves.toEqual( [
			{
				key: 2,
				label: 'zoemarketing',
			},
			{
				key: 3,
				label: 'nobody@zoeshop.test',
			},
		] );
	} );
	it( 'does not call the API when there are no ids', async () => {
		await expect( getCustomerLabels( '' ) ).resolves.toEqual( [] );
		expect( apiFetch ).not.toHaveBeenCalled();
	} );
} );
