/**
 * External dependencies
 */
import classnames from 'classnames';
import { SnackbarList } from 'wordpress-components';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import type { StoreNotice } from './types';

const SnackbarNotices = ( {
	className,
	notices,
}: {
	className: string;
	notices: StoreNotice[];
} ): JSX.Element | null => {
	const { removeNotice } = useDispatch( 'core/notices' );

	if ( ! notices.length ) {
		return null;
	}

	return (
		<SnackbarList
			className={ classnames(
				className,
				'wc-block-components-notices__snackbar'
			) }
			notices={ notices.map( ( notice ) => {
				return {
					...notice,
					className: 'components-snackbar--status-' + notice.status,
				};
			} ) }
			onRemove={ ( noticeId: string ) => {
				notices.forEach( ( notice ) => {
					if ( notice.explicitDismiss && notice.id === noticeId ) {
						removeNotice( notice.id, notice.context );
					} else if ( ! notice.explicitDismiss ) {
						removeNotice( notice.id, notice.context );
					}
				} );
			} }
		/>
	);
};

export default SnackbarNotices;
