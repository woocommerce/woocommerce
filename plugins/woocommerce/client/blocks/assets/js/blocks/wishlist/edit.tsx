/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
import { Icon, trash } from '@wordpress/icons';
import { PLACEHOLDER_IMG_SRC } from '@woocommerce/settings';

interface WishlistAttributes {
	columnCount: number;
}

interface EditProps {
	attributes: WishlistAttributes;
	setAttributes: ( attrs: Partial< WishlistAttributes > ) => void;
}

const MIN_COLUMNS = 2;
const MAX_COLUMNS = 6;

// Lives in JS because `__()` is needed for the heading copy. `block.json`
// strings aren't run through translation, so keeping the template here
// is the only way to ship a localized default.
const TEMPLATE: [ string, Record< string, unknown > ][] = [
	[ 'core/heading', { content: __( 'Wishlist', 'woocommerce' ), level: 2 } ],
];

const PREVIEW_ITEMS = [
	{
		key: 'preview-1',
		name: __( 'Sample product one', 'woocommerce' ),
		variation: __( 'Size: M', 'woocommerce' ),
		price: '$19.99',
	},
	{
		key: 'preview-2',
		name: __( 'Sample product two', 'woocommerce' ),
		variation: __( 'Color: Blue', 'woocommerce' ),
		price: '$29.99',
	},
	{
		key: 'preview-3',
		name: __( 'Sample product three', 'woocommerce' ),
		variation: '',
		price: '$9.99',
	},
	{
		key: 'preview-4',
		name: __( 'Sample product four', 'woocommerce' ),
		variation: __( 'Size: L', 'woocommerce' ),
		price: '$24.99',
	},
	{
		key: 'preview-5',
		name: __( 'Sample product five', 'woocommerce' ),
		variation: '',
		price: '$14.99',
	},
	{
		key: 'preview-6',
		name: __( 'Sample product six', 'woocommerce' ),
		variation: __( 'Color: Red', 'woocommerce' ),
		price: '$39.99',
	},
];

const Edit = ( { attributes, setAttributes }: EditProps ): JSX.Element => {
	const { columnCount } = attributes;

	const blockProps = useBlockProps( {
		className: 'wc-block-wishlist',
	} );

	// `allowedBlocks` is read from block.json automatically — passing it
	// here would just duplicate the declaration. `templateLock: false`
	// is the default so we omit that too. The className matches the
	// `<div>` PHP wraps `$content` in on the frontend, so any CSS keyed
	// off `__header` applies in both contexts.
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'wc-block-wishlist__header' },
		{ template: TEMPLATE }
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
					<RangeControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Columns', 'woocommerce' ) }
						value={ columnCount }
						onChange={ ( value?: number ) => {
							if ( typeof value !== 'number' ) {
								return;
							}
							setAttributes( { columnCount: value } );
						} }
						min={ MIN_COLUMNS }
						max={ MAX_COLUMNS }
					/>
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				<div { ...innerBlocksProps } />
				<ul
					className={ `wc-block-wishlist__list columns-${ columnCount }` }
				>
					{ PREVIEW_ITEMS.map( ( item ) => (
						<li
							key={ item.key }
							className="wc-block-shopper-list-item"
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
									className="wc-block-shopper-list-item__remove"
									aria-label={ sprintf(
										/* translators: %s: product name. */
										__(
											'Remove %s from wishlist',
											'woocommerce'
										),
										item.name
									) }
									disabled
								>
									<Icon icon={ trash } size={ 24 } />
								</button>
								{ item.variation && (
									<span className="wc-block-shopper-list-item__variation">
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
							<div className="wp-block-button wc-block-components-product-button">
								<button
									type="button"
									className="wp-block-button__link wp-element-button add_to_cart_button wc-block-components-product-button__button"
									disabled
								>
									{ __( 'Add to cart', 'woocommerce' ) }
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
