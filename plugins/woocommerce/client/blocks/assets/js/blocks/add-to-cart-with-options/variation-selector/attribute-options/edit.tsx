/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { type BlockEditProps } from '@wordpress/blocks';
import {
	Disabled,
	PanelBody,
	SelectControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { useCustomDataContext } from '@woocommerce/shared-context';
import type { ProductResponseAttributeItem } from '@woocommerce/types';
import clsx from 'clsx';

interface Attributes {
	className?: string;
	style?: 'pills' | 'dropdown';
}

function Pills( {
	id,
	options,
}: {
	id: string;
	options: SelectControl.Option[];
} ) {
	return (
		<ul
			id={ id }
			className="wc-block-add-to-cart-with-options-variation-selector-attribute-options__pills"
		>
			{ options.map( ( option, index ) => (
				<li
					key={ option.value }
					className={ clsx(
						'wc-block-add-to-cart-with-options-variation-selector-attribute-options__pill',
						{
							'wc-block-add-to-cart-with-options-variation-selector-attribute-options__pill--selected':
								index === 0,
							'wc-block-add-to-cart-with-options-variation-selector-attribute-options__pill--disabled':
								option.disabled,
						}
					) }
				>
					{ option.label }
				</li>
			) ) }
		</ul>
	);
}

export default function AttributeOptionsEdit(
	props: BlockEditProps< Attributes >
) {
	const { attributes, setAttributes } = props;
	const { className, style } = attributes;

	const blockProps = useBlockProps( {
		className,
	} );

	/**
	 * This is a workaround for the Site Editor to set the correct
	 * background color of the selected pills of Variation Selector based on
	 * the main background color set by the theme.
	 */
	useEffect( () => {
		let editorStylesWrapper = document.querySelector(
			'.editor-styles-wrapper'
		);
		// If the editor styles wrapper is not available, look in the site editor canvas for it.
		if ( ! editorStylesWrapper ) {
			const canvasEl = document.querySelector(
				'.edit-site-visual-editor__editor-canvas'
			);

			if ( ! ( canvasEl instanceof HTMLIFrameElement ) ) {
				return;
			}
			const canvas =
				canvasEl.contentDocument || canvasEl.contentWindow?.document;
			if ( ! canvas ) {
				return;
			}
			editorStylesWrapper = canvas.querySelector(
				'.editor-styles-wrapper'
			);
		}

		if ( ! editorStylesWrapper ) {
			return;
		}

		const editorBackgroundColor =
			window.getComputedStyle( editorStylesWrapper )?.backgroundColor;
		const editorColor =
			window.getComputedStyle( editorStylesWrapper )?.color;

		if (
			editorStylesWrapper &&
			! editorStylesWrapper.querySelector(
				'#add-to-cart-with-options-variation-selector-selected-pill'
			) &&
			editorBackgroundColor &&
			editorColor
		) {
			const styleElement = document.createElement( 'style' );
			styleElement.id =
				'add-to-cart-with-options-variation-selector-selected-pill';
			styleElement.appendChild(
				document.createTextNode(
					`:where(.wc-block-add-to-cart-with-options-variation-selector-attribute-options__pill--selected) {
							background-color: ${ editorColor };
							color: ${ editorBackgroundColor };
							border-color: ${ editorColor };
						}`
				)
			);
			editorStylesWrapper.appendChild( styleElement );
		}
	}, [] );

	const { data: attribute } =
		useCustomDataContext< ProductResponseAttributeItem >( 'attribute' );

	if ( ! attribute ) return;

	const options = attribute.terms.map( ( term, index ) => ( {
		value: term.slug,
		label: term.name,
		disabled: index > 1 && index === attribute.terms.length - 1,
	} ) );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Style', 'woocommerce' ) }>
					<ToggleGroupControl
						value={ style }
						onChange={ ( option: 'pills' | 'dropdown' ) => {
							setAttributes( { style: option } );
						} }
						isBlock
						hideLabelFromVision
						size="__unstable-large"
					>
						<ToggleGroupControlOption
							value="pills"
							label={ __( 'Pills', 'woocommerce' ) }
						/>
						<ToggleGroupControlOption
							value="dropdown"
							label={ __( 'Dropdown', 'woocommerce' ) }
						/>
					</ToggleGroupControl>
				</PanelBody>
			</InspectorControls>

			<Disabled>
				{ style === 'pills' ? (
					<Pills id={ attribute.taxonomy } options={ options } />
				) : (
					<select
						id={ attribute.taxonomy }
						className="wc-block-add-to-cart-with-options-variation-selector-attribute-options__dropdown"
					>
						{ options.map( ( option ) => (
							<option key={ option.value } value={ option.value }>
								{ option.label }
							</option>
						) ) }
					</select>
				) }
			</Disabled>
		</div>
	);
}
