/**
 * External dependencies
 */
import { ButtonGroup, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Product } from '~/marketplace/components/product-list/types';
import ProductCard from '~/marketplace/components/product-card/product-card';
import { WP_ADMIN_PLUGIN_LIST_URL } from '../constants';

export default function ProductInstalledModal( props: {
	product?: Product;
	documentationUrl?: string;
} ) {
	return (
		<>
			<p className="woocommerce-marketplace__header-account-modal-text">
				{ __(
					'Keep the momentum going and start setting up your extension.',
					'woocommerce'
				) }
			</p>
			{ props.product && (
				<ProductCard
					product={ props.product }
					small={ true }
					tracksData={ {
						position: 1,
						group: 'subscriptions',
						label: 'install',
					} }
				/>
			) }

			<ButtonGroup className="woocommerce-marketplace__header-account-modal-button-group">
				{ props.documentationUrl && (
					<Button
						variant="tertiary"
						href={ props.documentationUrl }
						className="woocommerce-marketplace__header-account-modal-button"
						key={ 'docs' }
					>
						{ __( 'View Docs', 'woocommerce' ) }
					</Button>
				) }
				<Button
					variant="primary"
					href={ WP_ADMIN_PLUGIN_LIST_URL }
					className="woocommerce-marketplace__header-account-modal-button"
					key={ 'plugin-list' }
				>
					{ __( 'View in Plugins', 'woocommerce' ) }
				</Button>
			</ButtonGroup>
		</>
	);
}
