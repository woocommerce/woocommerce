/**
 * External dependencies
 */
import { useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';

export const withSettingsHydration = ( group, settings ) => (
	OriginalComponent
) => {
	return ( props ) => {
		const settingsRef = useRef( settings );

		useSelect( ( select, registry ) => {
			if ( ! settingsRef.current ) {
				return;
			}

			const { isResolving, hasFinishedResolution } = select( STORE_NAME );
			const {
				startResolution,
				finishResolution,
				updateSettingsForGroup,
				clearIsDirty,
			} = registry.dispatch( STORE_NAME );

			if (
				! isResolving( 'getSettings', [ group ] ) &&
				! hasFinishedResolution( 'getSettings', [ group ] )
			) {
				startResolution( 'getSettings', [ group ] );
				updateSettingsForGroup( group, settingsRef.current );
				clearIsDirty( group );
				finishResolution( 'getSettings', [ group ] );
			}
		}, [] );

		return <OriginalComponent { ...props } />;
	};
};
