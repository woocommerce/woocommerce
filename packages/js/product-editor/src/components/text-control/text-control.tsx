/**
 * External dependencies
 */
import { createElement, createInterpolateElement } from '@wordpress/element';
import { useInstanceId } from '@wordpress/compose';
import classNames from 'classnames';
import { __ } from '@wordpress/i18n';
import { Icon, help as helpIcon } from '@wordpress/icons';
import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
	Tooltip,
} from '@wordpress/components';

export type TextProps = {
	value?: string;
	onChange: ( selected: string ) => void;
	label?: string;
	suffix?: string;
	help?: string;
	error?: string;
	placeholder?: string;
	required?: boolean;
	onBlur?: () => void;
	tooltip?: string;
};

export const TextControl: React.FC< TextProps > = ( {
	value,
	onChange,
	label,
	help,
	error,
	onBlur,
	placeholder,
	required,
	tooltip,
}: TextProps ) => {
	const textControlId = useInstanceId(
		BaseControl,
		'text-control'
	) as string;
	return (
		<BaseControl
			id={ textControlId }
			label={
				required
					? createInterpolateElement(
							`${ label } <required/> <tooltip/>`,
							{
								required: (
									<span className="woocommerce-product-form__required-input">
										{ /* translators: field 'required' indicator */ }
										{ __( '*', 'woocommerce' ) }
									</span>
								),
								tooltip: (
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
										<span>
											<Icon
												icon={ helpIcon }
												size={ 16 }
												fill="#949494"
											/>
										</span>
									</Tooltip>
								),
							}
					  )
					: label
			}
			className={ classNames( {
				'has-error': error,
			} ) }
			help={ error || help }
		>
			<InputControl
				id={ textControlId }
				placeholder={ placeholder }
				value={ value }
				onChange={ onChange }
				onBlur={ onBlur }
			></InputControl>
		</BaseControl>
	);
};
