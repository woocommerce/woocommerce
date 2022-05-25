/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { SnackbarList } from 'wordpress-components';
import classnames from 'classnames';
import { __experimentalApplyCheckoutFilter } from '@woocommerce/blocks-checkout';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useEditorContext } from '../../editor-context';

const EMPTY_SNACKBAR_NOTICES = {};

export const SnackbarNoticesContainer = ( {
	className,
	context = 'default',
} ) => {
	const { isEditor } = useEditorContext();

	const { notices } = useSelect( ( select ) => {
		const store = select( 'core/notices' );
		return {
			notices: store.getNotices( context ),
		};
	} );
	const { removeNotice } = useDispatch( 'core/notices' );

	if ( isEditor ) {
		return null;
	}

	const snackbarNotices = notices.filter(
		( notice ) => notice.type === 'snackbar'
	);

	const noticeVisibility =
		snackbarNotices.length > 0
			? snackbarNotices.reduce( ( acc, { content } ) => {
					acc[ content ] = true;
					return acc;
			  }, {} )
			: EMPTY_SNACKBAR_NOTICES;

	const filteredNotices = __experimentalApplyCheckoutFilter( {
		filterName: 'snackbarNoticeVisibility',
		defaultValue: noticeVisibility,
	} );

	const visibleNotices = snackbarNotices.filter(
		( notice ) => filteredNotices[ notice.content ] === true
	);

	const wrapperClass = classnames(
		className,
		'wc-block-components-notices__snackbar'
	);

	return (
		<SnackbarList
			notices={ visibleNotices }
			className={ wrapperClass }
			onRemove={ () => {
				visibleNotices.forEach( ( notice ) =>
					removeNotice( notice.id, context )
				);
			} }
		/>
	);
};

SnackbarNoticesContainer.propTypes = {
	className: PropTypes.string,
	notices: PropTypes.arrayOf(
		PropTypes.shape( {
			content: PropTypes.string.isRequired,
			id: PropTypes.string.isRequired,
			status: PropTypes.string.isRequired,
			isDismissible: PropTypes.bool,
			type: PropTypes.oneOf( [ 'default', 'snackbar' ] ),
		} )
	),
};
