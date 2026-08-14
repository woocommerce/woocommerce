/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { processErrorResponse } from '@woocommerce/block-data';
import { CartResponse, ExtensionCartUpdateArgs } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { extensionCartUpdate } from '../extension-cart-update';
import { STORE_KEY } from '../../../block-data/cart/constants';

jest.mock( '@wordpress/data', () => ( {
	dispatch: jest.fn(),
} ) );

jest.mock( '@woocommerce/block-data', () => ( {
	processErrorResponse: jest.fn(),
} ) );

const mockDispatch = dispatch as jest.MockedFunction< typeof dispatch >;
const mockProcessErrorResponse = processErrorResponse as jest.MockedFunction<
	typeof processErrorResponse
>;
const mockApplyExtensionCartUpdate = jest.fn();

describe( 'extensionCartUpdate', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockDispatch.mockReturnValue( {
			applyExtensionCartUpdate: mockApplyExtensionCartUpdate,
		} as never );
	} );

	it.each< [ string, ExtensionCartUpdateArgs ] >( [
		[
			'with overwrite omitted',
			{ namespace: 'test-extension', data: { value: 'omitted' } },
		],
		[
			'with overwrite disabled',
			{
				namespace: 'test-extension',
				data: { value: 'false' },
				overwriteDirtyCustomerData: false,
			},
		],
		[
			'with overwrite enabled',
			{
				namespace: 'test-extension',
				data: { value: 'true' },
				overwriteDirtyCustomerData: true,
			},
		],
		[
			'with shipping-address overwrite enabled',
			{
				namespace: 'test-extension',
				data: { value: 'shipping' },
				overwriteDirtyCustomerData: { shipping_address: true },
			},
		],
		[
			'with billing-address overwrite enabled',
			{
				namespace: 'test-extension',
				data: { value: 'billing' },
				overwriteDirtyCustomerData: { billing_address: true },
			},
		],
	] )(
		'forwards arguments and fulfills with the same cart response %s',
		async ( _, args ) => {
			const response = { items: [] } as CartResponse;
			mockApplyExtensionCartUpdate.mockResolvedValueOnce( response );

			const result = await extensionCartUpdate( args );

			expect( mockDispatch ).toHaveBeenCalledWith( STORE_KEY );
			expect( mockApplyExtensionCartUpdate ).toHaveBeenCalledWith( args );
			expect( result ).toBe( response );
		}
	);

	it( 'rejects the same non-special error without creating a notice', async () => {
		const error = {
			code: 'test_error',
			message: 'This is an extension error.',
		};
		mockApplyExtensionCartUpdate.mockRejectedValueOnce( error );

		await expect(
			extensionCartUpdate( {
				namespace: 'test-extension',
				data: {},
			} )
		).rejects.toBe( error );

		expect( mockProcessErrorResponse ).not.toHaveBeenCalled();
	} );

	it( 'processes a special cart-extension error once and rejects that same error', async () => {
		const error = {
			code: 'woocommerce_rest_cart_extensions_error',
			message: 'The cart extension could not be processed.',
		};
		mockApplyExtensionCartUpdate.mockRejectedValueOnce( error );

		await expect(
			extensionCartUpdate( {
				namespace: 'test-extension',
				data: {},
			} )
		).rejects.toBe( error );

		expect( mockProcessErrorResponse ).toHaveBeenCalledTimes( 1 );
		expect( mockProcessErrorResponse ).toHaveBeenCalledWith( error );
	} );
} );
