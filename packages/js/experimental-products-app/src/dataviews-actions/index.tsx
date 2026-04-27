/**
 * External dependencies
 */
import { edit, external } from '@wordpress/icons';
import { __, _x } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { getAdminLink } from '@woocommerce/settings';
import type { Action } from '@wordpress/dataviews';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../fields/types';

export const editAction = (): Action< ProductEntityRecord > => ( {
	id: 'edit-product',
	label: __( 'Edit', 'woocommerce' ),
	isPrimary: true,
	icon: edit,
	isEligible( product ) {
		return product.status !== 'trash';
	},
	callback( items, { onActionPerformed } ) {
		const product = items[ 0 ];

		if ( product ) {
			window.location.href = getAdminLink(
				addQueryArgs( 'post.php', {
					post: product.id,
					action: 'edit',
				} )
			);
		}

		if ( onActionPerformed ) {
			onActionPerformed( items );
		}
	},
} );

export const viewAction = (): Action< ProductEntityRecord > => ( {
	id: 'view-product',
	label: _x( 'View', 'verb', 'woocommerce' ),
	isPrimary: true,
	icon: external,
	isEligible( product ) {
		return product.status !== 'trash' && !! product.permalink;
	},
	callback( items, { onActionPerformed } ) {
		const product = items[ 0 ];

		if ( product?.permalink ) {
			window.open( product.permalink, '_blank' );
		}

		if ( onActionPerformed ) {
			onActionPerformed( items );
		}
	},
} );

export const useProductActions = () =>
	useMemo( () => [ editAction(), viewAction() ], [] );
