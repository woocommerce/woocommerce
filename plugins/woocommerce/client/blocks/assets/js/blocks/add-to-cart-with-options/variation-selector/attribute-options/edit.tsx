/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { type BlockEditProps } from '@wordpress/blocks';
import { useCustomDataContext } from '@woocommerce/shared-context';
import type { ProductResponseAttributeItem } from '@woocommerce/types';
import clsx from 'clsx';
import {
	Disabled,
	PanelBody,
	/* eslint-disable */
	/* @ts-ignore module is exported as experimental */
	__experimentalToggleGroupControl as ToggleGroupControl,
	/* @ts-ignore module is exported as experimental */
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	/* eslint-enable */
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useThemeColors } from '../../../../shared/hooks/use-theme-colors';

interface Attributes {
	className?: string;
	style?: 'pills' | 'dropdown';
}

function Pills( {
	id,
	options,
}: {
	id: string;
	options: {
		value: string;
		label: string;
		disabled: boolean;
	}[];
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

	// Apply selected variation pill styles based on Site Editor's background and text colors.
	useThemeColors(
		'add-to-cart-with-options-variation-selector-attribute-options',
		( { editorBackgroundColor, editorColor } ) => `
			:where(.wc-block-add-to-cart-with-options-variation-selector-attribute-options__pill--selected) {
				--pill-color: ${ editorBackgroundColor };
				--pill-background-color: ${ editorColor };
			}
		`
	);

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
