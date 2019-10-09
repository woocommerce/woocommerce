/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { InspectorControls } from '@wordpress/editor';
import { PanelBody, ToggleControl, Placeholder } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './editor.scss';
import Block from './block.js';
import ToggleButtonControl from '../../components/toggle-button-control';
import getCategories from './get-categories';
import { IconFolder } from '../../components/icons';

export default function( { attributes, setAttributes } ) {
	const { hasCount, hasEmpty, isDropdown, isHierarchical } = attributes;
	const categories = getCategories( attributes );

	return (
		<Fragment>
			<InspectorControls key="inspector">
				<PanelBody
					title={ __( 'Content', 'woo-gutenberg-products-block' ) }
					initialOpen
				>
					<ToggleControl
						label={ __( 'Show product count', 'woo-gutenberg-products-block' ) }
						help={
							hasCount ?
								__( 'Product count is visible.', 'woo-gutenberg-products-block' ) :
								__( 'Product count is hidden.', 'woo-gutenberg-products-block' )
						}
						checked={ hasCount }
						onChange={ () => setAttributes( { hasCount: ! hasCount } ) }
					/>
					<ToggleControl
						label={ __( 'Show hierarchy', 'woo-gutenberg-products-block' ) }
						help={
							isHierarchical ?
								__( 'Hierarchy is visible.', 'woo-gutenberg-products-block' ) :
								__( 'Hierarchy is hidden.', 'woo-gutenberg-products-block' )
						}
						checked={ isHierarchical }
						onChange={ () => setAttributes( { isHierarchical: ! isHierarchical } ) }
					/>
					<ToggleControl
						label={ __( 'Show empty categories', 'woo-gutenberg-products-block' ) }
						help={
							hasEmpty ?
								__( 'Empty categories are visible.', 'woo-gutenberg-products-block' ) :
								__( 'Empty categories are hidden.', 'woo-gutenberg-products-block' )
						}
						checked={ hasEmpty }
						onChange={ () => setAttributes( { hasEmpty: ! hasEmpty } ) }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'List Settings', 'woo-gutenberg-products-block' ) }
					initialOpen
				>
					<ToggleButtonControl
						label={ __( 'Display style', 'woo-gutenberg-products-block' ) }
						value={ isDropdown ? 'dropdown' : 'list' }
						options={ [
							{ label: __( 'List', 'woo-gutenberg-products-block' ), value: 'list' },
							{ label: __( 'Dropdown', 'woo-gutenberg-products-block' ), value: 'dropdown' },
						] }
						onChange={ ( value ) => setAttributes( { isDropdown: 'dropdown' === value } ) }
					/>
				</PanelBody>
			</InspectorControls>
			{ categories.length > 0 ? (
				<Block attributes={ attributes } categories={ categories } isPreview />
			) : (
				<Placeholder
					className="wc-block-product-categories"
					icon={ <IconFolder /> }
					label={ __( 'Product Categories List', 'woo-gutenberg-products-block' ) }
				>
					{ __( "This block shows product categories for your store. In order to preview this you'll first need to create a product and assign it to a category.", 'woo-gutenberg-products-block' ) }
				</Placeholder>
			) }
		</Fragment>
	);
}
