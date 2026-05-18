/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { EmptyState } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import type { StatusTab } from '../constants';
import { ProductListEmptyStateIcon } from './icon';

type EmptyStateCopy = {
	title: string;
	description: string;
};

function getEmptyStateCopy( tab: StatusTab ): EmptyStateCopy {
	switch ( tab ) {
		case 'publish':
			return {
				title: __( 'No products published yet', 'woocommerce' ),
				description: __(
					'Check your drafts or add a new product to start selling.',
					'woocommerce'
				),
			};
		case 'draft':
			return {
				title: __( 'No draft products yet', 'woocommerce' ),
				description: __(
					'Any products you save as drafts will be listed here.',
					'woocommerce'
				),
			};
		case 'pending':
			return {
				title: __( 'No products awaiting review', 'woocommerce' ),
				description: __(
					'Products submitted for review will appear here.',
					'woocommerce'
				),
			};
		case 'trash':
			return {
				title: __( 'No products in trash', 'woocommerce' ),
				description: __(
					'Deleted products will move here, where you can restore or remove them permanently.',
					'woocommerce'
				),
			};
		case 'all':
		default:
			return {
				title: __( 'No products yet', 'woocommerce' ),
				description: __(
					'All your products will appear here.',
					'woocommerce'
				),
			};
	}
}

type ProductListEmptyStateProps = {
	tab: StatusTab;
};

export function ProductListEmptyState( { tab }: ProductListEmptyStateProps ) {
	const { title, description } = getEmptyStateCopy( tab );

	return (
		<EmptyState.Root className="woocommerce-product-list__empty-state">
			<EmptyState.Visual>
				<ProductListEmptyStateIcon />
			</EmptyState.Visual>
			<EmptyState.Title>{ title }</EmptyState.Title>
			<EmptyState.Description>{ description }</EmptyState.Description>
		</EmptyState.Root>
	);
}
