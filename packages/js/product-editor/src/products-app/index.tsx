// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-nocheck
/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import {
	UnsavedChangesWarning,
	privateApis as editorPrivateApis,
} from '@wordpress/editor';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
import useInitEditedEntityFromURL from '@wordpress/edit-site/build-module/components/sync-state-with-url/use-init-edited-entity-from-url';

/**
 * Internal dependencies
 */
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

export default function ProductsApp() {
	return (
		<GlobalStylesProvider>
			<UnsavedChangesWarning />
			<RouterProvider>
				<ProductsLayout />
			</RouterProvider>
		</GlobalStylesProvider>
	);
}
