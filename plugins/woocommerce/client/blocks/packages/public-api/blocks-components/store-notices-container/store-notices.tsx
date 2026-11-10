/**
 * External dependencies
 */
import { _n } from '@wordpress/i18n';
import clsx from 'clsx';
import {
	useRef,
	useEffect,
	Children,
	cloneElement,
	RawHTML,
} from '@wordpress/element';
import { sanitizeHTML } from '@woocommerce/sanitize';
import { useDispatch } from '@wordpress/data';
import { usePrevious, usePresenceList } from '@woocommerce/base-hooks';
import { decodeEntities } from '@wordpress/html-entities';
import type { NoticeStatus, NoticeType } from '@woocommerce/types';
import type { NoticeBannerProps } from '@woocommerce/base-components/notice-banner';
import type { ReactElement, ReactNode, RefObject } from 'react';

/**
 * Internal dependencies
 */
import StoreNotice from '../store-notice';

/**
 * Renders notice contents as a list, so a single notice and multiple notices
 * share the same markup and can be styled with a single set of rules.
 *
 * `role="list"` is needed because the list markers are removed with CSS, which
 * strips the implicit list semantics in some browsers.
 *
 * `data-id` mirrors the classic notice templates, which expose the notice id on
 * each `li` via `wc_get_notice_data_attr()`. Store API errors surface their
 * error code as the notice id (e.g. `woocommerce_rest_cart_coupon_error`), so
 * this gives themes and scripts the same per-notice hook the shortcode
 * checkout has.
 */
const StoreNoticeList = ( {
	notices,
}: {
	notices: ( Pick< NoticeType, 'id' | 'content' > &
		Partial< Pick< NoticeType, 'context' > > )[];
} ): JSX.Element => (
	// The role is not redundant here, see above.
	// eslint-disable-next-line jsx-a11y/no-redundant-roles
	<ul className="wc-block-components-notice-banner__list" role="list">
		{ notices.map( ( notice ) => (
			<li key={ notice.id + '-' + notice.context } data-id={ notice.id }>
				<RawHTML>{ notice.content }</RawHTML>
			</li>
		) ) }
	</ul>
);

// Keeps removed notices mounted while they animate out.
const NoticesContainer = ( {
	containerRef,
	className,
	children,
}: {
	containerRef: RefObject< HTMLDivElement >;
	className: string;
	children: ReactNode;
} ): JSX.Element => {
	const notices = usePresenceList(
		containerRef,
		Children.toArray( children ) as ReactElement[],
		( notice ) => String( notice.key ),
		{ layout: true }
	);
	return (
		<div ref={ containerRef } className={ className }>
			{ notices.map( ( { item, className: motionClassName } ) =>
				cloneElement( item, { className: motionClassName } )
			) }
		</div>
	);
};

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
				behavior: window.matchMedia?.(
					'(prefers-reduced-motion: reduce)'
				)?.matches
					? 'instant'
					: 'smooth',
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
		<NoticesContainer
			containerRef={ ref }
			className={ clsx( className, 'wc-block-components-notices' ) }
		>
			{ nonDismissibleNotices.map( ( notice ) => (
				<StoreNotice
					key={ notice.id + '-' + notice.context }
					{ ...notice }
				>
					<StoreNoticeList
						notices={ [
							{
								...notice,
								content: sanitizeHTML(
									decodeEntities( notice.content )
								),
							},
						] }
					/>
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
					return (
						<StoreNotice
							key={ 'store-notice-' + status }
							{ ...noticeProps }
							summary={
								status === 'error'
									? _n(
											'Please fix the following error before continuing',
											'Please fix the following errors before continuing',
											uniqueNotices.length,
											'woocommerce'
										)
									: ''
							}
						>
							<StoreNoticeList notices={ uniqueNotices } />
						</StoreNotice>
					);
				}
			) }
		</NoticesContainer>
	);
};

export default StoreNotices;
