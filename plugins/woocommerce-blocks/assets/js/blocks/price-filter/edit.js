/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { InspectorControls } from '@wordpress/editor';
import {
	Placeholder,
	Disabled,
	PanelBody,
	ToggleControl,
	Button,
} from '@wordpress/components';
import { PRODUCT_COUNT } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import Block from './block.js';
import './editor.scss';
import { IconMoney, IconExternal } from '../../components/icons';
import { ADMIN_URL } from '@woocommerce/settings';
import ToggleButtonControl from '../../components/toggle-button-control';

export default function( { attributes, setAttributes } ) {
	const getInspectorControls = () => {
		const { showInputFields, showFilterButton } = attributes;

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
								showInputFields: 'editable' === value,
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
										'Results will only update when the button is pressed.',
										'woo-gutenberg-products-block'
								  )
								: __(
										'Results will update when the slider is moved.',
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
				</PanelBody>
			</InspectorControls>
		);
	};

	const noProductsPlaceholder = () => (
		<Placeholder
			className="wc-block-price-slider"
			icon={ <IconMoney /> }
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
				className="wc-block-price-slider__add_product_button"
				isDefault
				isLarge
				href={ ADMIN_URL + 'post-new.php?post_type=product' }
			>
				{ __( 'Add new product', 'woo-gutenberg-products-block' ) +
					' ' }
				<IconExternal />
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
			{ 0 === PRODUCT_COUNT ? (
				noProductsPlaceholder()
			) : (
				<Fragment>
					{ getInspectorControls() }
					<Disabled>
						<Block attributes={ attributes } isPreview />
					</Disabled>
				</Fragment>
			) }
		</Fragment>
	);
}
