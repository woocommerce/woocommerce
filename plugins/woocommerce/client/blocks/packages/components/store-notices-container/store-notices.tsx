/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import { useRef, useEffect, RawHTML } from '@wordpress/element';
import { sanitizeHTML } from '@woocommerce/sanitize';
import { useDispatch } from '@wordpress/data';
import { usePrevious } from '@woocommerce/base-hooks';
import { decodeEntities } from '@wordpress/html-entities';
import type { NoticeStatus, NoticeType } from '@woocommerce/types';
import type { NoticeBannerProps } from '@woocommerce/base-components/notice-banner';

/**
 * Internal dependencies
 */
import StoreNotice from '../store-notice';

const StoreNotices = ( {
	className,
	notices,
}: {
	className: string;
	notices: NoticeType[];
} ): JSX.Element => {
	const ref = useRef< HTMLDivElement >( null );
	const { removeNotice } = useDispatch( 'core/notices' );
	// Only scroll to the container when an error notice is added, not info notices.
	const errorIds = notices
		.map( ( notice ) => {
			if ( notice.status === 'error' || notice.status === 'warning' ) {
				return notice.id;
			}
			return null;
		} )
		.filter( Boolean );
	const previousErrorIds = usePrevious( errorIds );

	useEffect( () => {
		// Scroll to container when an error is added here.
		const containerRef = ref.current;

		if ( ! containerRef ) {
			return;
		}

		// Do not scroll if input has focus.
		const activeElement = containerRef.ownerDocument.activeElement;
		const inputs = [ 'input', 'select', 'button', 'textarea' ];

		if (
			activeElement &&
			inputs.indexOf( activeElement.tagName.toLowerCase() ) !== -1 &&
			activeElement.getAttribute( 'type' ) !== 'radio'
		) {
			return;
		}

		const newErrorIds = errorIds.filter(
			( value ) =>
				! previousErrorIds || ! previousErrorIds.includes( value )
		);

		if ( newErrorIds.length && containerRef?.scrollIntoView ) {
			containerRef.scrollIntoView( {
				behavior: 'smooth',
			} );
		}
	}, [ errorIds, previousErrorIds, ref ] );

	// Group notices by whether or not they are dismissible. Dismissible notices can be grouped.
	const dismissibleNotices = notices.filter(
		( { isDismissible } ) => !! isDismissible
	);
	const nonDismissibleNotices = notices.filter(
		( { isDismissible } ) => ! isDismissible
	);

	// Group dismissibleNotices by status. They will be combined into a single notice.
	const dismissibleNoticeGroups = {
		error: dismissibleNotices.filter(
			( { status } ) => status === 'error'
		),
		success: dismissibleNotices.filter(
			( { status } ) => status === 'success'
		),
		warning: dismissibleNotices.filter(
			( { status } ) => status === 'warning'
		),
		info: dismissibleNotices.filter( ( { status } ) => status === 'info' ),
		default: dismissibleNotices.filter(
			( { status } ) => status === 'default'
		),
	};

	return (
		<div
			ref={ ref }
			className={ clsx( className, 'wc-block-components-notices' ) }
		>
			{ nonDismissibleNotices.map( ( notice ) => (
				<StoreNotice
					key={ notice.id + '-' + notice.context }
					{ ...notice }
				>
					<RawHTML>
						{ sanitizeHTML( decodeEntities( notice.content ) ) }
					</RawHTML>
				</StoreNotice>
			) ) }
			{ Object.entries( dismissibleNoticeGroups ).map(
				( [ status, noticeGroup ] ) => {
					if ( ! noticeGroup.length ) {
						return null;
					}
					const uniqueNotices = noticeGroup
						.filter(
							(
								notice: NoticeType,
								noticeIndex: number,
								noticesArray: NoticeType[]
							) =>
								noticesArray.findIndex(
									( _notice: NoticeType ) =>
										_notice.content === notice.content
								) === noticeIndex
						)
						.map( ( notice ) => ( {
							...notice,
							content: sanitizeHTML(
								decodeEntities( notice.content )
							),
						} ) );
					const noticeProps: Omit< NoticeBannerProps, 'children' > = {
						status: status as NoticeStatus,
						onRemove: () => {
							noticeGroup.forEach( ( notice ) => {
								removeNotice( notice.id, notice.context );
							} );
						},
					};
					return uniqueNotices.length === 1 ? (
						<StoreNotice
							key={ 'store-notice-' + status }
							{ ...noticeProps }
						>
							<RawHTML>{ noticeGroup[ 0 ].content }</RawHTML>
						</StoreNotice>
					) : (
						<StoreNotice
							key={ 'store-notice-' + status }
							{ ...noticeProps }
							summary={
								status === 'error'
									? __(
											'Please fix the following errors before continuing',
											'woocommerce'
									  )
									: ''
							}
						>
							<ul>
								{ uniqueNotices.map( ( notice ) => (
									<li
										key={ notice.id + '-' + notice.context }
									>
										<RawHTML>{ notice.content }</RawHTML>
									</li>
								) ) }
							</ul>
						</StoreNotice>
					);
				}
			) }
		</div>
	);
};

export default StoreNotices;
