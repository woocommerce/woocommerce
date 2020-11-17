/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import {
	Placeholder,
	Disabled,
	PanelBody,
	ToggleControl,
	Button,
} from '@wordpress/components';
import { PRODUCT_COUNT } from '@woocommerce/block-settings';
import { getAdminLink } from '@woocommerce/settings';
import HeadingToolbar from '@woocommerce/editor-components/heading-toolbar';
import BlockTitle from '@woocommerce/editor-components/block-title';
import ToggleButtonControl from '@woocommerce/editor-components/toggle-button-control';
import { Icon, bill, external } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import Block from './block.js';
import './editor.scss';

export default function ( { attributes, setAttributes } ) {
	const {
		className,
		heading,
		headingLevel,
		showInputFields,
		showFilterButton,
	} = attributes;

	const getInspectorControls = () => {
		return (
			<InspectorControls key="inspector">
				<PanelBody
					title={ __(
						'Block Settings',
						'woo-gutenberg-products-block'
					) }
				>
					<ToggleButtonControl
						label={ __(
							'Price Range',
							'woo-gutenberg-products-block'
						) }
						value={ showInputFields ? 'editable' : 'text' }
						options={ [
							{
								label: __(
									'Editable',
									'woo-gutenberg-products-block'
								),
								value: 'editable',
							},
							{
								label: __(
									'Text',
									'woo-gutenberg-products-block'
								),
								value: 'text',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( {
								showInputFields: value === 'editable',
							} )
						}
					/>
					<ToggleControl
						label={ __(
							'Filter button',
							'woo-gutenberg-products-block'
						) }
						help={
							showFilterButton
								? __(
										'Products will only update when the button is pressed.',
										'woo-gutenberg-products-block'
								  )
								: __(
										'Products will update when the slider is moved.',
										'woo-gutenberg-products-block'
								  )
						}
						checked={ showFilterButton }
						onChange={ () =>
							setAttributes( {
								showFilterButton: ! showFilterButton,
							} )
						}
					/>
					<p>
						{ __(
							'Heading Level',
							'woo-gutenberg-products-block'
						) }
					</p>
					<HeadingToolbar
						isCollapsed={ false }
						minLevel={ 2 }
						maxLevel={ 7 }
						selectedLevel={ headingLevel }
						onChange={ ( newLevel ) =>
							setAttributes( { headingLevel: newLevel } )
						}
					/>
				</PanelBody>
			</InspectorControls>
		);
	};

	const noProductsPlaceholder = () => (
		<Placeholder
			className="wc-block-price-slider"
			icon={ <Icon srcElement={ bill } /> }
			label={ __(
				'Filter Products by Price',
				'woo-gutenberg-products-block'
			) }
			instructions={ __(
				'Display a slider to filter products in your store by price.',
				'woo-gutenberg-products-block'
			) }
		>
			<p>
				{ __(
					"Products with prices are needed for filtering by price. You haven't created any products yet.",
					'woo-gutenberg-products-block'
				) }
			</p>
			<Button
				className="wc-block-price-slider__add-product-button"
				isDefault
				href={ getAdminLink( 'post-new.php?post_type=product' ) }
			>
				{ __( 'Add new product', 'woo-gutenberg-products-block' ) +
					' ' }
				<Icon srcElement={ external } />
			</Button>
			<Button
				className="wc-block-price-slider__read_more_button"
				isTertiary
				href="https://docs.woocommerce.com/document/managing-products/"
			>
				{ __( 'Learn more', 'woo-gutenberg-products-block' ) }
			</Button>
		</Placeholder>
	);

	return (
		<Fragment>
			{ PRODUCT_COUNT === 0 ? (
				noProductsPlaceholder()
			) : (
				<div className={ className }>
					{ getInspectorControls() }
					<BlockTitle
						headingLevel={ headingLevel }
						heading={ heading }
						onChange={ ( value ) =>
							setAttributes( { heading: value } )
						}
					/>
					<Disabled>
						<Block attributes={ attributes } isEditor={ true } />
					</Disabled>
				</div>
			) }
		</Fragment>
	);
}
