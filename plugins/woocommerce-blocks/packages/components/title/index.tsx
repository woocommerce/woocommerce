/**
 * External dependencies
 */
import classNames from 'classnames';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Component that renders a block title.
 */
const Title = ( {
	children,
	className = '',
	headingLevel,
	...props
}: TitleProps ): JSX.Element => {
	const buttonClassName = classNames(
		'wc-block-components-title',
		className
	);
	const TagName = `h${ headingLevel }` as keyof JSX.IntrinsicElements;

	return (
		<TagName className={ buttonClassName } { ...props }>
			{ children }
		</TagName>
	);
};

export interface TitleProps {
	headingLevel: '1' | '2' | '3' | '4' | '5' | '6';
	className?: string | undefined;
	children: ReactNode;
}

export default Title;
