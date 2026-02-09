/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import ProductControl from '@woocommerce/editor-components/product-control';
import { SelectedOption } from '@woocommerce/block-hocs';
import { WC_BLOCKS_IMAGE_URL } from '@woocommerce/block-settings';
import { useState, useRef } from '@wordpress/element';
import type { WooCommerceBlockLocation } from '@woocommerce/blocks/product-template/utils';
import { type ProductResponseItem, isEmpty } from '@woocommerce/types';
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

const REFERENCE_TYPE_PRODUCT = 'product';
const REFERENCE_TYPE_CART = 'cart';
const REFERENCE_TYPE_ORDER = 'order';

const ProductButton: React.FC< {
	isOpen: boolean;
	onToggle: () => void;
	product: ProductResponseItem | null;
	isLoading: boolean;
} > = ( { isOpen, onToggle, product, isLoading } ) => {
	if ( isLoading && ! product ) {
		return <Spinner />;
	}

	const showPlaceholder = ! product;
	const showPlaceholderImg = showPlaceholder || ! product?.images?.[ 0 ]?.src;
	const imgSrc = showPlaceholderImg
		? `${ WC_BLOCKS_IMAGE_URL }/blocks/product-collection/placeholder.svg`
		: product.images[ 0 ].src;
	const imgAlt = showPlaceholderImg ? '' : product?.name;

	return (
		<Button
			className="wc-block-product-collection-linked-product-control__button"
			onClick={ onToggle }
			aria-expanded={ isOpen }
			disabled={ isLoading }
		>
			<Flex direction="row" expanded justify="flex-start">
				<FlexItem className="wc-block-product-collection-linked-product-control__image-container">
					<img src={ imgSrc } alt={ imgAlt } />
				</FlexItem>

				<Flex
					direction="column"
					align="flex-start"
					gap={ 1 }
					className="wc-block-product-collection-linked-product-control__content"
				>
					{ showPlaceholder ? (
						<FlexItem>
							<Text color="inherit" lineHeight={ 1 }>
								{ __( 'Select product', 'woocommerce' ) }
							</Text>
						</FlexItem>
					) : (
						<>
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
						</>
					) }
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

const enum PRODUCT_REFERENCE_TYPE {
	CURRENT_PRODUCT = 'CURRENT_PRODUCT',
	SPECIFIC_PRODUCT = 'SPECIFIC_PRODUCT',
}

const getFromCurrentProductRadioLabel = (
	currentLocation: string,
	hasCartReference: boolean,
	hasOrderReference: boolean
): string => {
	if ( currentLocation === REFERENCE_TYPE_CART && hasCartReference ) {
		return __( 'From products in the cart', 'woocommerce' );
	}

	if ( currentLocation === REFERENCE_TYPE_ORDER && hasOrderReference ) {
		return __( 'From products in the order', 'woocommerce' );
	}

	return __( 'From the current product', 'woocommerce' );
};

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
	const isProductLocation = location.type === REFERENCE_TYPE_PRODUCT;
	const hasProductReference = !! usesReference?.includes(
		REFERENCE_TYPE_PRODUCT
	);
	const isCartLocation = location.type === REFERENCE_TYPE_CART;
	const hasCartReference = !! usesReference?.includes( REFERENCE_TYPE_CART );

	const isOrderLocation = location.type === REFERENCE_TYPE_ORDER;
	const hasOrderReference =
		!! usesReference?.includes( REFERENCE_TYPE_ORDER );

	const { productReference } = query;

	const { product, isLoading } = useGetProduct( productReference );
	const [ isDropdownOpen, setIsDropdownOpen ] = useState< boolean >( false );
	const [ radioControlState, setRadioControlState ] =
		useState< PRODUCT_REFERENCE_TYPE >(
			( isProductLocation || isCartLocation || isOrderLocation ) &&
				isEmpty( productReference )
				? PRODUCT_REFERENCE_TYPE.CURRENT_PRODUCT
				: PRODUCT_REFERENCE_TYPE.SPECIFIC_PRODUCT
		);
	const prevReference = useRef< number | undefined >( undefined );

	const showRadioControl =
		( isProductLocation && hasProductReference ) ||
		( isCartLocation && hasCartReference ) ||
		( isOrderLocation && hasOrderReference );
	const showSpecificProductSelector = showRadioControl
		? radioControlState === PRODUCT_REFERENCE_TYPE.SPECIFIC_PRODUCT
		: ! isEmpty( productReference );

	const showLinkedProductControl =
		( showRadioControl || showSpecificProductSelector ) &&
		/**
		 * Linked control is only useful for collection which uses product, cart or order reference.
		 */
		( hasProductReference || hasCartReference || hasOrderReference );
	if ( ! showLinkedProductControl ) return null;

	const radioControlHelp =
		radioControlState === PRODUCT_REFERENCE_TYPE.CURRENT_PRODUCT
			? __(
					'Linked products will be pulled from the product a shopper is currently viewing',
					'woocommerce'
			  )
			: __(
					'Select a product to pull the linked products from',
					'woocommerce'
			  );

	const handleRadioControlChange = ( newValue: PRODUCT_REFERENCE_TYPE ) => {
		if ( newValue === PRODUCT_REFERENCE_TYPE.CURRENT_PRODUCT ) {
			const { productReference: toSave, ...rest } = query;
			prevReference.current = toSave;
			setAttributes( { query: rest } );
		} else {
			setAttributes( {
				query: prevReference.current
					? {
							...query,
							productReference: prevReference.current,
					  }
					: query,
			} );
		}
		setRadioControlState( newValue );
	};

	const fromCurrentProductRadioLabel = getFromCurrentProductRadioLabel(
		location.type,
		hasCartReference,
		hasOrderReference
	);

	return (
		<PanelBody title={ __( 'Linked Product', 'woocommerce' ) }>
			{ showRadioControl && (
				<PanelRow>
					<RadioControl
						className="wc-block-product-collection-product-reference-radio"
						label={ __( 'Products to show', 'woocommerce' ) }
						help={ radioControlHelp }
						selected={ radioControlState }
						options={ [
							{
								label: fromCurrentProductRadioLabel,
								value: PRODUCT_REFERENCE_TYPE.CURRENT_PRODUCT,
							},
							{
								label: __(
									'From a specific product',
									'woocommerce'
								),
								value: PRODUCT_REFERENCE_TYPE.SPECIFIC_PRODUCT,
							},
						] }
						onChange={ handleRadioControlChange }
					/>
				</PanelRow>
			) }
			{ showSpecificProductSelector && (
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
