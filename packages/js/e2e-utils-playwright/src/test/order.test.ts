/**
 * External dependencies
 */
import type { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { getOrderIdFromUrl } from '../order';

describe( 'getOrderIdFromUrl', () => {
	it( 'should extract the order ID from a valid URL', () => {
		const page = {
			url: () => 'https://example.com/order-received/12345/',
		} as unknown as Page;
		expect( getOrderIdFromUrl( page ) ).toBe( '12345' );
	} );

	it( 'should return undefined if the URL does not contain an order ID', () => {
		const page = {
			url: () => 'https://example.com/order-received/',
		} as unknown as Page;
		expect( getOrderIdFromUrl( page ) ).toBeUndefined();
	} );

	it( 'should return undefined if the URL is not in the expected format', () => {
		const page = {
			url: () => 'https://example.com/other-page/12345/',
		} as unknown as Page;
		expect( getOrderIdFromUrl( page ) ).toBeUndefined();
	} );
} );
