/**
 * External dependencies
 */
import classNames from 'classnames';
import { Button as AriakitButton } from '@ariakit/react';

/**
 * Internal dependencies
 */
import './style.scss';
import Spinner from '../../../../../packages/components/spinner';

// export interface ButtonProps
// 	extends Omit< WPButtonType.ButtonProps, 'variant' | 'href' > {
// 	/**
// 	 * Show spinner
// 	 *
// 	 * @default false
// 	 */
// 	showSpinner?: boolean | undefined;
// 	/**
// 	 * Button variant
// 	 */
// 	variant?: 'text' | 'contained' | 'outlined';
// 	/**
// 	 * The URL the button should link to.
// 	 */
// 	href?: string | undefined;
// }

// export interface AnchorProps extends Omit< ButtonProps, 'href' > {
// 	/**
// 	 * Button href
// 	 */
// 	href?: string | undefined;
// }

type ButtonProps = {
	className?: string;
	showSpinner?: boolean;
	children: React.ReactNode;
	variant?: 'text' | 'contained' | 'outlined';
} & React.ComponentProps< typeof AriakitButton >;

const Button = ( {
	className,
	showSpinner = false,
	children,
	variant = 'contained',
	...props
}: ButtonProps ) => {
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
		<AriakitButton className={ buttonClassName } { ...props }>
			{ showSpinner && <Spinner /> }
			<span className="wc-block-components-button__text">
				{ children }
			</span>
		</AriakitButton>
	);
};

export default Button;
