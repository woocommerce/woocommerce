/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { useInstanceId } from '@wordpress/compose';
import classNames from 'classnames';
import { plus, reset } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import {
	BaseControl,
	Button,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useNumberInputProps } from '../../hooks/use-number-input-props';
import { Label } from '../label/label';

export type NumberProps = {
	value: string;
	onChange: ( selected: string ) => void;
	label: string;
	suffix?: string;
	help?: string;
	error?: string;
	placeholder?: string;
	onBlur?: () => void;
	required?: boolean;
	tooltip?: string;
	disabled?: boolean;
	step?: number;
	showStepButtons?: boolean;
};

export const NumberControl: React.FC< NumberProps > = ( {
	value,
	onChange,
	label,
	suffix,
	help,
	error,
	onBlur,
	required,
	tooltip,
	placeholder,
	disabled,
	step = 1,
	showStepButtons = false,
}: NumberProps ) => {
	const inputProps = useNumberInputProps( {
		value: value || '',
		onChange,
	} );

	const id = useInstanceId( BaseControl, 'product_number_field' ) as string;

	return (
		<BaseControl
			className={ classNames( {
				'has-error': error,
			} ) }
			id={ id }
			label={
				<Label
					label={ label }
					required={ required }
					tooltip={ tooltip }
				/>
			}
			help={ error || help }
		>
			<InputControl
				{ ...inputProps }
				step={ step }
				disabled={ disabled }
				id={ id }
				suffix={
					<>
						{ suffix }
						{ showStepButtons && (
							<>
								<Button
									icon={ plus }
									onClick={ () =>
										onChange(
											String(
												parseFloat( value || '0' ) +
													step
											)
										)
									}
									isSmall
									aria-hidden="true"
									aria-label={ __(
										'Increment',
										'woocommerce'
									) }
									tabIndex={ -1 }
								/>
								<Button
									icon={ reset }
									onClick={ () =>
										onChange(
											String(
												parseFloat( value || '0' ) -
													step
											)
										)
									}
									isSmall
									aria-hidden="true"
									aria-label={ __(
										'Decrement',
										'woocommerce'
									) }
									tabIndex={ -1 }
								/>
							</>
						) }
					</>
				}
				placeholder={ placeholder }
				onBlur={ onBlur }
			/>
		</BaseControl>
	);
};
