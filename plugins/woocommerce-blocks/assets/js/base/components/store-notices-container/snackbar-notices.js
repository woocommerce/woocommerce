/**
 * External dependencies
 */
import { SnackbarList } from 'wordpress-components';
import { useStoreNotices } from '@woocommerce/base-hooks';
import { useEditorContext } from '@woocommerce/base-context';

const NoticesContainer = () => {
	const { isEditor } = useEditorContext();
	const { notices, removeNotice } = useStoreNotices();
	const snackbarNotices = notices.filter(
		( notice ) => notice.type === 'snackbar'
	);

	if ( isEditor ) {
		return null;
	}
	return (
		<SnackbarList
			notices={ snackbarNotices }
			className={ 'wc-block-notices__snackbar' }
			onRemove={ removeNotice }
		/>
	);
};

export default NoticesContainer;
