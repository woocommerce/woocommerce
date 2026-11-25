/**
 * External dependencies
 */
import clsx from 'clsx';
import type { NoticeType } from '@woocommerce/types';
import {
	cloneElement,
	createRef,
	useRef,
	type RefObject,
} from '@wordpress/element';
import { whenAnimationsEnd } from '@woocommerce/base-utils';
import { CSSTransition, TransitionGroup } from 'react-transition-group';

/**
 * Internal dependencies
 */
import './style.scss';
import Snackbar from './snackbar';

export type SnackbarListProps = {
	// Class name to be added to the container.
	className?: string | undefined;
	// List of notices to be rendered.
	notices: NoticeType[];
	// Callback to be called when a notice is dismissed.
	onRemove: ( id: string ) => void;
};

/**
 * A temporary informational UI displayed at the bottom of store pages.
 */
const SnackbarList = ( {
	notices,
	className,
	onRemove = () => void 0,
}: SnackbarListProps ): JSX.Element => {
	const listRef = useRef< HTMLDivElement | null >( null );
	// One ref for each snackbar, so that its transition can find it without `findDOMNode`, which React deprecates.
	const snackbarRefs = useRef(
		new Map< string, RefObject< HTMLDivElement > >()
	);

	// A snackbar that the shopper dismisses goes at once. Only the timeout plays the exit.
	const dismissed = useRef( new WeakSet< RefObject< HTMLDivElement > >() );

	const removeNotice = ( notice: NoticeType ) => () =>
		onRemove( notice?.id || '' );

	return (
		<div
			className={ clsx(
				className,
				'wc-block-components-notice-snackbar-list'
			) }
			tabIndex={ -1 }
			ref={ listRef }
		>
			<TransitionGroup
				// Applies to the snackbars that leave too, which keep the props of their last render.
				childFactory={ ( child ) =>
					cloneElement( child, {
						exit: ! dismissed.current.has( child.props.nodeRef ),
					} )
				}
			>
				{ notices.map( ( notice ) => {
					const { content, ...restNotice } = notice;
					const snackbarRef =
						snackbarRefs.current.get( notice.id ) ??
						createRef< HTMLDivElement >();
					snackbarRefs.current.set( notice.id, snackbarRef );
					return (
						<CSSTransition
							key={ 'snackbar-' + notice.id }
							nodeRef={ snackbarRef }
							// The stylesheet sets the timing and handles reduced motion, so the script waits for the
							// animation to end instead of a fixed timeout.
							addEndListener={ ( done: () => void ) =>
								whenAnimationsEnd( snackbarRef.current, done )
							}
							onExited={ () =>
								snackbarRefs.current.delete( notice.id )
							}
							classNames="notice-transition"
						>
							<Snackbar
								{ ...restNotice }
								onRemove={ removeNotice( notice ) }
								onDismiss={ () =>
									dismissed.current.add( snackbarRef )
								}
								listRef={ listRef }
								containerRef={ snackbarRef }
							>
								{ content }
							</Snackbar>
						</CSSTransition>
					);
				} ) }
			</TransitionGroup>
		</div>
	);
};

export default SnackbarList;
