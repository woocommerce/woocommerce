/**
 * External dependencies
 */
import { ComponentProps } from 'react';
import { ExternalLink, Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Alert from '../../assets/images/alert.svg';
import { NoticeAction, Notice as NoticeType } from '../../contexts/types';
import { noticeStore } from '../../contexts/notice-store';
import { removeNotice } from '../../utils/functions';

type WPNoticeAction = NonNullable<
	ComponentProps< typeof Notice >[ 'actions' ]
>[ number ];

// Notice renders any `url` it gets, even an empty one, as a link, so onClick-only actions must not pass one.
const toNoticeAction = ( {
	label,
	url,
	onClick,
}: NoticeAction ): WPNoticeAction => {
	const common = { label, variant: 'link', className: 'is-link' } as const;

	if ( url ) {
		return { ...common, url };
	}

	return { ...common, onClick: () => onClick?.() };
};

const opensInNewTab = (
	action: NoticeAction
): action is NoticeAction & { url: string } =>
	!! action.url && !! action.openInNewTab;

export default function Notices() {
	const notices: NoticeType[] = useSelect(
		( select ) => select( noticeStore ).notices(),
		[]
	);

	const errorNotices = [];
	for ( const notice of notices ) {
		const actions = notice.options?.actions ?? [];
		const newTabActions = actions.filter( opensInNewTab );

		errorNotices.push(
			<Notice
				className="woocommerce-marketplace__notice--error"
				status={ notice.status }
				onRemove={ () => removeNotice( notice.productKey ) }
				key={ notice.productKey }
				actions={ actions
					.filter( ( action ) => ! opensInNewTab( action ) )
					.map( toNoticeAction ) }
			>
				<img src={ Alert } alt="" width={ 24 } height={ 24 } />
				{ notice.message }
				{ newTabActions.length > 0 && (
					<div className="components-notice__actions">
						{ newTabActions.map( ( { label, url } ) => (
							<ExternalLink
								key={ label }
								href={ url }
								className="components-button components-notice__action is-link is-next-40px-default-size"
							>
								{ label }
							</ExternalLink>
						) ) }
					</div>
				) }
			</Notice>
		);
	}
	return <>{ errorNotices }</>;
}
