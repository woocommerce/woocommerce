/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import {
	PAYMENT_STORE_KEY,
	STORE_NOTICES_STORE_KEY,
} from '@woocommerce/block-data';
import { getNoticeContexts } from '@woocommerce/base-utils';
import type { Notice } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import './style.scss';
import StoreNotices from './store-notices';
import SnackbarNotices from './snackbar-notices';
import type { StoreNoticesContainerProps, StoreNotice } from './types';

const formatNotices = ( notices: Notice[], context: string ): StoreNotice[] => {
	return notices.map( ( notice ) => ( {
		...notice,
		context,
	} ) ) as StoreNotice[];
};

const StoreNoticesContainer = ( {
	className = '',
	context,
	additionalNotices = [],
}: StoreNoticesContainerProps ): JSX.Element | null => {
	const { suppressNotices, registeredContainers } = useSelect(
		( select ) => ( {
			suppressNotices:
				select( PAYMENT_STORE_KEY ).isExpressPaymentMethodActive(),
			registeredContainers: select(
				STORE_NOTICES_STORE_KEY
			).getRegisteredContainers(),
		} )
	);
	const contexts = Array.isArray( context ) ? context : [ context ];
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
	const notices = useSelect< StoreNotice[] >( ( select ) => {
		const { getNotices } = select( 'core/notices' );

		return [
			...unregisteredSubContexts.flatMap( ( subContext: string ) =>
				formatNotices( getNotices( subContext ), subContext )
			),
			...contexts.flatMap( ( subContext: string ) =>
				formatNotices(
					getNotices( subContext ).concat( additionalNotices ),
					subContext
				)
			),
		].filter( Boolean ) as StoreNotice[];
	} );
	if ( suppressNotices || ! notices.length ) {
		return null;
	}

	return (
		<>
			<StoreNotices
				className={ className }
				context={ contexts }
				notices={ notices.filter(
					( notice ) => notice.type === 'default'
				) }
			/>
			<SnackbarNotices
				className={ className }
				notices={ notices.filter(
					( notice ) => notice.type === 'snackbar'
				) }
			/>
		</>
	);
};

export default StoreNoticesContainer;
