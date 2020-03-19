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
import HeadingToolbar from '@woocommerce/block-components/heading-toolbar';
import BlockTitle from '@woocommerce/block-components/block-title';

/**
 * Internal dependencies
 */
import Block from './block.js';
import './editor.scss';
import { IconMoney, IconExternal } from '../../components/icons';
import ToggleButtonControl from '../../components/toggle-button-control';

export default function( { attributes, setAttributes } ) {
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
										'Results will only update when the button is pressed.',
										'woocommerce'
								  )
								: __(
										'Results will update when the slider is moved.',
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
			icon={ <IconMoney /> }
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
				className="wc-block-price-slider__add_product_button"
				isDefault
				isLarge
				href={ getAdminLink( 'post-new.php?post_type=product' ) }
			>
				{ __( 'Add new product', 'woocommerce' ) +
					' ' }
				<IconExternal />
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
