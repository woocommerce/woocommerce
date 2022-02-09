/**
 * External dependencies
 */
import { Button as WPButton } from 'wordpress-components';
import type { ReactNode } from 'react';
import classNames from 'classnames';
import Spinner from '@woocommerce/base-components/spinner';

/**
 * Internal dependencies
 */
import './style.scss';

export interface ButtonProps extends WPButton.ButtonProps {
	/**
	 * Component wrapper classname
	 *
	 * @default 'wc-block-components-button'
	 */
	className?: string;
	/**
	 * Show spinner
	 *
	 * @default false
	 */
	showSpinner?: boolean;
	/**
	 * Button content
	 */
	children?: ReactNode;
	/**
	 * Button state
	 */
	disabled?: boolean;
	/**
	 * Event handler triggered when the button is clicked
	 */
	onClick?: ( e: React.MouseEvent< HTMLButtonElement, MouseEvent > ) => void;
	/**
	 * Button type
	 */
	type?: 'button' | 'input' | 'submit';
	/**
	 * Button variant
	 */
	variant?: 'text' | 'contained' | 'outlined';
}

/**
 * Component that visually renders a button but semantically might be `<button>` or `<a>` depending
 * on the props.
 */
const Button = ( {
	className,
	showSpinner = false,
	children,
	variant = 'contained',
	...props
}: ButtonProps ): JSX.Element => {
	const buttonClassName = classNames(
		'wc-block-components-button',
		className,
		variant,
		{
			'wc-block-components-button--loading': showSpinner,
		}
	);

	return (
		<WPButton className={ buttonClassName } { ...props }>
			{ showSpinner && <Spinner /> }
			<span className="wc-block-components-button__text">
				{ children }
			</span>
		</WPButton>
	);
};

export default Button;
