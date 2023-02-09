/**
 * External dependencies
 */
import classnames from 'classnames';
import { useRef, useEffect } from '@wordpress/element';
import { Notice } from 'wordpress-components';
import { sanitizeHTML } from '@woocommerce/utils';
import { useDispatch } from '@wordpress/data';
import { usePrevious } from '@woocommerce/base-hooks';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import { getClassNameFromStatus } from './utils';
import type { StoreNotice } from './types';

const StoreNotices = ( {
	className,
	notices,
}: {
	className: string;
	notices: StoreNotice[];
} ): JSX.Element => {
	const ref = useRef< HTMLDivElement >( null );
	const { removeNotice } = useDispatch( 'core/notices' );
	const noticeIds = notices.map( ( notice ) => notice.id );
	const previousNoticeIds = usePrevious( noticeIds );

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

		const newNoticeIds = noticeIds.filter(
			( value ) =>
				! previousNoticeIds || ! previousNoticeIds.includes( value )
		);

		if ( newNoticeIds.length && containerRef?.scrollIntoView ) {
			containerRef.scrollIntoView( {
				behavior: 'smooth',
			} );
		}
	}, [ noticeIds, previousNoticeIds, ref ] );

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
	};

	return (
		<div
			ref={ ref }
			className={ classnames( className, 'wc-block-components-notices' ) }
		>
			{ nonDismissibleNotices.map( ( notice ) => (
				<Notice
					key={ notice.id + '-' + notice.context }
					className={ classnames(
						'wc-block-components-notices__notice',
						getClassNameFromStatus( notice.status )
					) }
					{ ...notice }
				>
					{ sanitizeHTML( decodeEntities( notice.content ) ) }
				</Notice>
			) ) }
			{ Object.entries( dismissibleNoticeGroups ).map(
				( [ status, noticeGroup ] ) => {
					if ( ! noticeGroup.length ) {
						return null;
					}
					const uniqueNotices = noticeGroup.filter(
						(
							notice: Notice,
							noticeIndex: number,
							noticesArray: Notice[]
						) =>
							noticesArray.findIndex(
								( _notice: Notice ) =>
									_notice.content === notice.content
							) === noticeIndex
					);
					return (
						<Notice
							key={ `store-notice-${ status }` }
							className={ classnames(
								'wc-block-components-notices__notice',
								getClassNameFromStatus( status )
							) }
							onRemove={ () => {
								noticeGroup.forEach( ( notice ) => {
									removeNotice( notice.id, notice.context );
								} );
							} }
						>
							{ uniqueNotices.length === 1 ? (
								<>
									{ sanitizeHTML(
										decodeEntities(
											noticeGroup[ 0 ].content
										)
									) }
								</>
							) : (
								<ul>
									{ uniqueNotices.map( ( notice ) => (
										<li
											key={
												notice.id + '-' + notice.context
											}
										>
											{ sanitizeHTML(
												decodeEntities( notice.content )
											) }
										</li>
									) ) }
								</ul>
							) }
						</Notice>
					);
				}
			) }
		</div>
	);
};

export default StoreNotices;
