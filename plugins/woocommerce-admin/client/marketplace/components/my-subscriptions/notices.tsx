/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Alert from '../../assets/images/alert.svg';
import { Notice as NoticeType } from '../../contexts/types';
import { noticeStore } from '../../contexts/notice-store';
import { removeNotice } from '../../utils/functions';

export default function Notices() {
	const notices: NoticeType[] = useSelect(
		( select ) => select( noticeStore ).notices(),
		[]
	);

	const actions = ( notice: NoticeType ) => {
		if ( ! notice.options?.actions ) {
			return [];
		}
		return notice.options?.actions.map( ( action ) => {
			return {
				...action,
				variant: 'link',
				className: 'is-link',
			};
		} );
	};

	const errorNotices = [];
	for ( const notice of notices ) {
		errorNotices.push(
			<Notice
				className="woocommerce-marketplace__notice--error"
				status={ notice.status }
				onRemove={ () => removeNotice( notice.productKey ) }
				key={ notice.productKey }
				actions={ actions( notice ) }
			>
				<img src={ Alert } alt="" width={ 24 } height={ 24 } />
				{ notice.message }
			</Notice>
		);
	}
	return <>{ errorNotices }</>;
}
