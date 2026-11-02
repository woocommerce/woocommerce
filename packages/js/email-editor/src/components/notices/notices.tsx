/**
 * External dependencies
 */
import type { ComponentProps } from 'react';
import { NoticeList } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { ValidationNotices } from './validation-notices';
import { EditorSnackbars } from './snackbars';
import { NoticesSlot } from '../../hacks/notices-slot';

// See: https://github.com/WordPress/gutenberg/blob/5be0ec4153c3adf9f0f2513239f4f7a358ba7948/packages/editor/src/components/editor-notices/index.js

type NoticeListNotices = ComponentProps< typeof NoticeList >[ 'notices' ];

interface EditorNoticesProps {
	disableSnackbarNotices?: boolean;
}

export function EditorNotices( {
	disableSnackbarNotices = false,
}: EditorNoticesProps = {} ) {
	const { notices } = useSelect(
		( select ) => ( {
			notices: select( noticesStore ).getNotices( 'email-editor' ),
		} ),
		[]
	);

	const { removeNotice } = useDispatch( noticesStore );

	// `WPNotice.status: string` and `actions[].onClick: Function` come in
	// looser than `<NoticeList>`'s prop types. At runtime the notices store
	// always emits values inside the narrower union; cast the filtered
	// results once so both `<NoticeList>` renders stay fully typed.
	const dismissibleNotices = notices.filter(
		( { isDismissible, type } ) => isDismissible && type === 'default'
	) as unknown as NoticeListNotices;

	const nonDismissibleNotices = notices.filter(
		( { isDismissible, type } ) => ! isDismissible && type === 'default'
	) as unknown as NoticeListNotices;

	return (
		<>
			<NoticesSlot>
				<NoticeList
					notices={ nonDismissibleNotices }
					className="components-editor-notices__pinned"
				/>
				<NoticeList
					notices={ dismissibleNotices }
					className="components-editor-notices__dismissible"
					onRemove={ ( id ) => removeNotice( id, 'email-editor' ) }
				/>
				<ValidationNotices />
			</NoticesSlot>
			{ ! disableSnackbarNotices && <EditorSnackbars /> }
		</>
	);
}
