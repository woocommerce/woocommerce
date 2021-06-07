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

	const filteredNotices = __experimentalApplyCheckoutFilter( {
		filterName: 'snackbarNotices',
		defaultValue: snackbarNotices,
	} );

	const wrapperClass = classnames(
		className,
		'wc-block-components-notices__snackbar'
	);

	return (
		<SnackbarList
			notices={ filteredNotices }
			className={ wrapperClass }
			onRemove={ removeNotice }
		/>
	);
};

export default SnackbarNoticesContainer;
