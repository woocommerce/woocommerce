/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { closeSmall, external } from '@wordpress/icons';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { PRODUCTS_STORE_NAME, Product } from '@woocommerce/data';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { FormattedPrice } from '../../../components/formatted-price';
import { ProductEditorBlockEditProps } from '../../../types';
import { LinkedProductListBlockAttributes } from './types';

export function Edit( {
	attributes,
	context: { postType },
}: ProductEditorBlockEditProps< LinkedProductListBlockAttributes > ) {
	const { property } = attributes;
	const blockProps = useWooBlockProps( attributes );
	const [ linkedProductIds, setLinkedProductIds ] = useEntityProp< number[] >(
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

	function removeProductClickHandler( product: Product ) {
		return function handleRemoveProductClick() {
			const newLinkedProductIds = linkedProductIds.reduce< number[] >(
				( list, current ) => {
					if ( current === product.id ) return list;
					return [ ...list, current ];
				},
				[]
			);

			setLinkedProductIds( newLinkedProductIds );
		};
	}

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
									<a
										className="wp-block-woocommerce-product-linked-list-field__product-name"
										href={ getNewPath(
											{},
											`/product/${ product.id }`,
											{}
										) }
										target="_blank"
										rel="noreferrer"
									>
										{ product.name }
									</a>
									<FormattedPrice
										product={ product }
										className="wp-block-woocommerce-product-linked-list-field__product-price"
									/>
								</div>
							</div>
							<div
								role="cell"
								className="wp-block-woocommerce-product-linked-list-field__actions"
							>
								<Button
									icon={ external }
									size={ 24 }
									aria-label={ __(
										'See product page',
										'woocommerce'
									) }
									href={ product.permalink }
									target="_blank"
									rel="noreferrer"
								/>
								<Button
									icon={ closeSmall }
									size={ 24 }
									aria-label={ __(
										'Remove product',
										'woocommerce'
									) }
									onClick={ removeProductClickHandler(
										product
									) }
								/>
							</div>
						</div>
					) ) }
				</div>
			</div>
		</div>
	);
}
