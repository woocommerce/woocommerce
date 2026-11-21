/**
 * External dependencies
 */
import { useSelector as xstateUseSelector } from '@xstate5/react';
import { useEffect, useState } from 'react';

type MobileHeaderMetaType< ComponentPropsType > = {
	mobileHeader: ( arg0: ComponentPropsType ) => React.ReactElement;
};

export function useMobileHeaderFromXStateService< ComponentProps >(
	service: Parameters< typeof xstateUseSelector >[ 0 ]
): [ MobileHeaderMetaType< ComponentProps >[ 'mobileHeader' ] | null ] {
	const mobileHeaderMeta = xstateUseSelector( service, ( state ) => {
		const meta = state.getMeta() as Record<
			string,
			{
				mobileHeader?: ( props: ComponentProps ) => React.ReactElement;
			}
		>;

		const metaEntry = Object.values( meta ).find(
			( metaValue ) => metaValue?.mobileHeader
		);

		return metaEntry?.mobileHeader ?? null;
	} );

	const [ MobileHeaderComponent, setMobileHeaderComponent ] = useState<
		MobileHeaderMetaType< ComponentProps >[ 'mobileHeader' ] | null
	>( null );

	useEffect( () => {
		setMobileHeaderComponent(
			mobileHeaderMeta ? () => mobileHeaderMeta : null
		);
	}, [ mobileHeaderMeta ] );

	return [ MobileHeaderComponent ? MobileHeaderComponent : null ];
}
