/**
 * External dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { BlockEditProps } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { isSiteEditorPage } from '@woocommerce/utils';
import {
	Disabled,
	PanelBody,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore - Ignoring because `__experimentalToggleGroupControl` is not yet in the type definitions.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore - Ignoring because `__experimentalToggleGroupControl` is not yet in the type definitions.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

enum QuantitySelectorStyle {
	Input = 'input',
	Stepper = 'stepper',
}

interface Attributes {
	className?: string;
	quantitySelectorStyle: QuantitySelectorStyle;
}

const getHelpText = ( quantitySelectorStyle: QuantitySelectorStyle ) => {
	if ( quantitySelectorStyle === QuantitySelectorStyle.Input ) {
		return __(
			'Shoppers can enter a number of items to add to cart.',
			'woocommerce'
		);
	}
	if ( quantitySelectorStyle === QuantitySelectorStyle.Stepper ) {
		return __(
			'Shoppers can use buttons to change the number of items to add to cart.',
			'woocommerce'
		);
	}
};

const AddToCartWithOptionsQuantitySelectorEdit = (
	props: BlockEditProps< Attributes >
) => {
	const { setAttributes } = props;
	const { quantitySelectorStyle } = props.attributes;

	const quantitySelectorStyleClass =
		quantitySelectorStyle === QuantitySelectorStyle.Input
			? 'wc-block-add-to-cart-with-options__quantity-selector--input'
			: 'wc-block-add-to-cart-with-options__quantity-selector--stepper';

	const blockProps = useBlockProps( {
		className: `wc-block-add-to-cart-with-options__quantity-selector ${ quantitySelectorStyleClass }`,
	} );

	const isSiteEditor = useSelect(
		( select ) => isSiteEditorPage( select( 'core/edit-site' ) ),
		[]
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
					<ToggleGroupControl
						className="wc-block-editor-add-to-cart-with-options__quantity-selector"
						__nextHasNoMarginBottom
						value={ quantitySelectorStyle }
						isBlock
						onChange={ ( value: QuantitySelectorStyle ) => {
							setAttributes( {
								quantitySelectorStyle:
									value as QuantitySelectorStyle,
							} );
						} }
						help={ getHelpText( quantitySelectorStyle ) }
					>
						<ToggleGroupControlOption
							label={ __( 'Input', 'woocommerce' ) }
							value={ QuantitySelectorStyle.Input }
						/>
						<ToggleGroupControlOption
							label={ __( 'Stepper', 'woocommerce' ) }
							value={ QuantitySelectorStyle.Stepper }
						/>
					</ToggleGroupControl>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<Disabled>
					{ quantitySelectorStyle === QuantitySelectorStyle.Input && (
						<div className="quantity">
							<input
								style={
									// In the post editor, the editor isn't in an iframe, so WordPress styles are applied. We need to remove them.
									! isSiteEditor
										? {
												backgroundColor: '#ffffff',
												lineHeight: 'normal',
												minHeight: 'unset',
												boxSizing: 'unset',
												borderRadius: 'unset',
										  }
										: {}
								}
								type="number"
								value="1"
								className="input-text qty text"
								readOnly
							/>
						</div>
					) }
					{ quantitySelectorStyle ===
						QuantitySelectorStyle.Stepper && (
						<div className="quantity wc-block-components-quantity-selector">
							<button className="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus">
								-
							</button>
							<input
								style={
									// In the post editor, the editor isn't in an iframe, so WordPress styles are applied. We need to remove them.
									! isSiteEditor
										? {
												backgroundColor: '#ffffff',
												lineHeight: 'normal',
												minHeight: 'unset',
												boxSizing: 'unset',
												borderRadius: 'unset',
										  }
										: {}
								}
								type="number"
								value="1"
								className="input-text qty text"
								readOnly
							/>
							<button className="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--plus">
								+
							</button>
						</div>
					) }
				</Disabled>
			</div>
		</>
	);
};

export default AddToCartWithOptionsQuantitySelectorEdit;
