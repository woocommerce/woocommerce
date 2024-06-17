/**
 * External dependencies
 */
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import type { MainContentComponentProps } from '../../xstate';

export const CacheWarning = ( props: MainContentComponentProps ) => {
	return (
		<div
			className={ clsx(
				'launch-store-success-page__container',
				props.className
			) }
		>
			Invalid Cache
		</div>
	);
};
