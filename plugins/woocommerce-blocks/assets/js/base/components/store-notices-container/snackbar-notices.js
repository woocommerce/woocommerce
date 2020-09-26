/**
 * External dependencies
 */
import { SnackbarList } from 'wordpress-components';
import { useStoreNotices } from '@woocommerce/base-hooks';
import { useEditorContext } from '@woocommerce/base-context';

const NoticesContainer = () => {
	const { isEditor } = useEditorContext();
	const { notices, removeNotice } = useStoreNotices();

	if ( isEditor ) {
		return null;
	}

	const snackbarNotices = notices.filter(
		( notice ) => notice.type === 'snackbar'
	);

	return (
		<SnackbarList
			notices={ snackbarNotices }
			className={ 'wc-block-components-notices__snackbar' }
			onRemove={ removeNotice }
		/>
	);
};

export default NoticesContainer;
