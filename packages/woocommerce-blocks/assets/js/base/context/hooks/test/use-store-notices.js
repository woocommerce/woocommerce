/**
 * External dependencies
 */
import { render, act } from '@testing-library/react';
import { StoreNoticesProvider } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import { useStoreNotices } from '../use-store-notices';

describe( 'useStoreNotices', () => {
	function setup() {
		const returnVal = {};

		function TestComponent() {
			Object.assign( returnVal, useStoreNotices() );

			return null;
		}

		render(
			<StoreNoticesProvider>
				<TestComponent />
			</StoreNoticesProvider>
		);

		return returnVal;
	}

	test( 'allows adding and removing notices and checking if there are notices of a specific type', () => {
		const storeNoticesData = setup();

		// Assert initial state.
		expect( storeNoticesData.notices ).toEqual( [] );
		expect( storeNoticesData.hasNoticesOfType( 'default' ) ).toBe( false );

		// Add error notice.
		act( () => {
			storeNoticesData.addErrorNotice( 'Error notice' );
		} );

		expect( storeNoticesData.notices.length ).toBe( 1 );
		expect( storeNoticesData.hasNoticesOfType( 'default' ) ).toBe( true );

		expect( storeNoticesData.notices.length ).toBe( 1 );
		expect( storeNoticesData.hasNoticesOfType( 'default' ) ).toBe( true );

		// Remove error notice.
		act( () => {
			storeNoticesData.removeNotices( 'error' );
		} );

		expect( storeNoticesData.notices.length ).toBe( 0 );
		expect( storeNoticesData.hasNoticesOfType( 'default' ) ).toBe( false );

		// Remove all remaining notices.
		act( () => {
			storeNoticesData.removeNotices();
		} );

		expect( storeNoticesData.notices.length ).toBe( 0 );
		expect( storeNoticesData.hasNoticesOfType( 'default' ) ).toBe( false );
	} );
} );
