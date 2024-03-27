/**
 * External dependencies
 */
import { Button as AriakitButton } from '@ariakit/react';
import { forwardRef } from '@wordpress/element';
import { Button as WPButton } from 'wordpress-components';
import type { Button as WPButtonType } from '@wordpress/components';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';
import Spinner from '../../../../../packages/components/spinner';

export interface ButtonProps
	extends Omit< WPButtonType.ButtonProps, 'variant' | 'href' > {
	/**
	 * Show spinner
	 *
	 * @default false
	 */
	showSpinner?: boolean | undefined;
	/**
	 * Button variant
	 */
	variant?: 'text' | 'contained' | 'outlined';
	/**
	 * The URL the button should link to.
	 */
	href?: string | undefined;
}

export interface AnchorProps extends Omit< ButtonProps, 'href' > {
	/**
	 * Button href
	 */
	href?: string | undefined;
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
		'wp-element-button',
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

const ButtonV2 = forwardRef( ( props: ButtonProps, ref ) => {
	const {
		className,
		showSpinner = false,
		children,
		variant = 'contained',
		...rest
	} = props;
	const buttonClassName = classNames(
		'wc-block-components-button',
		'wp-element-button',
		className,
		variant,
		{
			'wc-block-components-button--loading': showSpinner,
		}
	);

	// if ( rest.href ) {
	// 	return (
	// 		<AriakitButton render= className={ buttonClassName } { ...rest }>
	// 			{ showSpinner && <Spinner /> }
	// 			<span className="wc-block-components-button__text">
	// 				{ children }
	// 			</span>
	// 		</AriakitButton>
	// 	);
	// }

	return (
		<AriakitButton ref={ ref } className={ buttonClassName } { ...rest }>
			{ showSpinner && <Spinner /> }
			<span className="wc-block-components-button__text">
				{ children }
			</span>
		</AriakitButton>
	);
} );

export default Button;
