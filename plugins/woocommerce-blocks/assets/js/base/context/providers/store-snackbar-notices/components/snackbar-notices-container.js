/**
 * External dependencies
 */
import { SnackbarList } from 'wordpress-components';
import classnames from 'classnames';
import { __experimentalApplyCheckoutFilter } from '@woocommerce/blocks-checkout';

const SnackbarNoticesContainer = ( {
	className,
	notices,
	removeNotice,
	isEditor,
} ) => {
	if ( isEditor ) {
		return null;
	}

	const snackbarNotices = notices.filter(
		( notice ) => notice.type === 'snackbar'
	);

	const noticeVisibility = snackbarNotices.reduce( ( acc, { content } ) => {
		acc[ content ] = true;
		return acc;
	}, {} );

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
			onRemove={ removeNotice }
		/>
	);
};

export default SnackbarNoticesContainer;
