/**
 * External dependencies
 */
import { useEffect } from 'react';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { createElement, Fragment } from '@wordpress/element';
import { optionsStore } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { CustomerEffortScoreTracks } from '..';
import { QUEUE_OPTION_NAME, STORE_KEY } from '../../store';

/**
 * @typedef {Object} CustomerEffortScoreTracksContainerProps
 * @property {Array<Object>} queue      - The queue of CES tracks surveys to display
 * @property {boolean}       resolving  - If the queue option is being resolved
 * @property {Function}      clearQueue - Set up clearing the queue on the next page load
 */

/**
 * Maps the queue of CES tracks surveys to CustomerEffortScoreTracks
 * components. Note that generally there will only be a single survey per page
 * however this is designed to be flexible if multiple surveys per page are
 * added in the future.
 *
 * @param {CustomerEffortScoreTracksContainerProps} props Component props
 * @return {JSX.Element|null} The rendered component
 */
function _CustomerEffortScoreTracksContainer( {
	queue,
	resolving,
	clearQueue,
} ) {
	const queueForPage = queue.filter(
		( item ) =>
			item.pagenow === window.pagenow &&
			item.adminpage === window.adminpage
	);
	useEffect( () => {
		if ( queueForPage.length ) {
			clearQueue();
		}
	}, [ queueForPage ] );

	if ( resolving ) {
		return null;
	}

	return (
		<>
			{ queueForPage.map( ( item, index ) => (
				<CustomerEffortScoreTracks
					key={ index }
					action={ item.action }
					description={ item.description }
					noticeLabel={ item.noticeLabel }
					firstQuestion={ item.firstQuestion }
					secondQuestion={ item.secondQuestion }
					icon={ item.icon }
					title={ item.title }
					onSubmitLabel={ item.onsubmit_label }
					trackProps={ item.props || {} }
				/>
			) ) }
		</>
	);
}

/** @type {import('react').ComponentType<CustomerEffortScoreTracksContainerProps>} */
export const CustomerEffortScoreTracksContainer = compose(
	withSelect( ( select ) => {
		const { getCesSurveyQueue, isResolving } = select( STORE_KEY );
		const queue = getCesSurveyQueue();
		const resolving = isResolving( 'getOption', [ QUEUE_OPTION_NAME ] );

		return { queue, resolving };
	} ),
	withDispatch( ( dispatch ) => {
		const { updateOptions } = dispatch( optionsStore );

		return {
			clearQueue: () => {
				// This sets an option that should be used on the next page
				// load to clear the CES tracks queue for the current page (see
				// CustomerEffortScoreTracks.php) - clearing the queue
				// directly puts this into an infinite loop which is picked
				// up by React.
				updateOptions( {
					woocommerce_clear_ces_tracks_queue_for_page: {
						pagenow: window.pagenow,
						adminpage: window.adminpage,
					},
				} );
			},
		};
	} )
)( _CustomerEffortScoreTracksContainer );
