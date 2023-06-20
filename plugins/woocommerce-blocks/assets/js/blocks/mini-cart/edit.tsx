/**
 * External dependencies
 */
import {
	InspectorControls,
	useBlockProps,
	withColors,
	__experimentalColorGradientSettingsDropdown as ColorGradientSettingsDropdown,
	__experimentalUseMultipleOriginColorsAndGradients as useMultipleOriginColorsAndGradients,
} from '@wordpress/block-editor';
import { formatPrice } from '@woocommerce/price-format';
import {
	PanelBody,
	ExternalLink,
	ToggleControl,
	BaseControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalToggleGroupControl as ToggleGroupControl,
} from '@wordpress/components';
import { getSetting } from '@woocommerce/settings';
import { __, isRTL } from '@wordpress/i18n';
import Noninteractive from '@woocommerce/base-components/noninteractive';
import { isSiteEditorPage } from '@woocommerce/utils';
import type { ReactElement } from 'react';
import { select } from '@wordpress/data';
import classNames from 'classnames';
import { cartOutline, bag, bagAlt } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';
import { WC_BLOCKS_IMAGE_URL } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import QuantityBadge from './quantity-badge';
import './editor.scss';

interface Attributes {
	miniCartIcon: 'cart' | 'bag' | 'bag-alt';
	addToCartBehaviour: string;
	hasHiddenPrice: boolean;
	cartAndCheckoutRenderStyle: boolean;
	priceColor: string;
	iconColor: string;
	productCountColor: string;
	priceColorValue: string;
	iconColorValue: string;
	productCountColorValue: string;
}

interface Props {
	attributes: Attributes;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
	clientId: number;
	setPriceColor: ( colorValue: string | undefined ) => void;
	setIconColor: ( colorValue: string | undefined ) => void;
	setProductCountColor: ( colorValue: string | undefined ) => void;
}

const Edit = ( {
	attributes,
	setAttributes,
	clientId,
	setPriceColor,
	setIconColor,
	setProductCountColor,
}: Props ): ReactElement => {
	const {
		cartAndCheckoutRenderStyle,
		addToCartBehaviour,
		hasHiddenPrice,
		priceColorValue,
		iconColorValue,
		productCountColorValue,
		miniCartIcon,
	} = attributes;

	const className = classNames( {
		'wc-block-mini-cart': true,
		'has-price-color': priceColorValue,
		'has-icon-color': iconColorValue,
		'has-product-count-color': productCountColorValue,
	} );

	const blockProps = useBlockProps( { className } );

	const isSiteEditor = isSiteEditorPage( select( 'core/edit-site' ) );

	const templatePartEditUri = getSetting(
		'templatePartEditUri',
		''
	) as string;

	const productCount = 0;
	const productTotal = 0;

	const colorGradientSettings = useMultipleOriginColorsAndGradients();

	const colorSettings = [
		{
			value: priceColorValue,
			onChange: ( colorValue: string ) => {
				setPriceColor( colorValue );
				setAttributes( { priceColorValue: colorValue } );
			},
			label: __( 'Price', 'woo-gutenberg-products-block' ),
			resetAllFilter: () => {
				setPriceColor( undefined );
				setAttributes( { priceColorValue: undefined } );
			},
		},
		{
			value: iconColorValue,
			onChange: ( colorValue: string ) => {
				setIconColor( colorValue );
				setAttributes( { iconColorValue: colorValue } );
			},
			label: __( 'Icon', 'woo-gutenberg-products-block' ),
			resetAllFilter: () => {
				setIconColor( undefined );
				setAttributes( { iconColorValue: undefined } );
			},
		},
		{
			value: productCountColorValue,
			onChange: ( colorValue: string ) => {
				setProductCountColor( colorValue );
				setAttributes( { productCountColorValue: colorValue } );
			},
			label: __( 'Product count', 'woo-gutenberg-products-block' ),
			resetAllFilter: () => {
				setProductCountColor( undefined );
				setAttributes( { productCountColorValue: undefined } );
			},
		},
	];

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'woo-gutenberg-products-block' ) }
				>
					<ToggleGroupControl
						className="wc-block-editor-mini-cart__cart-icon-toggle"
						isBlock={ true }
						label={ __(
							'Cart Icon',
							'woo-gutenberg-products-block'
						) }
						value={ miniCartIcon }
						onChange={ ( value: 'cart' | 'bag' | 'bag-alt' ) => {
							setAttributes( {
								miniCartIcon: value,
							} );
						} }
					>
						<ToggleGroupControlOption
							value={ 'cart' }
							label={ <Icon size={ 32 } icon={ cartOutline } /> }
						/>
						<ToggleGroupControlOption
							value={ 'bag' }
							label={ <Icon size={ 32 } icon={ bag } /> }
						/>
						<ToggleGroupControlOption
							value={ 'bag-alt' }
							label={ <Icon size={ 32 } icon={ bagAlt } /> }
						/>
					</ToggleGroupControl>
					<BaseControl
						id="wc-block-mini-cart__display-toggle"
						label={ __(
							'Display',
							'woo-gutenberg-products-block'
						) }
					>
						<ToggleControl
							label={ __(
								'Display total price',
								'woo-gutenberg-products-block'
							) }
							help={ __(
								'Toggle to display the total price of products in the shopping cart. If no products have been added, the price will not display.',
								'woo-gutenberg-products-block'
							) }
							checked={ ! hasHiddenPrice }
							onChange={ () =>
								setAttributes( {
									hasHiddenPrice: ! hasHiddenPrice,
								} )
							}
						/>
					</BaseControl>
					{ isSiteEditor && (
						<ToggleGroupControl
							className="wc-block-editor-mini-cart__render-in-cart-and-checkout-toggle"
							label={ __(
								'Mini-Cart in cart and checkout pages',
								'woo-gutenberg-products-block'
							) }
							value={ cartAndCheckoutRenderStyle }
							onChange={ ( value: boolean ) => {
								setAttributes( {
									cartAndCheckoutRenderStyle: value,
								} );
							} }
							help={ __(
								'Select how the Mini-Cart behaves in the Cart and Checkout pages. This might affect the header layout.',
								'woo-gutenberg-products-block'
							) }
						>
							<ToggleGroupControlOption
								value={ 'hidden' }
								label={ __(
									'Hide',
									'woo-gutenberg-products-block'
								) }
							/>
							<ToggleGroupControlOption
								value={ 'removed' }
								label={ __(
									'Remove',
									'woo-gutenberg-products-block'
								) }
							/>
						</ToggleGroupControl>
					) }
				</PanelBody>
				<PanelBody
					title={ __(
						'Cart Drawer',
						'woo-gutenberg-products-block'
					) }
				>
					{ templatePartEditUri && (
						<>
							<img
								className="wc-block-editor-mini-cart__drawer-image"
								src={
									isRTL()
										? `${ WC_BLOCKS_IMAGE_URL }blocks/mini-cart/cart-drawer-rtl.svg`
										: `${ WC_BLOCKS_IMAGE_URL }blocks/mini-cart/cart-drawer.svg`
								}
								alt=""
							/>
							<p>
								{ __(
									'When opened, the Mini-Cart drawer gives shoppers quick access to view their selected products and checkout.',
									'woo-gutenberg-products-block'
								) }
							</p>
							<p className="wc-block-editor-mini-cart__drawer-link">
								<ExternalLink href={ templatePartEditUri }>
									{ __(
										'Edit Mini-Cart Drawer template',
										'woo-gutenberg-products-block'
									) }
								</ExternalLink>
							</p>
						</>
					) }
					<BaseControl
						id="wc-block-mini-cart__add-to-cart-behaviour-toggle"
						label={ __(
							'Behavior',
							'woo-gutenberg-products-block'
						) }
					>
						<ToggleControl
							label={ __(
								'Open drawer when adding',
								'woo-gutenberg-products-block'
							) }
							onChange={ ( value ) => {
								setAttributes( {
									addToCartBehaviour: value
										? 'open_drawer'
										: 'none',
								} );
							} }
							help={ __(
								'Toggle to open the Mini-Cart drawer when a shopper adds a product to their cart.',
								'woo-gutenberg-products-block'
							) }
							checked={ addToCartBehaviour === 'open_drawer' }
						/>
					</BaseControl>
				</PanelBody>
			</InspectorControls>
			{ colorGradientSettings.hasColorsOrGradients && (
				// @ts-to-do: Fix outdated InspectorControls type definitions in DefinitelyTyped and/or Gutenberg.
				<InspectorControls group="color">
					{ colorSettings.map(
						( { onChange, label, value, resetAllFilter } ) => (
							<ColorGradientSettingsDropdown
								key={ `mini-cart-color-${ label }` }
								__experimentalIsRenderedInSidebar
								settings={ [
									{
										colorValue: value,
										label,
										onColorChange: onChange,
										isShownByDefault: true,
										resetAllFilter,
										enableAlpha: true,
									},
								] }
								panelId={ clientId }
								{ ...colorGradientSettings }
							/>
						)
					) }
				</InspectorControls>
			) }
			<Noninteractive>
				<button className="wc-block-mini-cart__button">
					{ ! hasHiddenPrice && (
						<span
							className="wc-block-mini-cart__amount"
							style={ { color: priceColorValue } }
						>
							{ formatPrice( productTotal ) }
						</span>
					) }
					<QuantityBadge
						count={ productCount }
						iconColor={ iconColorValue }
						productCountColor={ productCountColorValue }
						icon={ miniCartIcon }
					/>
				</button>
			</Noninteractive>
		</div>
	);
};

const miniCartColorAttributes = {
	priceColor: 'price-color',
	iconColor: 'icon-color',
	productCountColor: 'product-count-color',
};

// @ts-expect-error: TypeScript doesn't resolve the shared React dependency and cannot resolve the type returned by `withColors`.
// Similar issue example: https://github.com/microsoft/TypeScript/issues/47663
const EditWithColors: JSX.Element = withColors( miniCartColorAttributes )(
	Edit
);

export default EditWithColors;
