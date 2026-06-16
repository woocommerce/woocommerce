/**
 * Internal dependencies
 */
import { taskIcons } from './icons';

describe( 'taskIcons', () => {
	it( 'does not keep the deprecated WooPayments task alias', () => {
		expect( taskIcons ).toHaveProperty( 'payments' );
		expect( taskIcons ).not.toHaveProperty( 'woocommerce-payments' );
	} );
} );
