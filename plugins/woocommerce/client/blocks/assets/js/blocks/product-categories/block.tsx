/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import { Icon, listView } from '@wordpress/icons';
import { isSiteEditorPage, isWidgetEditorPage } from '@woocommerce/utils';
import { useSelect } from '@wordpress/data';
import ProductCategoryControl from '@woocommerce/editor-components/product-category-control';
import type { ProductCategoryResponseItem } from '@woocommerce/types';
import {
	BaseControl,
	Disabled,
	PanelBody,
	ToggleControl,
	Placeholder,

	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,

	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
/**
 * Internal dependencies
 */
import type { ProductCategoriesBlockProps } from './types';

const EmptyPlaceholder = () => (
	<Placeholder
		icon={ <Icon icon={ listView } /> }
		label={ __( 'Product Categories List', 'woocommerce' ) }
		className="wc-block-product-categories"
	>
		{ __(
			'This block displays the product categories for your store. To use it you first need to create a product and assign it to a category.',
			'woocommerce'
		) }
	</Placeholder>
);

/**
 * Component displaying the categories as dropdown or list.
 *
 * @param {Object}            props               Incoming props for the component.
 * @param {Object}            props.attributes    Incoming block attributes.
 * @param {function(any):any} props.setAttributes Setter for block attributes.
 * @param {string}            props.name          Name for block.
 */
const ProductCategoriesBlock = ( {
	attributes,
	setAttributes,
	name,
}: ProductCategoriesBlockProps ) => {
	const editWidgetStore = useSelect(
		( select ) => select( 'core/edit-widgets' ),
		[]
	);
	const isSiteEditor = isSiteEditorPage();
	const isWidgetEditor = isWidgetEditorPage( editWidgetStore );
	const getInspectorControls = () => {
		const {
			hasCount,
			hasImage,
			hasEmpty,
			isDropdown,
			isHierarchical,
			parentCategoryId,
			showChildrenOnly,
		} = attributes;

		return (
			<InspectorControls key="inspector">
				<PanelBody
					title={ __( 'List Settings', 'woocommerce' ) }
					initialOpen
				>
					<ToggleGroupControl
						label={ __( 'Display style', 'woocommerce' ) }
						isBlock
						value={ isDropdown ? 'dropdown' : 'list' }
						onChange={ ( value: string ) =>
							setAttributes( {
								isDropdown: value === 'dropdown',
							} )
						}
					>
						<ToggleGroupControlOption
							value="list"
							label={ __( 'List', 'woocommerce' ) }
						/>
						<ToggleGroupControlOption
							value="dropdown"
							label={ __( 'Dropdown', 'woocommerce' ) }
						/>
					</ToggleGroupControl>
				</PanelBody>
				<PanelBody title={ __( 'Content', 'woocommerce' ) } initialOpen>
					<ToggleControl
						label={ __( 'Show product count', 'woocommerce' ) }
						checked={ hasCount }
						onChange={ () =>
							setAttributes( { hasCount: ! hasCount } )
						}
					/>
					{ ! isDropdown && (
						<ToggleControl
							label={ __(
								'Show category images',
								'woocommerce'
							) }
							help={
								hasImage
									? __(
											'Category images are visible.',
											'woocommerce'
									  )
									: __(
											'Category images are hidden.',
											'woocommerce'
									  )
							}
							checked={ hasImage }
							onChange={ () =>
								setAttributes( { hasImage: ! hasImage } )
							}
						/>
					) }
					<ToggleControl
						label={ __( 'Show hierarchy', 'woocommerce' ) }
						checked={ isHierarchical }
						onChange={ () =>
							setAttributes( {
								isHierarchical: ! isHierarchical,
							} )
						}
					/>
					<ToggleControl
						label={ __( 'Show empty categories', 'woocommerce' ) }
						checked={ hasEmpty }
						onChange={ () =>
							setAttributes( { hasEmpty: ! hasEmpty } )
						}
					/>
					<BaseControl
						label={ __( 'Parent category', 'woocommerce' ) }
						help={ __(
							'Show the children of a specific category on any page.',
							'woocommerce'
						) }
					>
						<ProductCategoryControl
							selected={
								parentCategoryId ? [ parentCategoryId ] : []
							}
							onChange={ (
								value: ProductCategoryResponseItem[] = []
							) => {
								const selectedParentCategoryId = value[ 0 ]
									? value[ 0 ].id
									: 0;

								setAttributes( {
									parentCategoryId: selectedParentCategoryId,
									showChildrenOnly: selectedParentCategoryId
										? false
										: showChildrenOnly,
								} );
							} }
							isCompact
							isSingle
						/>
					</BaseControl>
					{ ( isSiteEditor || isWidgetEditor ) && (
						<ToggleControl
							label={ __(
								'Only show children of current category',
								'woocommerce'
							) }
							help={ __(
								parentCategoryId
									? 'Clear the selected parent category to use the current product category instead.'
									: 'This will affect product category pages',
								'woocommerce'
							) }
							checked={ showChildrenOnly }
							disabled={ parentCategoryId > 0 }
							onChange={ () =>
								setAttributes( {
									parentCategoryId: 0,
									showChildrenOnly: ! showChildrenOnly,
								} )
							}
						/>
					) }
				</PanelBody>
			</InspectorControls>
		);
	};

	const blockProps = useBlockProps( {
		className: 'wc-block-product-categories',
	} );

	return (
		<div { ...blockProps }>
			{ getInspectorControls() }
			<Disabled>
				<ServerSideRender
					block={ name }
					attributes={ attributes }
					EmptyResponsePlaceholder={ EmptyPlaceholder }
				/>
			</Disabled>
		</div>
	);
};

export default ProductCategoriesBlock;
