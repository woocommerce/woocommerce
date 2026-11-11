/**
 * External dependencies
 */
import { useSkeletonPhase } from '@woocommerce/base-hooks';
import type { ReactNode } from 'react';

export interface DelayedContentWithSkeletonProps {
	/**
	 * The component to render when loading is complete
	 */
	children: ReactNode;

	/**
	 * Whether the content is currently loading
	 */
	isLoading: boolean;

	/**
	 * The skeleton component to show while loading
	 */
	skeleton: ReactNode;
}

/**
 * Shows a skeleton while content loads. All skeletons of one loading phase end together, after a short minimum time
 * that prevents a flash (Skeleton reveal rule, see `useSkeletonPhase`).
 */
export const DelayedContentWithSkeleton = ( {
	children,
	isLoading,
	skeleton,
}: DelayedContentWithSkeletonProps ): JSX.Element => {
	const showSkeleton = useSkeletonPhase( isLoading );

	return <>{ showSkeleton ? skeleton : children }</>;
};
