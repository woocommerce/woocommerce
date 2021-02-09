/**
 * External dependencies
 */
import classnames from 'classnames';
import PropTypes from 'prop-types';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import SnackbarList from './snackbar/list';
import './style.scss';

function TransientNotices( props ) {
	const { removeNotice: onRemove } = useDispatch( 'core/notices' );
	const { removeNotice: onRemove2 } = useDispatch( 'core/notices2' );
	const noticeData = useSelect( ( select ) => {
		// NOTE: This uses core/notices2, if this file is copied back upstream
		// to Gutenberg this needs to be changed back to just core/notices.
		const notices = select( 'core/notices' ).getNotices();
		const notices2 = select( 'core/notices2' ).getNotices();

		return { notices, notices2 };
	} );

	/**
	 * Combines the two notices in the component vs in the useSelect, as we don't want to
	 * create new object references on each useSelect call.
	 */
	const getNotices = () => {
		const { notices, notices2 = [] } = noticeData;
		return notices.concat( notices2 );
	};

	const { className } = props;
	const classes = classnames(
		'woocommerce-transient-notices',
		'components-notices__snackbar',
		className
	);
	const notices = getNotices();

	return (
		<SnackbarList
			notices={ notices }
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
