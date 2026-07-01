/**
 * External dependencies
 */
import { lazy, Suspense } from '@wordpress/element';
import type { ComponentProps, ComponentType } from 'react';

export const lazyEdit = < T extends ComponentType< object > >(
	loadComponent: () => Promise< { default: T } >
) => {
	const LazyComponent = lazy( loadComponent );

	return ( props: ComponentProps< T > ) => (
		<Suspense fallback={ null }>
			<LazyComponent { ...props } />
		</Suspense>
	);
};
