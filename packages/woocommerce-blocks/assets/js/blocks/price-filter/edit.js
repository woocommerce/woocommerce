/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import {
	Placeholder,
	Disabled,
	PanelBody,
	ToggleControl,
	Button,
} from '@wordpress/components';
import { getAdminLink } from '@woocommerce/settings';
import { blocksConfig } from '@woocommerce/block-settings';
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
						'woocommerce'
					) }
				>
					<ToggleButtonControl
						label={ __(
							'Price Range',
							'woocommerce'
						) }
						value={ showInputFields ? 'editable' : 'text' }
						options={ [
							{
								label: __(
									'Editable',
									'woocommerce'
								),
								value: 'editable',
							},
							{
								label: __(
									'Text',
									'woocommerce'
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
							'woocommerce'
						) }
						help={
							showFilterButton
								? __(
										'Products will only update when the button is pressed.',
										'woocommerce'
								  )
								: __(
										'Products will update when the slider is moved.',
										'woocommerce'
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
							'woocommerce'
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
				'woocommerce'
			) }
			instructions={ __(
				'Display a slider to filter products in your store by price.',
				'woocommerce'
			) }
		>
			<p>
				{ __(
					"Products with prices are needed for filtering by price. You haven't created any products yet.",
					'woocommerce'
				) }
			</p>
			<Button
				className="wc-block-price-slider__add-product-button"
				isSecondary
				href={ getAdminLink( 'post-new.php?post_type=product' ) }
			>
				{ __( 'Add new product', 'woocommerce' ) +
					' ' }
				<Icon srcElement={ external } />
			</Button>
			<Button
				className="wc-block-price-slider__read_more_button"
				isTertiary
				href="https://docs.woocommerce.com/document/managing-products/"
			>
				{ __( 'Learn more', 'woocommerce' ) }
			</Button>
		</Placeholder>
	);

	return (
		<>
			{ blocksConfig.productCount === 0 ? (
				noProductsPlaceholder()
			) : (
				<div className={ className }>
					{ getInspectorControls() }
					<BlockTitle
						className="wc-block-price-filter__title"
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
		</>
	);
}
