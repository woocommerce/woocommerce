/**
 * @format
 */

/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from '../../utils/flows';
import { deleteProduct } from '../../utils';
import { createVariableProduct } from '../../utils/components';

describe( 'Add New Variable Product Page', () => {
	it( 'can create variable product and delete it after', async () => {
		await StoreOwnerFlow.login();
		await createVariableProduct();
		await deleteProduct();
	} );
} );

