/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { edit } from '@wordpress/icons';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { __ } from '@wordpress/i18n';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { unlock } from '../../lock-unlock';

const { useHistory } = unlock( routerPrivateApis );

export const useEditProductAction = () => {
	const history = useHistory();
	return useMemo(
		() => ( {
			id: 'edit-product',
			label: __( 'Edit', 'woocommerce' ),
			isPrimary: true,
			icon: edit,
			supportsBulk: true,
			isEligible( product: Product ) {
				if ( product.status === 'trash' ) {
					return false;
				}
				return true;
			},
			callback( items: Product[] ) {
				const product = items[ 0 ];
				history.push( {
					page: 'woocommerce-products-dashboard',
					postId: product.id,
					postType: product.type,
					quickEdit: true,
				} );
			},
		} ),
		[ history ]
	);
};
