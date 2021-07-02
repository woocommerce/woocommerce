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

interface ButtonProps extends WPButton.ButtonProps {
	className?: string;
	showSpinner?: boolean;
	children?: ReactNode;
}

/**
 * Component that visually renders a button but semantically might be `<button>` or `<a>` depending
 * on the props.
 */
const Button = ( {
	className,
	showSpinner = false,
	children,
	...props
}: ButtonProps ): JSX.Element => {
	const buttonClassName = classNames(
		'wc-block-components-button',
		className,
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
