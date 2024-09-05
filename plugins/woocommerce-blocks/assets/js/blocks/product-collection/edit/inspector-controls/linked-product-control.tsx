/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import ProductControl from '@woocommerce/editor-components/product-control';
import { SelectedOption } from '@woocommerce/block-hocs';
import { useState, useMemo } from '@wordpress/element';
import type { WooCommerceBlockLocation } from '@woocommerce/blocks/product-template/utils';
import type { ProductResponseItem } from '@woocommerce/types';
import { decodeEntities } from '@wordpress/html-entities';
import {
	PanelBody,
	PanelRow,
	Button,
	Flex,
	FlexItem,
	Dropdown,
	RadioControl,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalText as Text,
	Spinner,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useGetProduct } from '../../utils';
import type {
	ProductCollectionQuery,
	ProductCollectionSetAttributes,
} from '../../types';

const ProductButton: React.FC< {
	isOpen: boolean;
	onToggle: () => void;
	product: ProductResponseItem | null;
	isLoading: boolean;
} > = ( { isOpen, onToggle, product, isLoading } ) => {
	if ( isLoading && ! product ) {
		return <Spinner />;
	}

	return (
		<Button
			className="wc-block-product-collection-linked-product-control__button"
			onClick={ onToggle }
			aria-expanded={ isOpen }
			disabled={ isLoading }
		>
			<Flex direction="row" expanded justify="flex-start">
				<FlexItem className="wc-block-product-collection-linked-product-control__image-container">
					<img
						src={ product?.images?.[ 0 ]?.src }
						alt={ product?.name }
					/>
				</FlexItem>

				<Flex
					direction="column"
					align="flex-start"
					gap={ 1 }
					className="wc-block-product-collection-linked-product-control__content"
				>
					<FlexItem>
						<Text color="inherit" lineHeight={ 1 }>
							{ product?.name
								? decodeEntities( product.name )
								: '' }
						</Text>
					</FlexItem>
					<FlexItem>
						<Text color="inherit" lineHeight={ 1 }>
							{ product?.sku }
						</Text>
					</FlexItem>
				</Flex>
			</Flex>
		</Button>
	);
};

const LinkedProductPopoverContent: React.FC< {
	query: ProductCollectionQuery;
	setAttributes: ProductCollectionSetAttributes;
	setIsDropdownOpen: React.Dispatch< React.SetStateAction< boolean > >;
} > = ( { query, setAttributes, setIsDropdownOpen } ) => (
	<ProductControl
		selected={ query?.productReference as SelectedOption }
		onChange={ ( value: { id: number }[] = [] ) => {
			const productId = value[ 0 ]?.id ?? null;
			if ( productId !== null ) {
				setAttributes( {
					query: {
						...query,
						productReference: productId,
					},
				} );
				setIsDropdownOpen( false );
			}
		} }
		messages={ {
			search: __( 'Select a product', 'woocommerce' ),
		} }
	/>
);

const enum PRODUCT_REF {
	CURRENT_PRODUCT = 'CURRENT_PRODUCT',
	SPECIFIC_PRODUCT = 'SPECIFIC_PRODUCT',
}

const LinkedProductControl = ( {
	query,
	setAttributes,
	location,
	usesReference,
}: {
	query: ProductCollectionQuery;
	setAttributes: ProductCollectionSetAttributes;
	location: WooCommerceBlockLocation;
	usesReference: string[] | undefined;
} ) => {
	const [ isDropdownOpen, setIsDropdownOpen ] = useState< boolean >( false );
	const [ productReference, setProductReference ] = useState< PRODUCT_REF >(
		PRODUCT_REF.CURRENT_PRODUCT
	);
	const { product, isLoading } = useGetProduct( query.productReference );

	const showDropdown = productReference === PRODUCT_REF.SPECIFIC_PRODUCT;
	const showLinkedProductControl = useMemo( () => {
		const isInRequiredLocation = usesReference?.includes( location.type );
		const isProductContextRequired = usesReference?.includes( 'product' );
		const isProductContextSelected =
			( query?.productReference ?? null ) !== null;

		return (
			isProductContextRequired &&
			! isInRequiredLocation &&
			isProductContextSelected
		);
	}, [ location.type, query?.productReference, usesReference ] );

	if ( ! showLinkedProductControl ) return null;

	const radioControlHelp =
		productReference === PRODUCT_REF.CURRENT_PRODUCT
			? __(
					'Related products will be pulled from the product a shopper is currently viewing',
					'woocommerce'
			  )
			: __(
					'Select a product to pull the related products from',
					'woocommerce'
			  );

	return (
		<PanelBody title={ __( 'Linked Product', 'woocommerce' ) }>
			<PanelRow>
				<RadioControl
					className="wc-block-product-collection-product-reference-radio"
					label={ __( 'Products to show', 'woocommerce' ) }
					help={ radioControlHelp }
					selected={ productReference }
					options={ [
						{
							label: __(
								'From the current product',
								'woocommerce'
							),
							value: PRODUCT_REF.CURRENT_PRODUCT,
						},
						{
							label: __(
								'From a specific product',
								'woocommerce'
							),
							value: PRODUCT_REF.SPECIFIC_PRODUCT,
						},
					] }
					onChange={ setProductReference }
				/>
			</PanelRow>
			{ showDropdown && (
				<PanelRow>
					<Dropdown
						className="wc-block-product-collection-linked-product-control"
						contentClassName="wc-block-product-collection-linked-product__popover-content"
						popoverProps={ { placement: 'left-start' } }
						renderToggle={ ( { isOpen, onToggle } ) => (
							<ProductButton
								isOpen={ isOpen }
								onToggle={ onToggle }
								product={ product }
								isLoading={ isLoading }
							/>
						) }
						renderContent={ () => (
							<LinkedProductPopoverContent
								query={ query }
								setAttributes={ setAttributes }
								setIsDropdownOpen={ setIsDropdownOpen }
							/>
						) }
						open={ isDropdownOpen }
						onToggle={ () => setIsDropdownOpen( ! isDropdownOpen ) }
					/>
				</PanelRow>
			) }
		</PanelBody>
	);
};

export default LinkedProductControl;
