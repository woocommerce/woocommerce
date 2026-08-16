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
	isStatic?: boolean;
	ariaMessage?: string;
}

export const Skeleton = ( {
	tag: Tag = 'div',
	width = '100%',
	height = '8px',
	maxWidth = '',
	className = '',
	borderRadius = '',
	isStatic = false,
	ariaMessage,
}: SkeletonProps ): JSX.Element => {
	return (
		<Tag
			className={ clsx(
				'wc-block-components-skeleton__element',
				{
					'wc-block-components-skeleton__element--static': isStatic,
				},
				className
			) }
			{ ...( ariaMessage
				? {
						'aria-live': 'polite',
						'aria-label': ariaMessage,
				  }
				: {
						'aria-hidden': 'true',
				  } ) }
			style={ {
				width,
				height,
				borderRadius,
				maxWidth,
			} }
		/>
	);
};
