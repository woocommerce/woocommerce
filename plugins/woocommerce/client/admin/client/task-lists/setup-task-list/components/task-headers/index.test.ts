/**
 * Internal dependencies
 */
import { taskHeaders } from './index';

describe( 'taskHeaders', () => {
	it( 'should not register the deprecated WooPayments onboarding task header', () => {
		expect( taskHeaders ).not.toHaveProperty( 'woocommerce-payments' );
		expect( taskHeaders ).toHaveProperty( 'payments' );
	} );
} );
