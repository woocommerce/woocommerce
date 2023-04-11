/**
 * External dependencies
 */
import { useEffect } from 'react';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { createElement, Fragment } from '@wordpress/element';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { CustomerEffortScoreTracks } from '../';
import { QUEUE_OPTION_NAME, STORE_KEY } from '../../store';

/**
 * Maps the queue of CES tracks surveys to CustomerEffortScoreTracks
 * components. Note that generally there will only be a single survey per page
 * however this is designed to be flexible if multiple surveys per page are
 * added in the future.
 *
 * @param {Object}   props            Component props.
 * @param {Array}    props.queue      The queue of surveys.
 * @param {boolean}  props.resolving  Whether the queue is resolving.
 * @param {Function} props.clearQueue Sets up clearing of the queue on the next page load.
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

_CustomerEffortScoreTracksContainer.propTypes = {
	/**
	 * The queue of CES tracks surveys to display.
	 */
	queue: PropTypes.arrayOf( PropTypes.object ),
	/**
	 * If the queue option is being resolved.
	 */
	resolving: PropTypes.bool,
	/**
	 * Set up clearing the queue on the next page load.
	 */
	clearQueue: PropTypes.func,
};

export const CustomerEffortScoreTracksContainer = compose(
	withSelect( ( select ) => {
		const { getCesSurveyQueue, isResolving } = select( STORE_KEY );
		const queue = getCesSurveyQueue();
		const resolving = isResolving( 'getOption', [ QUEUE_OPTION_NAME ] );

		return { queue, resolving };
	} ),
	withDispatch( ( dispatch ) => {
		const { updateOptions } = dispatch( OPTIONS_STORE_NAME );

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
