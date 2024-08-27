/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import useInitEditedEntityFromURL from '@wordpress/edit-site/build-module/components/sync-state-with-url/use-init-edited-entity-from-url';
import {
	UnsavedChangesWarning,
	// @ts-expect-error No types for this exist yet.
	privateApis as editorPrivateApis,
} from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { unlock } from '../lock-unlock';
import useLayoutAreas from './router';
import { Layout } from './layout';

const { RouterProvider } = unlock( routerPrivateApis );
const { GlobalStylesProvider } = unlock( editorPrivateApis );

function ProductsLayout() {
	// This ensures the edited entity id and type are initialized properly.
	useInitEditedEntityFromURL();
	const route = useLayoutAreas();
	return <Layout route={ route } />;
}

export function ProductsApp() {
	return (
		<GlobalStylesProvider>
			<UnsavedChangesWarning />
			<RouterProvider>
				<ProductsLayout />
			</RouterProvider>
		</GlobalStylesProvider>
	);
}
