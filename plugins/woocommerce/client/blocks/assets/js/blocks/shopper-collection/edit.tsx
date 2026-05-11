/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
} from '@wordpress/components';
import { Icon, trash } from '@wordpress/icons';
import { PLACEHOLDER_IMG_SRC } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { PREVIEW_ITEMS } from './preview-items';

const HEADER_TEMPLATE: [ string, Record< string, never > ][] = [
	[ 'woocommerce/shopper-collection-header', {} ],
];

interface ShopperCollectionAttributes {
	listName: string;
	columnCount: number;
}

interface EditProps {
	attributes: ShopperCollectionAttributes;
	setAttributes: ( attrs: Partial< ShopperCollectionAttributes > ) => void;
}

const DEFAULT_COLUMN_COUNT = 3;

const Edit = ( { attributes, setAttributes }: EditProps ): JSX.Element => {
	const { listName, columnCount } = attributes;
	const blockProps = useBlockProps( {
		className: 'wc-block-shopper-collection-wrapper',
		style: {
			'--wc-shopper-collection-columns': columnCount,
		} as React.CSSProperties,
	} );
	// Locked to all so the header stays as the only child. Items in the
	// list aren't editable blocks — they're rendered by iAPI from the
	// shopper-lists store — so the header is the only thing the admin
	// touches inside this block.
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'wc-block-shopper-collection-inner' },
		{
			template: HEADER_TEMPLATE,
			templateLock: 'all',
		}
	);

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
								label: __( 'Saved for Later', 'woocommerce' ),
								value: 'saved-for-later',
							},
						] }
						onChange={ ( value: string ) =>
							setAttributes( { listName: value } )
						}
					/>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Columns', 'woocommerce' ) }
						value={ columnCount }
						onChange={ ( value: number | undefined ) =>
							setAttributes( {
								columnCount: value ?? DEFAULT_COLUMN_COUNT,
							} )
						}
						min={ 1 }
						max={ 6 }
					/>
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div { ...innerBlocksProps } />
				<ul className="wc-block-shopper-collection">
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
								<button
									type="button"
									className="wc-block-shopper-collection-item__remove"
									aria-label={ sprintf(
										/* translators: %s: product name. */
										__(
											'Remove %s from Saved for later list',
											'woocommerce'
										),
										item.name
									) }
									disabled
								>
									<Icon icon={ trash } size={ 24 } />
								</button>
								{ item.variation && (
									<span className="wc-block-shopper-collection-item__variation">
										{ item.variation }
									</span>
								) }
							</div>
							<h2 className="wp-block-post-title has-text-align-center has-medium-font-size">
								<a
									href="#preview"
									onClick={ ( e ) => e.preventDefault() }
								>
									{ item.name }
								</a>
							</h2>
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
			</section>
		</>
	);
};

export default Edit;
