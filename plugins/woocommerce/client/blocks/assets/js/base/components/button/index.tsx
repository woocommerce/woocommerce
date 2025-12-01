/**
 * External dependencies
 */
import { Button as AriakitButton } from '@ariakit/react';
import { forwardRef } from '@wordpress/element';
import clsx from 'clsx';
import type { ForwardedRef } from 'react';
import type { ButtonProps as AriakitButtonProps } from '@ariakit/react';

/**
 * Internal dependencies
 */
import './style.scss';

type WCButtonProps = AriakitButtonProps & { children?: React.ReactNode };

export interface ButtonProps extends WCButtonProps {
	/**
	 * Button variant
	 *
	 * @default 'contained'
	 */
	variant?: 'text' | 'contained' | 'outlined';
	/**
	 * By default we render a wrapper around  the button children,
	 * but you can opt in to removing it by setting removeTextWrap
	 * to true.
	 *
	 * @default false
	 */
	removeTextWrap?: boolean;
}

interface LinkProps extends ButtonProps {
	/**
	 * Button href
	 */
	href: string;
}

/**
 * Component that visually renders a button but semantically might be `<button>` or `<a>` depending
 * on the props.
 */
const Button = forwardRef< HTMLButtonElement, ButtonProps | LinkProps >(
	( props, ref ) => {
		const {
			className,
			children,
			variant = 'contained',
			// To maintain backward compat we render a wrapper for button text by default,
			// but you can opt in to removing it by setting removeTextWrap to true.
			removeTextWrap = false,
			...rest
		} = props;

		const buttonClassName = clsx(
			'wc-block-components-button',
			'wp-element-button',
			className,
			variant
		);

		if ( 'href' in props ) {
			return (
				<AriakitButton
					render={
						<a
							ref={ ref as ForwardedRef< HTMLAnchorElement > }
							href={ props.href }
						>
							<div className="wc-block-components-button__text">
								{ children }
							</div>
						</a>
					}
					className={ buttonClassName }
					{ ...rest }
				/>
			);
		}

		const buttonChildren = removeTextWrap ? (
			props.children
		) : (
			<div className="wc-block-components-button__text">
				{ props.children }
			</div>
		);

		return (
			<AriakitButton
				ref={ ref }
				className={ buttonClassName }
				{ ...rest }
			>
				{ buttonChildren }
			</AriakitButton>
		);
	}
);

export default Button;
