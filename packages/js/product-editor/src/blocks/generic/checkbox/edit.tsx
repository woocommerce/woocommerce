/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { CheckboxControl, Tooltip } from '@wordpress/components';
import { Icon, help } from '@wordpress/icons';
import { useWooBlockProps } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { CheckboxBlockAttributes } from './types';

export function Edit( {
	attributes,
	context: { postType },
}: ProductEditorBlockEditProps< CheckboxBlockAttributes > ) {
	const { property, title, label, tooltip, checkedValue, uncheckedValue } =
		attributes;

	const blockProps = useWooBlockProps( {
		className: 'woocommerce-product-form__checkbox',
		...attributes,
	} );

	const [ value, setValue ] = useProductEntityProp< boolean | string | null >(
		property,
		{
			postType,
			fallbackValue: false,
		}
	);

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
			{ title && <h4>{ title }</h4> }
			<div className="woocommerce-product-form__checkbox-wrapper">
				<CheckboxControl
					label={ label }
					checked={ isChecked() }
					onChange={ handleChange }
				/>
				{ tooltip && (
					<Tooltip
						text={ <span>{ tooltip }</span> }
						position="top center"
						// eslint-disable-next-line @typescript-eslint/ban-ts-comment
						// @ts-ignore Incorrect types.
						className={
							'woocommerce-product-form__checkbox-tooltip'
						}
						delay={ 0 }
					>
						<span className="woocommerce-product-form__checkbox-tooltip-icon">
							<Icon icon={ help } size={ 21.94 } fill="#949494" />
						</span>
					</Tooltip>
				) }
			</div>
		</div>
	);
}
