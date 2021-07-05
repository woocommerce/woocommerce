/**
 * External dependencies
 */
import { useMemo, useRef, useEffect } from '@wordpress/element';
import { useStoreSnackbarNoticesContext } from '@woocommerce/base-context/providers';

export const useStoreSnackbarNotices = () => {
	const {
		notices,
		createSnackbarNotice,
		removeSnackbarNotice,
		setIsSuppressed,
	} = useStoreSnackbarNoticesContext();
	// Added to a ref so the surface for notices doesn't change frequently
	// and thus can be used as dependencies on effects.
	const currentNotices = useRef( notices );

	// Update notices ref whenever they change
	useEffect( () => {
		currentNotices.current = notices;
	}, [ notices ] );

	const noticesApi = useMemo(
		() => ( {
			removeNotices: ( status = null ) => {
				currentNotices.current.forEach( ( notice ) => {
					if ( status === null || notice.status === status ) {
						removeSnackbarNotice( notice.id );
					}
				} );
			},
			removeSnackbarNotice,
		} ),
		[ removeSnackbarNotice ]
	);

	const noticeCreators = useMemo(
		() => ( {
			addSnackbarNotice: ( text, noticeProps = {} ) => {
				createSnackbarNotice( text, noticeProps );
			},
		} ),
		[ createSnackbarNotice ]
	);

	return {
		notices,
		...noticesApi,
		...noticeCreators,
		setIsSuppressed,
	};
};
