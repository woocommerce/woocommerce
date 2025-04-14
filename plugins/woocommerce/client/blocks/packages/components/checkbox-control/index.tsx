/**
 * External dependencies
 */
import clsx from 'clsx';
import { useInstanceId } from '@wordpress/compose';
import type { ReactNode, InputHTMLAttributes } from 'react';
import { forwardRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';

export type CheckboxControlProps = {
	label?: string | React.ReactNode;
	onChange: ( value: boolean ) => void;
	children?: ReactNode | null | undefined;
	hasError?: boolean;
	errorId?: string;
	errorMessage?: string;
} & Pick<
	InputHTMLAttributes< HTMLInputElement >,
	| 'value'
	| 'name'
	| 'aria-label'
	| 'aria-describedby'
	| 'className'
	| 'id'
	| 'disabled'
	| 'checked'
>;

/**
 * Component used to show a checkbox control with styles.
 */
export const CheckboxControl = forwardRef<
	HTMLInputElement,
	CheckboxControlProps
>(
	(
		{
			className,
			label,
			id,
			onChange,
			children,
			hasError = false,
			checked = false,
			disabled = false,
			errorId,
			errorMessage,
			value,
			...rest
		}: CheckboxControlProps,
		forwardedRef
	): JSX.Element => {
		const instanceId = useInstanceId( CheckboxControl );
		const checkboxId = id || `checkbox-control-${ instanceId }`;

		return (
			<div
				className={ clsx(
					'wc-block-components-checkbox',
					{
						'has-error': hasError,
					},
					className
				) }
			>
				<label htmlFor={ checkboxId }>
					<input
						ref={ forwardedRef }
						id={ checkboxId }
						className="wc-block-components-checkbox__input"
						type="checkbox"
						onChange={ ( event ) =>
							onChange( event.target.checked )
						}
						aria-invalid={ hasError === true }
						checked={ checked }
						disabled={ disabled }
						value={ value }
						{ ...rest }
					/>
					<svg
						className="wc-block-components-checkbox__mark"
						aria-hidden="true"
						xmlns="http://www.w3.org/2000/svg"
						viewBox="0 0 24 20"
					>
						<path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z" />
					</svg>
					{ label && (
						<span className="wc-block-components-checkbox__label">
							{ label }
						</span>
					) }
					{ children }
				</label>
			</div>
		);
	}
);

export default CheckboxControl;
