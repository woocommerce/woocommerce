/**
 * Internal dependencies
 */
import { Skeleton } from '../..';

interface ProductShortDescriptionSkeletonProps {
	isStatic?: boolean;
}

export const ProductShortDescriptionSkeleton = ( {
	isStatic = false,
}: ProductShortDescriptionSkeletonProps ) => {
	return (
		<div className="wc-block-components-skeleton">
			<Skeleton height="16px" isStatic={ isStatic } />
			<Skeleton height="16px" isStatic={ isStatic } />
			<Skeleton height="16px" width="80%" isStatic={ isStatic } />
		</div>
	);
};
