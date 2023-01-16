/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import {
	addExitPage,
	removeExitPage,
} from '~/customer-effort-score-tracks/customer-effort-score-exit-page';

const ACTION_NAME = 'import_products';

( () => {
	const query: { step?: string; page?: string } = getQuery();

	if ( query.page !== 'product_importer' ) {
		return;
	}

	if ( query.step === 'done' ) {
		removeExitPage( ACTION_NAME );
		return;
	}

	addExitPage( ACTION_NAME );
} )();
