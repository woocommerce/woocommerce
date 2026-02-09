/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { paymentStore, storeNoticesStore } from '@woocommerce/block-data';
import { getNoticeContexts } from '@woocommerce/base-utils';
import type { WPNotice } from '@wordpress/notices/build-types/store/selectors';
import { useMemo, useEffect } from '@wordpress/element';
import type { NoticeType } from '@woocommerce/types';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import './style.scss';
import StoreNotices from './store-notices';
import SnackbarNotices from './snackbar-notices';
import type { StoreNoticesContainerProps } from './types';

const formatNotices = (
	notices: WPNotice[],
	context: string
): NoticeType[] => {
	return notices.map( ( notice ) => ( {
		...notice,
		context,
	} ) ) as NoticeType[];
};

const StoreNoticesContainer = ( {
	className = '',
	context = '',
	additionalNotices = [],
}: StoreNoticesContainerProps ): JSX.Element | null => {
	const { registerContainer, unregisterContainer } =
		useDispatch( storeNoticesStore );
	const { suppressNotices, registeredContainers } = useSelect(
		( select ) => ( {
			suppressNotices:
				select( paymentStore ).isExpressPaymentMethodActive(),
			registeredContainers:
				select( storeNoticesStore ).getRegisteredContainers(),
		} ),
		[]
	);
	const contexts = useMemo< string[] >(
		() => ( Array.isArray( context ) ? context : [ context ] ),
		[ context ]
	);
	// Find sub-contexts that have not been registered. We will show notices from those contexts here too.
	const allContexts = getNoticeContexts();
	const unregisteredSubContexts = allContexts.filter(
		( subContext: string ) =>
			contexts.some( ( _context: string ) =>
				subContext.includes( _context + '/' )
			) && ! registeredContainers.includes( subContext )
	);

	// Get notices from the current context and any sub-contexts and append the name of the context to the notice
	// objects for later reference.
	const notices =
		useSelect(
			( select ) => {
				const getNotices = select( noticesStore ).getNotices;

				return [
					...unregisteredSubContexts.flatMap(
						( subContext: string ) =>
							formatNotices(
								getNotices( subContext ),
								subContext
							)
					),
					...contexts.flatMap( ( subContext: string ) =>
						formatNotices(
							getNotices( subContext ).concat(
								additionalNotices as WPNotice[]
							),
							subContext
						)
					),
				].filter( Boolean ) as NoticeType[];
			},
			[ contexts, unregisteredSubContexts, additionalNotices ]
		) || [];

	// Register the container context with the parent.
	useEffect( () => {
		contexts.forEach( ( _context ) => registerContainer( _context ) );
		return () => {
			contexts.forEach( ( _context ) => unregisterContainer( _context ) );
		};
	}, [ contexts, registerContainer, unregisterContainer ] );

	if ( suppressNotices ) {
		return null;
	}

	return (
		<>
			<StoreNotices
				className={ className }
				notices={ notices.filter(
					( notice: NoticeType ) => notice.type === 'default'
				) }
			/>
			<SnackbarNotices
				className={ className }
				notices={ notices.filter(
					( notice: NoticeType ) => notice.type === 'snackbar'
				) }
			/>
		</>
	);
};

export default StoreNoticesContainer;
