/*
 * Internal dependencies
 */
import Link from '../';

export default {
	title: 'WooCommerce Admin/components/Link',
	component: Link,
};

export const External = () => {
	return (
		<Link href="https://woocommerce.com" type="external">
			WooCommerce.com
		</Link>
	);
};
