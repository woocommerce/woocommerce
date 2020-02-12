/**
 * @format
 */

/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from '../../utils/flows';
import { deleteProduct } from '../../utils';
import { createSimpleProduct } from '../../utils/components';

describe( 'Add New Simple Product Page', () => {
	it( 'can create simple product and delete it after', async () => {
		await StoreOwnerFlow.login();
		await createSimpleProduct();
		await deleteProduct();
	} );
} );

