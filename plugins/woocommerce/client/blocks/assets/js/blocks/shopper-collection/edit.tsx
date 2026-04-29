/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { PLACEHOLDER_IMG_SRC } from '@woocommerce/settings';

interface ShopperCollectionAttributes {
	listName: string;
	layout?: {
		type?: string;
		columnCount?: number;
	};
}

interface EditProps {
	attributes: ShopperCollectionAttributes;
	setAttributes: ( attrs: Partial< ShopperCollectionAttributes > ) => void;
}

const DEFAULT_COLUMN_COUNT = 3;

const PREVIEW_ITEMS = [
	{
		key: 'preview-1',
		name: __( 'Sample product one', 'woocommerce' ),
		variation: __( 'Size: M', 'woocommerce' ),
		price: '$19.99',
		quantity: __( 'Qty: 2', 'woocommerce' ),
	},
	{
		key: 'preview-2',
		name: __( 'Sample product two', 'woocommerce' ),
		variation: __( 'Color: Blue', 'woocommerce' ),
		price: '$29.99',
		quantity: __( 'Qty: 1', 'woocommerce' ),
	},
	{
		key: 'preview-3',
		name: __( 'Sample product three', 'woocommerce' ),
		variation: '',
		price: '$9.99',
		quantity: __( 'Qty: 3', 'woocommerce' ),
	},
	{
		key: 'preview-4',
		name: __( 'Sample product four', 'woocommerce' ),
		variation: __( 'Size: L', 'woocommerce' ),
		price: '$24.99',
		quantity: __( 'Qty: 1', 'woocommerce' ),
	},
	{
		key: 'preview-5',
		name: __( 'Sample product five', 'woocommerce' ),
		variation: '',
		price: '$14.99',
		quantity: __( 'Qty: 2', 'woocommerce' ),
	},
	{
		key: 'preview-6',
		name: __( 'Sample product six', 'woocommerce' ),
		variation: __( 'Color: Red', 'woocommerce' ),
		price: '$39.99',
		quantity: __( 'Qty: 1', 'woocommerce' ),
	},
];

const Edit = ( { attributes, setAttributes }: EditProps ): JSX.Element => {
	const { listName, layout } = attributes;
	const columnCount =
		typeof layout?.columnCount === 'number'
			? layout.columnCount
			: DEFAULT_COLUMN_COUNT;
	const blockProps = useBlockProps( {
		className: 'wc-block-shopper-collection',
		style: {
			'--wc-shopper-collection-columns': columnCount,
		} as React.CSSProperties,
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Collection settings', 'woocommerce' ) }>
					<SelectControl
						label={ __( 'List', 'woocommerce' ) }
						help={ __(
							'Choose which shopper list this block displays.',
							'woocommerce'
						) }
						value={ listName }
						options={ [
							{
								label: __( 'Save for Later', 'woocommerce' ),
								value: 'save-for-later',
							},
						] }
						onChange={ ( value: string ) =>
							setAttributes( { listName: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<ul { ...blockProps }>
				{ PREVIEW_ITEMS.map( ( item ) => (
					<li
						key={ item.key }
						className="wc-block-shopper-collection-item"
					>
						<div className="wc-block-components-product-image wc-block-components-product-image--aspect-ratio-auto">
							<a
								href="#preview"
								onClick={ ( e ) => e.preventDefault() }
							>
								<img src={ PLACEHOLDER_IMG_SRC } alt="" />
							</a>
							<div className="wc-block-components-product-image__inner-container">
								<button
									type="button"
									className="wc-block-shopper-collection-item__remove"
									disabled
								>
									{ __( 'Remove', 'woocommerce' ) }
								</button>
							</div>
						</div>
						<h2 className="wp-block-post-title has-text-align-center has-medium-font-size">
							<a
								href="#preview"
								onClick={ ( e ) => e.preventDefault() }
							>
								{ item.name }
							</a>
						</h2>
						{ item.variation && (
							<span className="wc-block-shopper-collection-item__variation">
								{ item.variation }
							</span>
						) }
						<div className="price wc-block-components-product-price has-text-align-center has-small-font-size">
							<span className="wc-block-components-product-price__value">
								{ item.price }
							</span>
						</div>
						<span className="wc-block-shopper-collection-item__quantity">
							{ item.quantity }
						</span>
						<div className="wp-block-button wc-block-components-product-button">
							<button
								type="button"
								className="wp-block-button__link wp-element-button add_to_cart_button wc-block-components-product-button__button"
								disabled
							>
								{ __( 'Move to cart', 'woocommerce' ) }
							</button>
						</div>
					</li>
				) ) }
			</ul>
		</>
	);
};

export default Edit;
