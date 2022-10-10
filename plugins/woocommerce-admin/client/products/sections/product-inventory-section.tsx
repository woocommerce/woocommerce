/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardBody, TextControl } from '@wordpress/components';
import { cleanForSlug } from '@wordpress/url';
import { getAdminLink } from '@woocommerce/settings';
import { Link, useFormContext } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getTextControlProps } from './utils';
import { ProductSectionLayout } from '../layout/product-section-layout';
import { usePrevious } from '~/hooks/usePrevious';

export const ProductInventorySection: React.FC = () => {
	const { getInputProps, setValue, touched, values } = useFormContext< Product >();
	const prevTouchedName = usePrevious( touched.name );

	const setSkuIfEmpty = () => {
		if ( values.sku || ! values.name?.length ) {
			return;
		}
		setValue( 'sku', cleanForSlug( values.name ) );
	};

	const isNameBlurred = ! prevTouchedName && touched.name;
	useEffect( setSkuIfEmpty, [ isNameBlurred ] );

	return (
		<ProductSectionLayout
			title={ __( 'Inventory', 'woocommerce' ) }
			description={
				<>
					<span>
						{ __(
							'Set up and manage inventory for this product, including status and available quantity.',
							'woocommerce'
						) }
					</span>
					<Link
						href={ getAdminLink(
							'admin.php?page=wc-settings&tab=products&section=inventory'
						) }
						target="_blank"
						type="wp-admin"
						onClick={ () => {
							recordEvent( 'add_product_inventory_help' );
						} }
						className="woocommerce-form-section__header-link"
					>
						{ __(
							'Manage global inventory settings',
							'woocommerce'
						) }
					</Link>
				</>
			}
		>
			<Card>
				<CardBody>
					<TextControl
						label={ __(
							'SKU (Stock Keeping Unit)',
							'woocommerce'
						) }
						placeholder={ __(
							'washed-oxford-button-down-shirt',
							'woocommerce'
						) }
						{ ...getTextControlProps( getInputProps( 'sku' ) ) }
					/>
				</CardBody>
			</Card>
		</ProductSectionLayout>
	);
};
