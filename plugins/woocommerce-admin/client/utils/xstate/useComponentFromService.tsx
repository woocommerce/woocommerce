/**
 * External dependencies
 */
import { useSelector as xstateUseSelector } from '@xstate5/react';
import React, { useEffect, useState } from 'react';
/**
 * Internal dependencies
 */
import { findComponentMeta } from '~/utils/xstate/find-component';

type ComponentMetaType< ComponentPropsType > = {
	component: ( arg0: ComponentPropsType ) => React.ReactElement;
};
export function useComponentFromXStateService< ComponentProps >(
	service: Parameters< typeof xstateUseSelector >[ 0 ]
): [ ComponentMetaType< ComponentProps >[ 'component' ] | null ] {
	const componentMeta = xstateUseSelector( service, ( state ) =>
		findComponentMeta< ComponentMetaType< ComponentProps > >(
			state.getMeta() ?? undefined
		)
	);

	const [ Component, setComponent ] = useState<
		ComponentMetaType< ComponentProps >[ 'component' ] | null
	>( null );

	useEffect( () => {
		if ( componentMeta?.component ) {
			setComponent( () => componentMeta.component );
		}
	}, [ componentMeta?.component ] );

	return [ Component ? Component : null ];
}
