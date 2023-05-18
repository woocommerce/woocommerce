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
import { __ } from '@wordpress/i18n';
import Noninteractive from '@woocommerce/base-components/noninteractive';
import { isSiteEditorPage } from '@woocommerce/utils';
import type { ReactElement } from 'react';
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import QuantityBadge from './quantity-badge';
import './editor.scss';

interface Attributes {
	addToCartBehaviour: string;
	hasHiddenPrice: boolean;
	cartAndCheckoutRenderStyle: boolean;
}

interface Props {
	attributes: Attributes;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
}

const Edit = ( { attributes, setAttributes }: Props ): ReactElement => {
	const { addToCartBehaviour, hasHiddenPrice, cartAndCheckoutRenderStyle } =
		attributes;
	const blockProps = useBlockProps( {
		className: `wc-block-mini-cart`,
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
				<PanelBody
					title={ __( 'Settings', 'woo-gutenberg-products-block' ) }
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
					{ isSiteEditor && (
						<ToggleGroupControl
							className="wc-block-editor-mini-cart__render-in-cart-and-checkout-toggle"
							label={ __(
								'Mini-Cart in cart and checkout pages',
								'woo-gutenberg-products-block'
							) }
							value={ cartAndCheckoutRenderStyle }
							onChange={ ( value ) => {
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
								src="/wp-content/plugins/woocommerce-blocks/images/blocks/mini-cart/cart-drawer.svg"
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
			<Noninteractive>
				<button className="wc-block-mini-cart__button">
					{ ! hasHiddenPrice && (
						<span className="wc-block-mini-cart__amount">
							{ formatPrice( productTotal ) }
						</span>
					) }
					<QuantityBadge count={ productCount } />
				</button>
			</Noninteractive>
		</div>
	);
};

export default Edit;
