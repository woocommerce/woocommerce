/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import ProductControl from '@woocommerce/editor-components/product-control';
import { SelectedOption } from '@woocommerce/block-hocs';
import { useState, useMemo } from '@wordpress/element';
import { WooCommerceBlockLocation } from '@woocommerce/blocks/product-template/utils';
import {
	PanelBody,
	PanelRow,
	Button,
	Flex,
	FlexItem,
	Dropdown,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalText as Text,
	Spinner,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useGetProduct } from '../../utils';
import {
	ProductCollectionQuery,
	ProductCollectionSetAttributes,
} from '../../types';

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
	const { product, isLoading } = useGetProduct( query.productReference );

	const isShowLinkedProductControl = useMemo( () => {
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

	const jsxProductButton = ( {
		isOpen,
		onToggle,
	}: {
		isOpen: boolean;
		onToggle: () => void;
	} ) => {
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
					<Flex direction="column" align="flex-start" gap={ 0 }>
						<FlexItem>
							<Text color="inherit">{ product?.name }</Text>
						</FlexItem>
						<FlexItem>
							<Text color="inherit">{ product?.sku }</Text>
						</FlexItem>
					</Flex>
				</Flex>
			</Button>
		);
	};

	const jsxPopoverContent = (
		<ProductControl
			selected={ query?.productReference as SelectedOption }
			onChange={ ( value = [] ) => {
				const isValidId = ( value[ 0 ]?.id ?? null ) !== null;
				if ( isValidId ) {
					setAttributes( {
						query: {
							...query,
							productReference: value[ 0 ].id,
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

	if ( ! isShowLinkedProductControl ) return null;

	return (
		<PanelBody title={ __( 'Linked Product', 'woocommerce' ) }>
			<PanelRow>
				<Dropdown
					className="wc-block-product-collection-linked-product-control"
					contentClassName="wc-block-product-collection-linked-product__popover-content"
					popoverProps={ { placement: 'left-start' } }
					renderToggle={ ( { isOpen, onToggle } ) =>
						jsxProductButton( { isOpen, onToggle } )
					}
					renderContent={ () => jsxPopoverContent }
					open={ isDropdownOpen }
					onToggle={ () => setIsDropdownOpen( ! isDropdownOpen ) }
				/>
			</PanelRow>
		</PanelBody>
	);
};

export default LinkedProductControl;
