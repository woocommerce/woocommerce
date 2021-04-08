/**
 * External dependencies
 */
import { SnackbarList } from 'wordpress-components';
import classnames from 'classnames';

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

	const wrapperClass = classnames(
		className,
		'wc-block-components-notices__snackbar'
	);

	return (
		<SnackbarList
			notices={ snackbarNotices }
			className={ wrapperClass }
			onRemove={ removeNotice }
		/>
	);
};

export default SnackbarNoticesContainer;
