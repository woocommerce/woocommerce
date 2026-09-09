/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react-hooks/dom';
import { useUserPreferences } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { useLastProvider } from '../use-last-provider';
import type { FinanceProvider } from '../../types';

jest.mock( '@woocommerce/data', () => ( {
	useUserPreferences: jest.fn(),
} ) );

const mockUseUserPreferences = useUserPreferences as unknown as jest.Mock;
const mockUpdateUserPreferences = jest.fn( () => Promise.resolve() );

const makeProvider = ( id: string ): FinanceProvider => ( {
	provider_id: id,
	title: id,
	icon_url: null,
	data_types: [ { type: 'payouts', schema_version: 1 } ],
} );

const providers = [ makeProvider( 'bacs' ), makeProvider( 'cheque' ) ];

describe( 'useLastProvider', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockUseUserPreferences.mockReturnValue( {
			updateUserPreferences: mockUpdateUserPreferences,
		} );
	} );

	it( 'falls back to the first provider without persisting it', () => {
		const { result } = renderHook( () => useLastProvider( providers ) );

		expect( result.current.selectedId ).toBe( 'bacs' );
		expect( mockUpdateUserPreferences ).not.toHaveBeenCalled();
	} );

	it( 'uses the stored provider when it is available', () => {
		mockUseUserPreferences.mockReturnValue( {
			payments_finance_last_provider: 'cheque',
			updateUserPreferences: mockUpdateUserPreferences,
		} );

		const { result } = renderHook( () => useLastProvider( providers ) );

		expect( result.current.selectedId ).toBe( 'cheque' );
	} );

	it( 'ignores a stored provider that is no longer available', () => {
		mockUseUserPreferences.mockReturnValue( {
			payments_finance_last_provider: 'stripe',
			updateUserPreferences: mockUpdateUserPreferences,
		} );

		const { result } = renderHook( () => useLastProvider( providers ) );

		expect( result.current.selectedId ).toBe( 'bacs' );
	} );

	it( 'returns null without providers', () => {
		const { result } = renderHook( () => useLastProvider( [] ) );

		expect( result.current.selectedId ).toBeNull();
	} );

	it( 'persists an explicit selection', () => {
		const { result } = renderHook( () => useLastProvider( providers ) );

		act( () => result.current.selectProvider( 'cheque' ) );

		expect( result.current.selectedId ).toBe( 'cheque' );
		expect( mockUpdateUserPreferences ).toHaveBeenCalledWith( {
			payments_finance_last_provider: 'cheque',
		} );
	} );
} );
