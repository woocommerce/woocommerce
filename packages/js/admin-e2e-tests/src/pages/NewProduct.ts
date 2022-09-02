/**
 * Internal dependencies
 */
import { BasePage } from './BasePage';

export class NewProduct extends BasePage {
	url = 'wp-admin/post-new.php?post_type=product';
}
