/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { InspectorControls } from '@wordpress/editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './editor.scss';
import Block from './block.js';

export default function( { attributes, setAttributes } ) {
	const { hasCount, hasEmpty, isDropdown, isHierarchical } = attributes;

	return (
		<Fragment>
			<InspectorControls key="inspector">
				<PanelBody
					title={ __( 'Content', 'woo-gutenberg-products-block' ) }
					initialOpen
				>
					<ToggleControl
						label={ __( 'Show as dropdown', 'woo-gutenberg-products-block' ) }
						help={
							isDropdown ?
								__( 'Categories are shown in a dropdown.', 'woo-gutenberg-products-block' ) :
								__( 'Categories are shown in a list.', 'woo-gutenberg-products-block' )
						}
						checked={ isDropdown }
						onChange={ () => setAttributes( { isDropdown: ! isDropdown } ) }
					/>
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
					{ ! isDropdown && (
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
					) }
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
			</InspectorControls>
			<Block attributes={ attributes } />
		</Fragment>
	);
}
