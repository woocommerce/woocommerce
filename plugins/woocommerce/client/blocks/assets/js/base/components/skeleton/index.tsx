/**
 * External dependencies
 */
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import './style.scss';

export interface SkeletonProps {
	tag?: keyof JSX.IntrinsicElements;
	width?: string;
	height?: string;
	borderRadius?: string;
	className?: string;
	maxWidth?: string;
}

export const Skeleton = ( {
	tag: Tag = 'div',
	width = '100%',
	height = '8px',
	maxWidth = '',
	className = '',
	borderRadius = '',
}: SkeletonProps ): JSX.Element => {
	return (
		<Tag
			className={ clsx(
				'wc-block-components-skeleton__element',
				className
			) }
			aria-hidden="true"
			style={ {
				width,
				height,
				borderRadius,
				maxWidth,
			} }
		/>
	);
};
