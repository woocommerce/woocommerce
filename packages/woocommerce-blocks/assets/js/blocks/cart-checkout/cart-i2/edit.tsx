/* tslint:disable */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { CartCheckoutFeedbackPrompt } from '@woocommerce/editor-components/feedback-prompt';
import {
	useBlockProps,
	InnerBlocks,
	InspectorControls,
	BlockControls,
} from '@wordpress/block-editor';
import { PanelBody, ToggleControl, Notice } from '@wordpress/components';
import { CartCheckoutCompatibilityNotice } from '@woocommerce/editor-components/compatibility-notices';
import { CART_PAGE_ID } from '@woocommerce/block-settings';
import BlockErrorBoundary from '@woocommerce/base-components/block-error-boundary';
import {
	EditorProvider,
	useEditorContext,
	CartProvider,
} from '@woocommerce/base-context';
import { createInterpolateElement } from '@wordpress/element';
import { getAdminLink, getSetting } from '@woocommerce/settings';
import { previewCart } from '@woocommerce/resource-previews';
import { Icon, filledCart, removeCart } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import './editor.scss';
import { addClassToBody, useBlockPropsWithLocking } from './hacks';
import { useViewSwitcher } from './use-view-switcher';
import type { Attributes } from './types';
import { CartBlockContext } from './context';

// This is adds a class to body to signal if the selected block is locked
addClassToBody();

// Array of allowed block names.
const ALLOWED_BLOCKS: string[] = [
	'woocommerce/filled-cart-block',
	'woocommerce/empty-cart-block',
];

const BlockSettings = ( {
	attributes,
	setAttributes,
}: {
	attributes: Attributes;
	setAttributes: ( attributes: Record< string, unknown > ) => undefined;
} ): JSX.Element => {
	const { isShippingCalculatorEnabled, showRateAfterTaxName } = attributes;
	const { currentPostId } = useEditorContext();
	return (
		<InspectorControls>
			{ currentPostId !== CART_PAGE_ID && (
				<Notice
					className="wc-block-cart__page-notice"
					isDismissible={ false }
					status="warning"
				>
					{ createInterpolateElement(
						__(
							'If you would like to use this block as your default cart you must update your <a>page settings in WooCommerce</a>.',
							'woo-gutenberg-products-block'
						),
						{
							a: (
								// eslint-disable-next-line jsx-a11y/anchor-has-content
								<a
									href={ getAdminLink(
										'admin.php?page=wc-settings&tab=advanced'
									) }
									target="_blank"
									rel="noopener noreferrer"
								/>
							),
						}
					) }
				</Notice>
			) }
			{ getSetting( 'shippingEnabled', true ) && (
				<PanelBody
					title={ __(
						'Shipping rates',
						'woo-gutenberg-products-block'
					) }
				>
					<ToggleControl
						label={ __(
							'Shipping calculator',
							'woo-gutenberg-products-block'
						) }
						help={ __(
							'Allow customers to estimate shipping by entering their address.',
							'woo-gutenberg-products-block'
						) }
						checked={ isShippingCalculatorEnabled }
						onChange={ () =>
							setAttributes( {
								isShippingCalculatorEnabled: ! isShippingCalculatorEnabled,
							} )
						}
					/>
				</PanelBody>
			) }
			{ getSetting( 'taxesEnabled' ) &&
				getSetting( 'displayItemizedTaxes', false ) &&
				! getSetting( 'displayCartPricesIncludingTax', false ) && (
					<PanelBody
						title={ __( 'Taxes', 'woo-gutenberg-products-block' ) }
					>
						<ToggleControl
							label={ __(
								'Show rate after tax name',
								'woo-gutenberg-products-block'
							) }
							help={ __(
								'Show the percentage rate alongside each tax line in the summary.',
								'woo-gutenberg-products-block'
							) }
							checked={ showRateAfterTaxName }
							onChange={ () =>
								setAttributes( {
									showRateAfterTaxName: ! showRateAfterTaxName,
								} )
							}
						/>
					</PanelBody>
				) }
			<CartCheckoutFeedbackPrompt />
		</InspectorControls>
	);
};

/**
 * Component to handle edit mode of "Cart Block".
 */
export const Edit = ( {
	className,
	attributes,
	setAttributes,
	clientId,
}: {
	className: string;
	attributes: Attributes;
	setAttributes: ( attributes: Record< string, unknown > ) => undefined;
	clientId: string;
} ): JSX.Element => {
	const { currentView, component: ViewSwitcherComponent } = useViewSwitcher(
		clientId,
		[
			{
				view: 'woocommerce/filled-cart-block',
				label: __( 'Filled Cart', 'woo-gutenberg-products-block' ),
				icon: <Icon srcElement={ filledCart } />,
			},
			{
				view: 'woocommerce/empty-cart-block',
				label: __( 'Empty Cart', 'woo-gutenberg-products-block' ),
				icon: <Icon srcElement={ removeCart } />,
			},
		]
	);
	const cartClassName = classnames( {
		'has-dark-controls': attributes.hasDarkControls,
	} );
	const defaultInnerBlocksTemplate = [
		[
			'woocommerce/filled-cart-block',
			{},
			[
				[
					'woocommerce/cart-items-block',
					{},
					[ [ 'woocommerce/cart-line-items-block', {}, [] ] ],
				],
				[
					'woocommerce/cart-totals-block',
					{},
					[
						[ 'woocommerce/cart-order-summary-block', {}, [] ],
						[ 'woocommerce/cart-express-payment-block', {}, [] ],
						[ 'woocommerce/proceed-to-checkout-block', {}, [] ],
					],
				],
			],
		],
		[ 'woocommerce/empty-cart-block', {}, [] ],
	];
	const blockProps = useBlockPropsWithLocking( {
		className: classnames( className, 'wp-block-woocommerce-cart', {
			'is-editor-preview': attributes.isPreview,
		} ),
	} );

	return (
		<div { ...blockProps }>
			<BlockErrorBoundary
				header={ __(
					'Cart Block Error',
					'woo-gutenberg-products-block'
				) }
				text={ __(
					'There was an error whilst rendering the cart block. If this problem continues, try re-creating the block.',
					'woo-gutenberg-products-block'
				) }
				showErrorMessage={ true }
				errorMessagePrefix={ __(
					'Error message:',
					'woo-gutenberg-products-block'
				) }
			>
				<EditorProvider previewData={ { previewCart } }>
					<BlockSettings
						attributes={ attributes }
						setAttributes={ setAttributes }
					/>
					<BlockControls __experimentalShareWithChildBlocks>
						<ViewSwitcherComponent />
					</BlockControls>
					<CartBlockContext.Provider
						value={ {
							currentView,
						} }
					>
						<CartProvider>
							<div className={ cartClassName }>
								<InnerBlocks
									allowedBlocks={ ALLOWED_BLOCKS }
									template={ defaultInnerBlocksTemplate }
									templateLock="insert"
								/>
							</div>
						</CartProvider>
					</CartBlockContext.Provider>
				</EditorProvider>
			</BlockErrorBoundary>
			<CartCheckoutCompatibilityNotice blockName="cart" />
		</div>
	);
};

export const Save = (): JSX.Element => {
	return (
		<div
			{ ...useBlockProps.save( {
				className: 'wc-block-cart is-loading',
			} ) }
		>
			<InnerBlocks.Content />
		</div>
	);
};
