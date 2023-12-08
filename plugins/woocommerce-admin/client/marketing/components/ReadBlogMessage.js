/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Link } from '@woocommerce/components';
import interpolateComponents from '@automattic/interpolate-components';

const ReadBlogMessage = () => {
	return interpolateComponents( {
		mixedString: __(
			'Read {{link}}the WooCommerce blog{{/link}} for more tips on marketing your store',
			'woocommerce'
		),
		components: {
			link: (
				<Link
					type="external"
					href="https://woo.com/blog/marketing/coupons/?utm_medium=product"
					target="_blank"
				/>
			),
		},
	} );
};

export default ReadBlogMessage;
