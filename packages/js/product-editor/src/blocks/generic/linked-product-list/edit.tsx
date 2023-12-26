/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { PRODUCTS_STORE_NAME, Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';
import { LinkedProductListBlockAttributes } from './types';
import { FormattedPrice } from '../../../components/formatted-price';

export function Edit( {
	attributes,
	context: { postType },
}: ProductEditorBlockEditProps< LinkedProductListBlockAttributes > ) {
	const { property } = attributes;
	const blockProps = useWooBlockProps( attributes );
	const [ linkedProductIds ] = useEntityProp< number[] >(
		'postType',
		postType,
		property
	);

	const linkedProducts = useSelect(
		( select ) => {
			const { getProducts } = select( PRODUCTS_STORE_NAME );

			if ( linkedProductIds.length === 0 ) {
				return [];
			}

			return (
				getProducts< Product[] >( {
					include: linkedProductIds,
				} ) ?? []
			);
		},
		[ linkedProductIds ]
	);

	return (
		<div { ...blockProps }>
			<div role="table">
				<div role="rowgroup">
					<div role="rowheader">
						<div role="columnheader">
							{ __( 'Product', 'woocommerce' ) }
						</div>
						<div
							role="columnheader"
							aria-label={ __( 'Actions', 'woocommerce' ) }
						/>
					</div>
				</div>
				<div role="rowgroup">
					{ linkedProducts.map( ( product ) => (
						<div role="row" key={ product.id }>
							<div role="cell">
								<div
									className="wp-block-woocommerce-product-linked-list-field__product-image"
									style={ {
										backgroundImage: product.images[ 0 ]
											? `url(${ product.images[ 0 ].src })`
											: undefined,
									} }
								/>
								<div className="wp-block-woocommerce-product-linked-list-field__product-info">
									<span className="wp-block-woocommerce-product-linked-list-field__product-name">
										{ product.name }
									</span>
									<FormattedPrice
										product={ product }
										className="wp-block-woocommerce-product-linked-list-field__product-price"
									/>
								</div>
							</div>
							<div role="cell"></div>
						</div>
					) ) }
				</div>
			</div>
		</div>
	);
}
