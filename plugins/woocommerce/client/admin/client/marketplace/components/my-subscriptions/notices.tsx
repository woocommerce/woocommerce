/**
 * External dependencies
 */
import { ComponentProps } from 'react';
import { Notice, VisuallyHidden } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

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
	openInNewTab,
}: NoticeAction ): WPNoticeAction => {
	const common = { label, variant: 'link', className: 'is-link' } as const;

	if ( url && openInNewTab ) {
		// Notice can't set a link target, so open the tab from a link-styled button.
		return {
			...common,
			// Notice renders `label` as the button's children, so a node works despite the string type.
			label: (
				<>
					{ label }
					<VisuallyHidden as="span">
						{ __( '(opens in a new tab)', 'woocommerce' ) }
					</VisuallyHidden>
				</>
			 ) as unknown as string,
			noDefaultClasses: true,
			onClick: () => window.open( url, '_blank', 'noopener' ),
		};
	}

	if ( url ) {
		return { ...common, url };
	}

	return { ...common, onClick: () => onClick?.() };
};

export default function Notices() {
	const notices: NoticeType[] = useSelect(
		( select ) => select( noticeStore ).notices(),
		[]
	);

	const errorNotices = [];
	for ( const notice of notices ) {
		errorNotices.push(
			<Notice
				className="woocommerce-marketplace__notice--error"
				status={ notice.status }
				onRemove={ () => removeNotice( notice.productKey ) }
				key={ notice.productKey }
				actions={ notice.options?.actions?.map( toNoticeAction ) }
			>
				<img src={ Alert } alt="" width={ 24 } height={ 24 } />
				{ notice.message }
			</Notice>
		);
	}
	return <>{ errorNotices }</>;
}
