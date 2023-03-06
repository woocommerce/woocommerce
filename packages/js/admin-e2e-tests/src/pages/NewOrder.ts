/**
 * Internal dependencies
 */
import { BasePage } from './BasePage';

export class NewOrder extends BasePage {
	url = 'wp-admin/post-new.php?post_type=shop_order';
}
