/**
 * External dependencies
 */
import { withInstanceId } from '@wordpress/compose';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';

export type CheckboxControlProps = {
	className?: string;
	label?: string;
	id?: string;
	instanceId: string;
	onChange: ( value: boolean ) => void;
	children: React.ReactChildren;
	hasError: boolean;
};

/**
 * Component used to show a checkbox control with styles.
 */
export const CheckboxControl = ( {
	className,
	label,
	id,
	instanceId,
	onChange,
	children,
	hasError = false,
	...rest
}: CheckboxControlProps ): JSX.Element => {
	const checkboxId = id || `checkbox-control-${ instanceId }`;

	return (
		<div
			className={ classNames(
				'wc-block-components-checkbox',
				{
					'has-error': hasError,
				},
				className
			) }
		>
			<label htmlFor={ checkboxId }>
				<input
					id={ checkboxId }
					className="wc-block-components-checkbox__input"
					type="checkbox"
					onChange={ ( event ) => onChange( event.target.checked ) }
					aria-invalid={ hasError === true }
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
};

export default withInstanceId( CheckboxControl );
