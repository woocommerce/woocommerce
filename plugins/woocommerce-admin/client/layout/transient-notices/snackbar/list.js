/**
 * External dependencies
 */
import clsx from 'clsx';
import { omit, noop } from 'lodash';
import { useTransition, animated } from '@react-spring/web';
import { useReducedMotion } from '@wordpress/compose';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Snackbar from './';

/**
 * Renders a list of notices.
 *
 * @param {Object}   $0           Props passed to the component.
 * @param {Array}    $0.notices   Array of notices to render.
 * @param {Function} $0.onRemove  Function called when a notice should be removed / dismissed.
 * @param {Function} $0.onRemove2 Function called when a notice should be removed / dismissed.
 * @param {Object}   $0.className Name of the class used by the component.
 * @param {Object}   $0.children  Array of children to be rendered inside the notice list.
 * @return {Object}                The rendered notices list.
 */
function SnackbarList( {
	notices,
	className,
	children,
	onRemove = noop,
	onRemove2 = noop,
} ) {
	const isReducedMotion = useReducedMotion();
	const [ refMap ] = useState( () => new WeakMap() );
	const transitions = useTransition( notices, {
		keys: ( notice ) => notice.id,
		from: { opacity: 0, height: 0 },
		enter: ( item ) => async ( next ) =>
			await next( {
				opacity: 1,
				height: refMap.get( item ).offsetHeight,
			} ),
		leave: () => async ( next ) => {
			await next( { opacity: 0 } );
			await next( { height: 0 } );
		},
		immediate: isReducedMotion,
	} );

	className = clsx( 'components-snackbar-list', className );
	const removeNotice = ( notice ) => () => {
		onRemove( notice.id );
		// To be removed when we're no longer using core/notices2.
		onRemove2( notice.id );
	};

	return (
		<div className={ className }>
			{ children }
			{ transitions( ( style, notice ) => (
				<animated.div style={ style }>
					<div
						className="components-snackbar-list__notice-container"
						ref={ ( ref ) => ref && refMap.set( notice, ref ) }
					>
						<Snackbar
							{ ...omit( notice, [ 'content' ] ) }
							onRemove={ removeNotice( notice ) }
						>
							{ notice.content }
						</Snackbar>
					</div>
				</animated.div>
			) ) }
		</div>
	);
}

export default SnackbarList;
