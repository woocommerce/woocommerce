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

const { useHistory, useLocation } = unlock( routerPrivateApis );

export const useEditProductAction = ( { postType }: { postType: string } ) => {
	const history = useHistory();
	const location = useLocation();
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
					...location.params,
					postId: product.id,
					postType,
					quickEdit: true,
				} );
			},
		} ),
		[ history, location.params ]
	);
};
