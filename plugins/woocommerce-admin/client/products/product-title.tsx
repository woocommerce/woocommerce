/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { useFormContext } from '@woocommerce/components';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { WooHeaderPageTitle } from '~/header/utils';

export const ProductTitle: React.FC = () => {
	const { values } = useFormContext< Product >();

	return <WooHeaderPageTitle>{ values.name }</WooHeaderPageTitle>;
};

registerPlugin( 'woocommerce-product-title', {
	render: ProductTitle,
	icon: 'admin-generic',
} );
