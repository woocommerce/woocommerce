/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { ToggleControl } from '@wordpress/components';
import { useWooBlockProps } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import { ToggleBlockAttributes } from './types';
import { sanitizeHTML } from '../../../utils/sanitize-html';
import { ProductEditorBlockEditProps } from '../../../types';
import useProductEntityProp from '../../../hooks/use-product-entity-prop';

export function Edit( {
	attributes,
	context: { postType },
}: ProductEditorBlockEditProps< ToggleBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const {
		label,
		property,
		disabled,
		disabledCopy,
		checkedValue,
		uncheckedValue,
	} = attributes;
	const [ value, setValue ] = useProductEntityProp< boolean >( property, {
		postType,
		fallbackValue: false,
	} );

	function isChecked() {
		if ( checkedValue !== undefined ) {
			return checkedValue === value;
		}
		return value as boolean;
	}

	function handleChange( checked: boolean ) {
		if ( checked ) {
			setValue( checkedValue !== undefined ? checkedValue : checked );
		} else {
			setValue( uncheckedValue !== undefined ? uncheckedValue : checked );
		}
	}

	return (
		<div { ...blockProps }>
			<ToggleControl
				label={ label }
				checked={ isChecked() }
				disabled={ disabled }
				onChange={ handleChange }
			/>
			{ disabled && (
				<p
					className="wp-block-woocommerce-product-toggle__disable-copy"
					dangerouslySetInnerHTML={ sanitizeHTML( disabledCopy ) }
				/>
			) }
		</div>
	);
}
