/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

interface CartButtonAttributes {
	text: string;
}

interface EditProps {
	attributes: CartButtonAttributes;
	setAttributes: ( attrs: Partial< CartButtonAttributes > ) => void;
}

const DEFAULT_BUTTON_TEXT = (): string => __( 'Move to cart', 'woocommerce' );

const Edit = ( { attributes, setAttributes }: EditProps ): JSX.Element => {
	const { text } = attributes;
	const blockProps = useBlockProps( {
		className: clsx(
			'wp-block-button',
			'wc-block-components-product-button',
			'wc-block-shopper-collection-cart-button'
		),
	} );

	const buttonText = text || DEFAULT_BUTTON_TEXT();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Button settings', 'woocommerce' ) }>
					<TextControl
						label={ __( 'Button text', 'woocommerce' ) }
						help={ __(
							'Leave empty to use the default label.',
							'woocommerce'
						) }
						value={ text || '' }
						onChange={ ( value: string ) =>
							setAttributes( { text: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<button
					type="button"
					className={ clsx(
						'wp-block-button__link',
						'wp-element-button',
						'wc-block-components-product-button__button'
					) }
					disabled
				>
					{ buttonText }
				</button>
			</div>
		</>
	);
};

export default Edit;
