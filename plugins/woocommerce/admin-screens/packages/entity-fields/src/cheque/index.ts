/**
 * Script module of the `cheque` collection: `@woocommerce/entity-fields/cheque`.
 *
 * The fields' data is in their `field.php` files. This module adds the parts PHP can't describe.
 */

/**
 * Internal dependencies
 */
import type { FieldsScriptParts } from '../types';
import type { ChequeSettings } from './types';
import { fieldExtensions as instructions } from './instructions/field';

const fields: FieldsScriptParts< ChequeSettings > = {
	instructions,
};

export default fields;
