/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { closeSmall, external } from '@wordpress/icons';
import { getNewPath } from '@woocommerce/navigation';
import { Product } from '@woocommerce/data';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { FormattedPrice } from '../formatted-price';
import { ProductImage } from '../product-image';
import { ProductListProps } from './types';

export function ProductList( {
	products,
	onRemove,
	onEdit,
	onPreview,
	className,
	...props
}: ProductListProps ) {
	function nameLinkClickHandler( product: Product ) {
		return function handleNameLinkClick() {
			if ( onEdit ) {
				onEdit( product );
			}
		};
	}

	function previewLinkClickHandler( product: Product ) {
		return function handlePreviewLinkClick() {
			if ( onPreview ) {
				onPreview( product );
			}
		};
	}

	function removeClickHandler( product: Product ) {
		return function handleRemoveClick() {
			if ( onRemove ) {
				onRemove( product );
			}
		};
	}

	return (
		<div
			{ ...props }
			className={ classNames( 'woocommerce-product-list', className ) }
		>
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
					{ products.map( ( product ) => (
						<div role="row" key={ product.id }>
							<div role="cell">
								<ProductImage
									product={ product }
									className="woocommerce-product-list__product-image"
								/>
								<div className="woocommerce-product-list__product-info">
									<a
										className="woocommerce-product-list__product-name"
										href={ getNewPath(
											{},
											`/product/${ product.id }`,
											{}
										) }
										target="_blank"
										rel="noreferrer"
										onClick={ nameLinkClickHandler(
											product
										) }
									>
										{ product.name }
									</a>
									<FormattedPrice
										product={ product }
										className="woocommerce-product-list__product-price"
									/>
								</div>
							</div>
							<div
								role="cell"
								className="woocommerce-product-list__actions"
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
									onClick={ previewLinkClickHandler(
										product
									) }
								/>
								<Button
									icon={ closeSmall }
									size={ 24 }
									aria-label={ __(
										'Remove product',
										'woocommerce'
									) }
									onClick={ removeClickHandler( product ) }
								/>
							</div>
						</div>
					) ) }
				</div>
			</div>
		</div>
	);
}
