/**
 * External dependencies
 */
import { applyFilters } from '@wordpress/hooks';
import classnames from 'classnames';
import { NOTICES_STORE_NAME, USER_STORE_NAME } from '@woocommerce/data';
import PropTypes from 'prop-types';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SnackbarList from './snackbar/list';
import './style.scss';

const QUEUED_NOTICE_FILTER = 'woocommerce_admin_queued_notice_filter';

function TransientNotices( props ) {
	const { removeNotice: onRemove } = useDispatch( 'core/notices' );
	const {
		createNotice: createNotice2,
		removeNotice: onRemove2,
	} = useDispatch( 'core/notices2' );
	const { dismissNotice } = useDispatch( NOTICES_STORE_NAME );
	const {
		hasFinishedResolution,
		notices = [],
		notices2 = [],
		noticesQueue = [],
	} = useSelect( ( select ) => {
		const currentUser = select( USER_STORE_NAME ).getCurrentUser();

		// NOTE: This uses core/notices2, if this file is copied back upstream
		// to Gutenberg this needs to be changed back to just core/notices.
		return {
			currentUser,
			hasFinishedResolution: select(
				NOTICES_STORE_NAME
			).hasFinishedResolution( 'getNotices', [ currentUser.id ] ),
			notices: select( 'core/notices' ).getNotices(),
			notices2: select( 'core/notices2' ).getNotices(),
			noticesQueue:
				currentUser && currentUser.id
					? Object.values(
							select( NOTICES_STORE_NAME ).getNotices(
								currentUser.id
							)
					  )
					: [],
		};
	} );

	useEffect( () => {
		if ( ! hasFinishedResolution ) {
			return;
		}

		noticesQueue.forEach( ( queuedNotice ) => {
			/**
			 * Filter each transient notice.
			 *
			 * @filter woocommerce_admin_queued_notice_filter
			 * @param {Object} notice A transient notice.
			 */
			const notice = applyFilters( QUEUED_NOTICE_FILTER, queuedNotice );

			createNotice2( notice.status, notice.content, {
				onDismiss: () => {
					dismissNotice( notice.id );
				},
				...( notice.options || {} ),
			} );
		} );
	}, [ hasFinishedResolution ] );

	/**
	 * Combines the two notices in the component vs in the useSelect, as we don't want to
	 * create new object references on each useSelect call.
	 */
	const getNotices = () => {
		return notices.concat( notices2 );
	};

	const { className } = props;
	const classes = classnames(
		'woocommerce-transient-notices',
		'components-notices__snackbar',
		className
	);
	const combinedNotices = getNotices();

	return (
		<SnackbarList
			notices={ combinedNotices }
			className={ classes }
			onRemove={ onRemove }
			onRemove2={ onRemove2 }
		/>
	);
}

TransientNotices.propTypes = {
	/**
	 * Additional class name to style the component.
	 */
	className: PropTypes.string,
	/**
	 * Array of notices to be displayed.
	 */
	notices: PropTypes.array,
};

export default TransientNotices;
