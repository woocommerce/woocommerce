/**
 * External dependencies
 */
import clsx from 'clsx';
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
	const TagName = `h${ headingLevel }` as const;

	return (
		<TagName
			className={ clsx( 'wc-block-components-title', className ) }
			{ ...props }
		>
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
