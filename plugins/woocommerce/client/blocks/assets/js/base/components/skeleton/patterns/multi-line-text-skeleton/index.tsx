/**
 * Internal dependencies
 */
import { Skeleton } from '../..';

export const MultiLineTextSkeleton = () => {
	return (
		<div className="wc-block-components-skeleton">
			<Skeleton height="16px" isStatic={ true } />
			<Skeleton height="16px" isStatic={ true } />
			<Skeleton height="16px" width="80%" isStatic={ true } />
		</div>
	);
};
