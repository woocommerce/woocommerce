/**
 * External dependencies
 */
import { Gravatar } from '@woocommerce/components';

export const Basic = () => <Gravatar user="email@example.org" size={ 48 } />;

export default {
	title: 'WooCommerce Admin/components/Gravatar',
	component: Gravatar,
};
