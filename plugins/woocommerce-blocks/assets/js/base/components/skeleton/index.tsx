/**
 * Internal dependencies
 */
import './style.scss';

export interface SkeletonProps {
	numberOfLines?: number;
	tag?: keyof JSX.IntrinsicElements;
	maxWidth?: string;
}

export const Skeleton = ( {
	numberOfLines = 1,
	tag: Tag = 'div',
	maxWidth = '100%',
}: SkeletonProps ): JSX.Element => {
	const skeletonLines = Array.from(
		{ length: numberOfLines },
		( _: undefined, index ) => (
			<span
				className="wc-block-components-skeleton-text-line"
				aria-hidden="true"
				key={ index }
			/>
		)
	);
	return (
		<Tag
			className="wc-block-components-skeleton"
			style={ {
				maxWidth,
			} }
		>
			{ skeletonLines }
		</Tag>
	);
};
