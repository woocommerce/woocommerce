/**
 * External dependencies
 */
import { useRef } from '@wordpress/element';
import { getSetting } from '@woocommerce/settings';
import { SCHEMA_STORE_KEY } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';

/**
 * Hydrate Rest API data from settings to reduce the number of API requests needed.
 */
const useRestApiHydration = () => {
	const restApiRoutes = useRef( getSetting( 'restApiRoutes' ) );

	useSelect( ( select, registry ) => {
		if ( ! restApiRoutes.current ) {
			return;
		}

		const { isResolving, hasFinishedResolution } = select(
			SCHEMA_STORE_KEY
		);
		const {
			receiveRoutes,
			startResolution,
			finishResolution,
		} = registry.dispatch( SCHEMA_STORE_KEY );

		Object.keys( restApiRoutes.current ).forEach( ( namespace ) => {
			const routes = restApiRoutes.current[ namespace ];
			if (
				! isResolving( 'getRoutes', [ namespace ] ) &&
				! hasFinishedResolution( 'getRoutes', [ namespace ] )
			) {
				startResolution( 'getRoutes', [ namespace ] );
				receiveRoutes( routes, [ namespace ] );
				finishResolution( 'getRoutes', [ namespace ] );
			}
		} );
	}, [] );
};

/**
 * HOC that calls the useRestApiHydration hook.
 *
 * @param {Function} OriginalComponent Component being wrapped.
 */
const withRestApiHydration = ( OriginalComponent ) => {
	return ( props ) => {
		useRestApiHydration();
		return <OriginalComponent { ...props } />;
	};
};

export default withRestApiHydration;
