/**
 * Internal dependencies
 */
import { Skeleton } from '../..';

interface MultiLineTextSkeletonProps {
	isStatic?: boolean;
}

export const MultiLineTextSkeleton = ( {
	isStatic = false,
}: MultiLineTextSkeletonProps ) => {
	return (
		<div className="wc-block-components-skeleton">
			<Skeleton height="16px" isStatic={ isStatic } />
			<Skeleton height="16px" isStatic={ isStatic } />
			<Skeleton height="16px" width="80%" isStatic={ isStatic } />
		</div>
	);
};
