/**
 * External dependencies
 */
import { ScrollTo } from '@woocommerce/components';

const MyScrollTo = () => (
	<ScrollTo>
		<div>
			Have the web browser automatically scroll to this component on page
			render.
		</div>
	</ScrollTo>
);

// The ScrollTo Component will trigger scrolling if rendered on the main docs page.
export default MyScrollTo;
