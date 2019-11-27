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
					title={ __( 'Content', 'woocommerce' ) }
					initialOpen
				>
					<ToggleControl
						label={ __( 'Show product count', 'woocommerce' ) }
						help={
							hasCount ?
								__( 'Product count is visible.', 'woocommerce' ) :
								__( 'Product count is hidden.', 'woocommerce' )
						}
						checked={ hasCount }
						onChange={ () => setAttributes( { hasCount: ! hasCount } ) }
					/>
					<ToggleControl
						label={ __( 'Show hierarchy', 'woocommerce' ) }
						help={
							isHierarchical ?
								__( 'Hierarchy is visible.', 'woocommerce' ) :
								__( 'Hierarchy is hidden.', 'woocommerce' )
						}
						checked={ isHierarchical }
						onChange={ () => setAttributes( { isHierarchical: ! isHierarchical } ) }
					/>
					<ToggleControl
						label={ __( 'Show empty categories', 'woocommerce' ) }
						help={
							hasEmpty ?
								__( 'Empty categories are visible.', 'woocommerce' ) :
								__( 'Empty categories are hidden.', 'woocommerce' )
						}
						checked={ hasEmpty }
						onChange={ () => setAttributes( { hasEmpty: ! hasEmpty } ) }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'List Settings', 'woocommerce' ) }
					initialOpen
				>
					<ToggleButtonControl
						label={ __( 'Display style', 'woocommerce' ) }
						value={ isDropdown ? 'dropdown' : 'list' }
						options={ [
							{ label: __( 'List', 'woocommerce' ), value: 'list' },
							{ label: __( 'Dropdown', 'woocommerce' ), value: 'dropdown' },
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
					label={ __( 'Product Categories List', 'woocommerce' ) }
				>
					{ __( "This block shows product categories for your store. In order to preview this you'll first need to create a product and assign it to a category.", 'woocommerce' ) }
				</Placeholder>
			) }
		</Fragment>
	);
}
