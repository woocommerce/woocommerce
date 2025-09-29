/**
 * External dependencies
 */
import { sanitizeHTML } from '@woocommerce/sanitize';

export default ( html ) => {
	return {
		__html: sanitizeHTML( html ),
	};
};
