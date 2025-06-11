/**
 * Internal dependencies
 */
import { Analytics } from './Analytics';

export class Customers extends Analytics {
	// The analytics pages are `analytics-{slug}`.
	url = 'wp-admin/admin.php?page=wc-admin&path=%2Fcustomers';
}
