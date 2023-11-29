/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { resolveSelect } from '@wordpress/data';
import {
	createElement,
	useContext,
	useEffect,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { external, closeSmall } from '@wordpress/icons';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { CurrencyContext } from '@woocommerce/currency';
import { PRODUCTS_STORE_NAME, Product } from '@woocommerce/data';
import { getNewPath } from '@woocommerce/navigation';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import {
	AddProductsModal,
	getProductImageStyle,
} from '../../../components/add-products-modal';
import { ProductEditorBlockEditProps } from '../../../types';
import { Shirt, Pants, Glasses } from './images';
import { UploadsBlockAttributes } from './types';
import {
	getProductStockStatus,
	getProductStockStatusClass,
} from '../../../utils';

export function Edit( {
	attributes,
	context: { postType },
}: ProductEditorBlockEditProps< UploadsBlockAttributes > ) {
	const { property } = attributes;
	const blockProps = useWooBlockProps( attributes );
	const [ openAddProductsModal, setOpenAddProductsModal ] = useState( false );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ groupedProductIds, setGroupedProductIds ] = useEntityProp<
		number[]
	>( 'postType', postType, property );
	const [ groupedProducts, setGroupedProducts ] = useState< Product[] >( [] );
	const { formatAmount } = useContext( CurrencyContext );

	useEffect(
		function loadGroupedProducts() {
			if ( groupedProductIds.length ) {
				setIsLoading( false );
				resolveSelect( PRODUCTS_STORE_NAME )
					.getProducts< Product[] >( {
						include: groupedProductIds,
						orderby: 'include',
					} )
					.then( setGroupedProducts )
					.finally( () => setIsLoading( false ) );
			} else {
				setGroupedProducts( [] );
			}
		},
		[ groupedProductIds ]
	);

	function handleAddProductsButtonClick() {
		setOpenAddProductsModal( true );
	}

	function handleAddProductsModalSubmit( value: Product[] ) {
		setGroupedProductIds( [
			...groupedProductIds,
			...value.map( ( product ) => product.id ),
		] );
		setOpenAddProductsModal( false );
	}

	function handleAddProductsModalClose() {
		setOpenAddProductsModal( false );
	}

	function removeProductHandler( product: Product ) {
		return function handleRemoveClick() {
			const newGroupedProductIds = groupedProductIds.filter(
				( productId ) => productId !== product.id
			);
			setGroupedProductIds( newGroupedProductIds );
		};
	}

	return (
		<div { ...blockProps }>
			<div className="wp-block-woocommerce-product-list-field__header">
				<Button
					onClick={ handleAddProductsButtonClick }
					variant="secondary"
				>
					{ __( 'Add products', 'woocommerce' ) }
				</Button>
			</div>

			<div className="wp-block-woocommerce-product-list-field__body">
				{ ! isLoading && groupedProducts.length === 0 && (
					<div className="wp-block-woocommerce-product-list-field__empty-state">
						<div
							className="wp-block-woocommerce-product-list-field__empty-state-illustration"
							role="presentation"
						>
							<Shirt />
							<Pants />
							<Glasses />
						</div>
						<p className="wp-block-woocommerce-product-list-field__empty-state-tip">
							{ __(
								'Tip: Group together items that have a clear relationship or compliment each other well, e.g., garment bundles, camera kits, or skincare product sets.',
								'woocommerce'
							) }
						</p>
					</div>
				) }

				{ ! isLoading && groupedProducts.length > 0 && (
					<div
						className="wp-block-woocommerce-product-list-field__table"
						role="table"
					>
						<div className="wp-block-woocommerce-product-list-field__table-header">
							<div
								className="wp-block-woocommerce-product-list-field__table-row"
								role="rowheader"
							>
								<div
									className="wp-block-woocommerce-product-list-field__table-header-column"
									role="columnheader"
								>
									{ __( 'Product', 'woocommerce' ) }
								</div>
								<div
									className="wp-block-woocommerce-product-list-field__table-header-column"
									role="columnheader"
								>
									{ __( 'Price', 'woocommerce' ) }
								</div>
								<div
									className="wp-block-woocommerce-product-list-field__table-header-column"
									role="columnheader"
								>
									{ __( 'Stock', 'woocommerce' ) }
								</div>
								<div
									className="wp-block-woocommerce-product-list-field__table-header-column"
									role="columnheader"
								/>
							</div>
						</div>
						<div
							className="wp-block-woocommerce-product-list-field__table-body"
							role="rowgroup"
						>
							{ groupedProducts.map( ( product ) => (
								<div
									key={ product.id }
									className="wp-block-woocommerce-product-list-field__table-row"
									role="row"
								>
									<div
										className="wp-block-woocommerce-product-list-field__table-cell"
										role="cell"
									>
										<div
											className="wp-block-woocommerce-product-list-field__product-image"
											style={ getProductImageStyle(
												product
											) }
										/>

										<div className="wp-block-woocommerce-product-list-field__product-info">
											<div className="wp-block-woocommerce-product-list-field__product-name">
												<Button
													variant="link"
													href={ getNewPath(
														{},
														`/product/${ product.id }`
													) }
													target="_blank"
												>
													{ product.name }
												</Button>
											</div>

											<div className="wp-block-woocommerce-product-list-field__product-sku">
												{ product.sku }
											</div>
										</div>
									</div>
									<div
										className="wp-block-woocommerce-product-list-field__table-cell"
										role="cell"
									>
										{ product.sale_price && (
											<span>
												{ formatAmount(
													product.sale_price
												) }
											</span>
										) }
										<span
											className={ classNames( {
												'wp-block-woocommerce-product-list-field__price--on-sale':
													product.sale_price,
											} ) }
										>
											{ formatAmount(
												product.regular_price
											) }
										</span>
									</div>
									<div
										className="wp-block-woocommerce-product-list-field__table-cell"
										role="cell"
									>
										<span
											className={ classNames(
												'woocommerce-product-variations__status-dot',
												getProductStockStatusClass(
													product
												)
											) }
										>
											●
										</span>
										<span>
											{ getProductStockStatus( product ) }
										</span>
									</div>
									<div
										className="wp-block-woocommerce-product-list-field__table-cell"
										role="cell"
									>
										<Button
											variant="tertiary"
											icon={ external }
											aria-label={ __(
												'Preview the product',
												'woocommerce'
											) }
											href={ product.permalink }
											target="_blank"
										/>

										<Button
											type="button"
											variant="tertiary"
											icon={ closeSmall }
											aria-label={ __(
												'Remove product',
												'woocommerce'
											) }
											onClick={ removeProductHandler(
												product
											) }
										/>
									</div>
								</div>
							) ) }
						</div>
					</div>
				) }
			</div>

			{ openAddProductsModal && (
				<AddProductsModal
					initialValue={ groupedProducts }
					onSubmit={ handleAddProductsModalSubmit }
					onClose={ handleAddProductsModalClose }
				/>
			) }
		</div>
	);
}
