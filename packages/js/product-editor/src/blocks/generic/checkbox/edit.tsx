/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { CheckboxBlockAttributes } from './types';
import { Checkbox } from '../../../components/checkbox-control';

export function Edit( {
	attributes,
	context: { postType },
	setAttributes,
}: ProductEditorBlockEditProps< CheckboxBlockAttributes > ) {
	const {
		property = '',
		title = '',
		label = __( 'Label', 'woocommerce' ),
		tooltip,
		checkedValue = 'yes',
		uncheckedValue = 'no',
		disabled = false,
	} = attributes;

	const blockProps = useWooBlockProps( attributes );

	const [ value, setValue ] = useProductEntityProp< boolean | string | null >(
		property,
		{
			postType,
			fallbackValue: false,
		}
	);

	return (
		<div { ...blockProps }>
			<Checkbox
				value={ value || false }
				onChange={ setValue }
				label={ label || '' }
				title={ title }
				tooltip={ tooltip }
				checkedValue={ checkedValue }
				uncheckedValue={ uncheckedValue }
				disabled={ disabled }
			/>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
					<TextControl
						label={ __( 'Title', 'woocommerce' ) }
						value={ title }
						onChange={ ( newValue ) =>
							setAttributes( { title: newValue } )
						}
					/>
					<TextControl
						label={ __( 'Label', 'woocommerce' ) }
						value={ label }
						onChange={ ( newValue ) =>
							setAttributes( { label: newValue } )
						}
					/>
					<TextControl
						label={ __( 'Property', 'woocommerce' ) }
						value={ property }
						onChange={ ( newValue ) =>
							setAttributes( { property: newValue } )
						}
					/>
					<TextControl
						label={ __( 'Checked value', 'woocommerce' ) }
						value={ checkedValue || '' }
						onChange={ ( newValue ) =>
							setAttributes( { checkedValue: newValue } )
						}
					/>
					<TextControl
						label={ __( 'Checked value', 'woocommerce' ) }
						value={ uncheckedValue || '' }
						onChange={ ( newValue ) =>
							setAttributes( { uncheckedValue: newValue } )
						}
					/>
					<ToggleControl
						label={ __( 'Disabled', 'woocommerce' ) }
						checked={ disabled }
						onChange={ ( newValue ) =>
							setAttributes( { disabled: newValue } )
						}
					/>
				</PanelBody>
			</InspectorControls>
		</div>
	);
}
