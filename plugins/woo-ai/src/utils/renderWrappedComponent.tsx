/**
 * External dependencies
 */
import * as elementExports from '@wordpress/element';
import { QueryClient, QueryClientProvider } from 'react-query';

const queryClient = new QueryClient();

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore Incorrect type definition for createRoot
const { createRoot, render } = elementExports;

export const renderWrappedComponent = (
	Component: React.ComponentType,
	rootElement: HTMLElement | null
) => {
	if ( ! rootElement ) {
		return;
	}

	const WrappedComponent = () => (
		<QueryClientProvider client={ queryClient }>
			<Component />
		</QueryClientProvider>
	);

	if ( createRoot ) {
		createRoot( rootElement ).render( <WrappedComponent /> );
	} else {
		render( <WrappedComponent />, rootElement );
	}
};
