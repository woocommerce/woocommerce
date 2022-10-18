/**
 * External dependencies
 */
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import CustomerEffortScoreTracks from './customer-effort-score-tracks';
import { STORE_KEY, QUEUE_OPTION_NAME } from './data/constants';
import './data';

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
function CustomerEffortScoreTracksContainer( {
	queue,
	resolving,
	clearQueue,
} ) {
	if ( resolving ) {
		return null;
	}

	const queueForPage = queue.filter(
		( item ) =>
			item.pagenow === window.pagenow &&
			item.adminpage === window.adminpage
	);

	if ( queueForPage.length ) {
		clearQueue();
	}

	return (
		<>
			{ queueForPage.map( ( item, index ) => (
				<CustomerEffortScoreTracks
					key={ index }
					action={ item.action }
					label={ item.label }
					onSubmitLabel={ item.onsubmit_label }
					trackProps={ item.props || {} }
				/>
			) ) }
		</>
	);
}

CustomerEffortScoreTracksContainer.propTypes = {
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

export default compose(
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
)( CustomerEffortScoreTracksContainer );
