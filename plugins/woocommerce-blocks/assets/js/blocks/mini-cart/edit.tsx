/**
 * External dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
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
import { cartOutline, bag, bagAlt } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';
import { WC_BLOCKS_IMAGE_URL } from '@woocommerce/block-settings';
import { ColorPanel } from '@woocommerce/editor-components/color-panel';
import type { ColorPaletteOption } from '@woocommerce/editor-components/color-panel/types';

/**
 * Internal dependencies
 */
import QuantityBadge from './quantity-badge';
import { defaultColorItem } from './utils/defaults';
import { migrateAttributesToColorPanel } from './utils/data';
import './editor.scss';

export interface Attributes {
	miniCartIcon: 'cart' | 'bag' | 'bag-alt';
	addToCartBehaviour: string;
	hasHiddenPrice: boolean;
	cartAndCheckoutRenderStyle: boolean;
	priceColor: ColorPaletteOption;
	iconColor: ColorPaletteOption;
	productCountColor: ColorPaletteOption;
}

interface Props {
	attributes: Attributes;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
	clientId: number;
	setPriceColor: ( colorValue: string | undefined ) => void;
	setIconColor: ( colorValue: string | undefined ) => void;
	setProductCountColor: ( colorValue: string | undefined ) => void;
}

const Edit = ( { attributes, setAttributes }: Props ): ReactElement => {
	const {
		cartAndCheckoutRenderStyle,
		addToCartBehaviour,
		hasHiddenPrice,
		priceColor = defaultColorItem,
		iconColor = defaultColorItem,
		productCountColor = defaultColorItem,
		miniCartIcon,
	} = migrateAttributesToColorPanel( attributes );

	const miniCartColorAttributes = {
		priceColor: {
			label: __( 'Price', 'woocommerce' ),
			context: 'price-color',
		},
		iconColor: {
			label: __( 'Icon', 'woocommerce' ),
			context: 'icon-color',
		},
		productCountColor: {
			label: __( 'Product Count', 'woocommerce' ),
			context: 'product-count-color',
		},
	};

	const blockProps = useBlockProps( {
		className: 'wc-block-mini-cart',
	} );

	const isSiteEditor = isSiteEditorPage( select( 'core/edit-site' ) );

	const templatePartEditUri = getSetting(
		'templatePartEditUri',
		''
	) as string;

	const productCount = 0;
	const productTotal = 0;
	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
					<ToggleGroupControl
						className="wc-block-editor-mini-cart__cart-icon-toggle"
						isBlock={ true }
						label={ __( 'Cart Icon', 'woocommerce' ) }
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
						label={ __( 'Display', 'woocommerce' ) }
					>
						<ToggleControl
							label={ __( 'Display total price', 'woocommerce' ) }
							help={ __(
								'Toggle to display the total price of products in the shopping cart. If no products have been added, the price will not display.',
								'woocommerce'
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
								'woocommerce'
							) }
							value={ cartAndCheckoutRenderStyle }
							onChange={ ( value: boolean ) => {
								setAttributes( {
									cartAndCheckoutRenderStyle: value,
								} );
							} }
							help={ __(
								'Select how the Mini-Cart behaves in the Cart and Checkout pages. This might affect the header layout.',
								'woocommerce'
							) }
						>
							<ToggleGroupControlOption
								value={ 'hidden' }
								label={ __( 'Hide', 'woocommerce' ) }
							/>
							<ToggleGroupControlOption
								value={ 'removed' }
								label={ __( 'Remove', 'woocommerce' ) }
							/>
						</ToggleGroupControl>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Cart Drawer', 'woocommerce' ) }>
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
									'woocommerce'
								) }
							</p>
							<p className="wc-block-editor-mini-cart__drawer-link">
								<ExternalLink href={ templatePartEditUri }>
									{ __(
										'Edit Mini-Cart Drawer template',
										'woocommerce'
									) }
								</ExternalLink>
							</p>
						</>
					) }
					<BaseControl
						id="wc-block-mini-cart__add-to-cart-behaviour-toggle"
						label={ __( 'Behavior', 'woocommerce' ) }
					>
						<ToggleControl
							label={ __(
								'Open drawer when adding',
								'woocommerce'
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
								'woocommerce'
							) }
							checked={ addToCartBehaviour === 'open_drawer' }
						/>
					</BaseControl>
				</PanelBody>
			</InspectorControls>
			<ColorPanel colorTypes={ miniCartColorAttributes } />
			<Noninteractive>
				<button className="wc-block-mini-cart__button">
					{ ! hasHiddenPrice && (
						<span
							className="wc-block-mini-cart__amount"
							style={ { color: priceColor.color } }
						>
							{ formatPrice( productTotal ) }
						</span>
					) }
					<QuantityBadge
						count={ productCount }
						iconColor={ iconColor }
						productCountColor={ productCountColor }
						icon={ miniCartIcon }
					/>
				</button>
			</Noninteractive>
		</div>
	);
};

export default Edit;
