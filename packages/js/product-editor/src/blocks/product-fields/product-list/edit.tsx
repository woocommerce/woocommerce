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
import { productsStore, Product } from '@woocommerce/data';
import { getNewPath } from '@woocommerce/navigation';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import {
	AddProductsModal,
	getProductImageStyle,
	ReorderProductsModal,
} from '../../../components/add-products-modal';
import { ProductEditorBlockEditProps } from '../../../types';
import { UploadsBlockAttributes } from './types';
import {
	getProductStockStatus,
	getProductStockStatusClass,
} from '../../../utils';
import { Shirt } from '../../../images/shirt';
import { Pants } from '../../../images/pants';
import { Glasses } from '../../../images/glasses';
import { AdviceCard } from '../../../components/advice-card';
import { SectionActions } from '../../../components/block-slot-fill';

export function ProductListBlockEdit( {
	attributes,
	context: { postType },
}: ProductEditorBlockEditProps< UploadsBlockAttributes > ) {
	const { property } = attributes;
	const blockProps = useWooBlockProps( attributes );
	const [ openAddProductsModal, setOpenAddProductsModal ] = useState( false );
	const [ openReorderProductsModal, setOpenReorderProductsModal ] =
		useState( false );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ preventFetch, setPreventFetch ] = useState( false );
	const [ groupedProductIds, setGroupedProductIds ] = useEntityProp<
		number[]
	>( 'postType', postType, property );
	const [ groupedProducts, setGroupedProducts ] = useState< Product[] >( [] );
	const { formatAmount } = useContext( CurrencyContext );

	useEffect(
		function loadGroupedProducts() {
			if ( preventFetch ) return;

			if ( groupedProductIds.length ) {
				setIsLoading( false );
				resolveSelect( productsStore )
					.getProducts( {
						include: groupedProductIds,
						orderby: 'include',
					} )
					.then( setGroupedProducts )
					.finally( () => setIsLoading( false ) );
			} else {
				setGroupedProducts( [] );
			}
		},
		[ groupedProductIds, preventFetch ]
	);

	function handleAddProductsButtonClick() {
		setOpenAddProductsModal( true );
	}

	function handleReorderProductsButtonClick() {
		setOpenReorderProductsModal( true );
	}

	function handleAddProductsModalSubmit( value: Product[] ) {
		const newGroupedProducts = [ ...groupedProducts, ...value ];
		setPreventFetch( true );
		setGroupedProducts( newGroupedProducts );
		setGroupedProductIds(
			newGroupedProducts.map( ( product ) => product.id )
		);
		setOpenAddProductsModal( false );
	}

	function handleReorderProductsModalSubmit( value: Product[] ) {
		setGroupedProducts( value );
		setGroupedProductIds( value.map( ( product ) => product.id ) );
		setOpenReorderProductsModal( false );
	}

	function handleAddProductsModalClose() {
		setOpenAddProductsModal( false );
	}

	function handleReorderProductsModalClose() {
		setOpenReorderProductsModal( false );
	}

	function removeProductHandler( product: Product ) {
		return function handleRemoveClick() {
			const newGroupedProducts = groupedProducts.filter(
				( groupedProduct ) => groupedProduct.id !== product.id
			);
			setPreventFetch( true );
			setGroupedProducts( newGroupedProducts );
			setGroupedProductIds(
				newGroupedProducts.map(
					( groupedProduct ) => groupedProduct.id
				)
			);
		};
	}

	return (
		<div { ...blockProps }>
			<SectionActions>
				{ ! isLoading && groupedProducts.length > 0 && (
					<Button
						onClick={ handleReorderProductsButtonClick }
						variant="tertiary"
					>
						{ __( 'Reorder', 'woocommerce' ) }
					</Button>
				) }
				<Button
					onClick={ handleAddProductsButtonClick }
					variant="secondary"
				>
					{ __( 'Add products', 'woocommerce' ) }
				</Button>
			</SectionActions>

			<div className="wp-block-woocommerce-product-list-field__body">
				{ ! isLoading && groupedProducts.length === 0 && (
					<AdviceCard
						tip={ __(
							'Tip: Group together items that have a clear relationship or compliment each other well, e.g., garment bundles, camera kits, or skincare product sets.',
							'woocommerce'
						) }
						isDismissible={ false }
					>
						<Shirt />
						<Pants />
						<Glasses />
					</AdviceCard>
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
										{ product.on_sale && (
											<span>
												{ product.sale_price
													? formatAmount(
															product.sale_price
													  )
													: formatAmount(
															product.price
													  ) }
											</span>
										) }

										{ product.regular_price && (
											<span
												className={ classNames( {
													'wp-block-woocommerce-product-list-field__price--on-sale':
														product.on_sale,
												} ) }
											>
												{ formatAmount(
													product.regular_price
												) }
											</span>
										) }
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
			{ openReorderProductsModal && (
				<ReorderProductsModal
					products={ groupedProducts }
					onSubmit={ handleReorderProductsModalSubmit }
					onClose={ handleReorderProductsModalClose }
				/>
			) }
		</div>
	);
}
