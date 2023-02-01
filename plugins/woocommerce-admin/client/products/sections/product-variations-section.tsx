/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { Link, useFormContext2 } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { useController } from 'react-hook-form';

/**
 * Internal dependencies
 */
import { ProductSectionLayout } from '../layout/product-section-layout';
import { Variations } from '../fields/variations';

export const ProductVariationsSection: React.FC = () => {
	const { control } = useFormContext2< Product >();

	const { field } = useController( { control, name: 'attributes' } );

	const options = field.value
		? field.value.filter( ( attribute ) => attribute.variation )
		: [];

	if ( options.length === 0 ) {
		return null;
	}

	return (
		<ProductSectionLayout
			title={ __( 'Variations', 'woocommerce' ) }
			description={
				<>
					<span>
						{ __(
							'Manage individual product combinations created from options.',
							'woocommerce'
						) }
					</span>
					<Link
						className="woocommerce-form-section__header-link"
						href="https://woocommerce.com/posts/product-variations-display/"
						target="_blank"
						type="external"
						onClick={ () => {
							recordEvent( 'add_product_variation_help' );
						} }
					>
						{ __(
							'How to make variations work for you',
							'woocommerce'
						) }
					</Link>
				</>
			}
		>
			<Variations />
		</ProductSectionLayout>
	);
};
