/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from 'react';
import { InspectorControls, ServerSideRender } from '@wordpress/editor';
import PropTypes from 'prop-types';
import { PanelBody, ToggleControl, Placeholder } from '@wordpress/components';
import { IconFolder } from '@woocommerce/block-components/icons';
import ToggleButtonControl from '@woocommerce/block-components/toggle-button-control';

const EmptyPlaceHolder = () => (
	<Placeholder
		icon={ <IconFolder /> }
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
 */
const ProductCategoriesBlock = ( { attributes, setAttributes, name } ) => {
	const getInspectorControls = () => {
		const { hasCount, hasEmpty, isDropdown, isHierarchical } = attributes;

		return (
			<InspectorControls key="inspector">
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
			</InspectorControls>
		);
	};

	return (
		<Fragment>
			{ getInspectorControls() }
			<ServerSideRender
				block={ name }
				attributes={ attributes }
				EmptyResponsePlaceholder={ EmptyPlaceHolder }
			/>
		</Fragment>
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
