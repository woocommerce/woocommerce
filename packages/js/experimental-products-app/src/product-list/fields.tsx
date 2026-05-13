/**
 * Internal dependencies
 */
import {
	PRODUCT_FIELD_IDS as PRODUCT_LIST_FIELD_IDS,
	createProductFields,
} from '../fields/registry';

export const productFields = createProductFields( PRODUCT_LIST_FIELD_IDS );
