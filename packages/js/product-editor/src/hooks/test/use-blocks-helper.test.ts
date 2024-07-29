/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import { useBlocksHelper } from '../use-blocks-helper';

const mockGetBlockParentsByBlockName = jest.fn();
const mockGetBlock = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	select: jest.fn( () => ( {
		getBlockParentsByBlockName: mockGetBlockParentsByBlockName,
		getBlock: mockGetBlock,
	} ) ),
} ) );

describe( 'useBlocksHelper', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should return the closest parent tab id', () => {
		const clientId = 'test-client-id';
		const parentClientId = 'parent-client-id';
		const attributes = { id: 'parent-tab-id' };

		mockGetBlockParentsByBlockName.mockReturnValue( [ parentClientId ] );
		mockGetBlock.mockReturnValue( { attributes } );

		const { result } = renderHook( () => useBlocksHelper() );
		const { getParentTabId } = result.current;

		const tabId = getParentTabId( clientId );

		expect( tabId ).toBe( 'parent-tab-id' );
		expect( mockGetBlockParentsByBlockName ).toHaveBeenCalledWith(
			clientId,
			'woocommerce/product-tab',
			true
		);
		expect( mockGetBlock ).toHaveBeenCalledWith( parentClientId );
	} );

	it( 'should return null if no parent tab id is found', () => {
		const clientId = 'test-client-id';

		mockGetBlockParentsByBlockName.mockReturnValue( [] );

		const { result } = renderHook( () => useBlocksHelper() );
		const { getParentTabId } = result.current;

		const tabId = getParentTabId( clientId );

		expect( tabId ).toBe( null );
		expect( mockGetBlockParentsByBlockName ).toHaveBeenCalledWith(
			clientId,
			'woocommerce/product-tab',
			true
		);
		expect( mockGetBlock ).not.toHaveBeenCalled();
	} );

	it( 'should return `undefined` if parent block has no attributes', () => {
		const clientId = 'test-client-id';
		const parentClientId = 'parent-client-id';

		mockGetBlockParentsByBlockName.mockReturnValue( [ parentClientId ] );
		mockGetBlock.mockReturnValue( {} );

		const { result } = renderHook( () => useBlocksHelper() );
		const { getParentTabId } = result.current;

		const tabId = getParentTabId( clientId );

		expect( tabId ).toBeUndefined();
		expect( mockGetBlockParentsByBlockName ).toHaveBeenCalledWith(
			clientId,
			'woocommerce/product-tab',
			true
		);
		expect( mockGetBlock ).toHaveBeenCalledWith( parentClientId );
	} );
} );
