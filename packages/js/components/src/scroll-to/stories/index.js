/**
 * External dependencies
 */
import { ScrollTo } from '@woocommerce/components';

export const Basic = () => (
	<ScrollTo>
		<div>
			Have the web browser automatically scroll to this component on page
			render.
		</div>
	</ScrollTo>
);

export default {
	title: 'WooCommerce Admin/components/ScrollTo',
	component: ScrollTo,
};
