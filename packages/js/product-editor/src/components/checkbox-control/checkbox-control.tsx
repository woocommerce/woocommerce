/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { CheckboxControl } from '@wordpress/components';
import { __experimentalTooltip as Tooltip } from '@woocommerce/components';
import { Icon, help } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { sanitizeHTML } from '../../utils/sanitize-html';

export type CheckboxProps = {
	label: string;
	value: boolean | string | null;
	tooltip?: string;
	title?: string;
	onChange: ( selected: boolean | string | null ) => void;
	checkedValue?: string | null;
	uncheckedValue?: string | null;
	disabled?: boolean;
};

export const Checkbox: React.FC< CheckboxProps > = ( {
	value,
	label,
	onChange,
	tooltip,
	title,
	checkedValue,
	uncheckedValue,
	disabled,
}: CheckboxProps ) => {
	function isChecked() {
		if ( checkedValue !== undefined ) {
			return checkedValue === value;
		}
		return value as boolean;
	}

	function handleChange( checked: boolean ) {
		if ( checked ) {
			onChange( checkedValue !== undefined ? checkedValue : checked );
		} else {
			onChange( uncheckedValue !== undefined ? uncheckedValue : checked );
		}
	}
	return (
		<div className="woocommerce-product-form__checkbox">
			{ title && <h4>{ title }</h4> }
			<div className="woocommerce-product-form__checkbox-wrapper">
				<CheckboxControl
					label={ label }
					checked={ isChecked() }
					onChange={ handleChange }
					disabled={ disabled }
				/>
				{ tooltip && (
					<Tooltip
						text={
							<span
								dangerouslySetInnerHTML={ sanitizeHTML(
									tooltip
								) }
							></span>
						}
						position="top center"
						className={
							'woocommerce-product-form__checkbox-tooltip'
						}
					>
						<span className="woocommerce-product-form__checkbox-tooltip-icon">
							<Icon icon={ help } size={ 21.94 } fill="#949494" />
						</span>
					</Tooltip>
				) }
			</div>
		</div>
	);
};
