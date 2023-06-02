/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import PropTypes from 'prop-types';
import { PanelBody, ToggleControl, Placeholder } from '@wordpress/components';
import { Icon, list } from '@woocommerce/icons';
import ToggleButtonControl from '@woocommerce/editor-components/toggle-button-control';

const EmptyPlaceholder = () => (
	<Placeholder
		icon={ <Icon srcElement={ list } /> }
		label={ __(
			'Product Categories List',
			'woocommerce'
		) }
		className="wc-block-product-categories"
	>
		{ __(
			"This block shows product categories for your store. To use it, you'll first need to create a product and assign it to a category.",
			'woocommerce'
		) }
	</Placeholder>
);

/**
 * Component displaying the categories as dropdown or list.
 *
 * @param {Object} props Incoming props for the component.
 * @param {Object} props.attributes Incoming block attributes.
 * @param {function(any):any} props.setAttributes Setter for block attributes.
 * @param {string} props.name Name for block.
 */
const ProductCategoriesBlock = ( { attributes, setAttributes, name } ) => {
	const getInspectorControls = () => {
		const {
			hasCount,
			hasImage,
			hasEmpty,
			isDropdown,
			isHierarchical,
		} = attributes;

		return (
			<InspectorControls key="inspector">
				<PanelBody
					title={ __(
						'List Settings',
						'woocommerce'
					) }
					initialOpen
				>
					<ToggleButtonControl
						label={ __(
							'Display style',
							'woocommerce'
						) }
						value={ isDropdown ? 'dropdown' : 'list' }
						options={ [
							{
								label: __(
									'List',
									'woocommerce'
								),
								value: 'list',
							},
							{
								label: __(
									'Dropdown',
									'woocommerce'
								),
								value: 'dropdown',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( {
								isDropdown: value === 'dropdown',
							} )
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Content', 'woocommerce' ) }
					initialOpen
				>
					<ToggleControl
						label={ __(
							'Show product count',
							'woocommerce'
						) }
						help={
							hasCount
								? __(
										'Product count is visible.',
										'woocommerce'
								  )
								: __(
										'Product count is hidden.',
										'woocommerce'
								  )
						}
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
						label={ __(
							'Show hierarchy',
							'woocommerce'
						) }
						help={
							isHierarchical
								? __(
										'Hierarchy is visible.',
										'woocommerce'
								  )
								: __(
										'Hierarchy is hidden.',
										'woocommerce'
								  )
						}
						checked={ isHierarchical }
						onChange={ () =>
							setAttributes( {
								isHierarchical: ! isHierarchical,
							} )
						}
					/>
					<ToggleControl
						label={ __(
							'Show empty categories',
							'woocommerce'
						) }
						help={
							hasEmpty
								? __(
										'Empty categories are visible.',
										'woocommerce'
								  )
								: __(
										'Empty categories are hidden.',
										'woocommerce'
								  )
						}
						checked={ hasEmpty }
						onChange={ () =>
							setAttributes( { hasEmpty: ! hasEmpty } )
						}
					/>
				</PanelBody>
			</InspectorControls>
		);
	};

	return (
		<>
			{ getInspectorControls() }
			<ServerSideRender
				block={ name }
				attributes={ attributes }
				EmptyResponsePlaceholder={ EmptyPlaceholder }
			/>
		</>
	);
};

ProductCategoriesBlock.propTypes = {
	/**
	 * The attributes for this block
	 */
	attributes: PropTypes.object.isRequired,
	/**
	 * The register block name.
	 */
	name: PropTypes.string.isRequired,
	/**
	 * A callback to update attributes
	 */
	setAttributes: PropTypes.func.isRequired,
};

export default ProductCategoriesBlock;
