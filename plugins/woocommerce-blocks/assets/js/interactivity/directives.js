/**
 * External dependencies
 */

import { useContext, useMemo, useEffect } from 'preact/hooks';
import { useSignalEffect } from '@preact/signals';
import { deepSignal } from 'deepsignal';

/**
 * Internal dependencies
 */
import { directive } from './hooks';
import { prefetch, navigate, hasClientSideTransitions } from './router';

// Until useSignalEffects is fixed:
// https://github.com/preactjs/signals/issues/228
const raf = window.requestAnimationFrame;
const tick = () => new Promise( ( r ) => raf( () => raf( r ) ) );

// Check if current page has client-side transitions enabled.
const clientSideTransitions = hasClientSideTransitions( document.head );

export default () => {
	// wp-context
	directive(
		'context',
		( {
			directives: {
				context: { default: context },
			},
			props: { children },
			context: { Provider },
		} ) => {
			const signals = useMemo( () => deepSignal( context ), [ context ] );
			return <Provider value={ signals }>{ children }</Provider>;
		}
	);

	// wp-effect:[name]
	directive( 'effect', ( { directives: { effect }, context, evaluate } ) => {
		const contextValue = useContext( context );
		Object.values( effect ).forEach( ( path ) => {
			useSignalEffect( () => {
				evaluate( path, { context: contextValue } );
			} );
		} );
	} );

	// wp-on:[event]
	directive( 'on', ( { directives: { on }, element, evaluate, context } ) => {
		const contextValue = useContext( context );
		Object.entries( on ).forEach( ( [ name, path ] ) => {
			element.props[ `on${ name }` ] = ( event ) => {
				evaluate( path, { event, context: contextValue } );
			};
		} );
	} );

	// wp-class:[classname]
	directive(
		'class',
		( {
			directives: { class: className },
			element,
			evaluate,
			context,
		} ) => {
			const contextValue = useContext( context );
			Object.keys( className )
				.filter( ( n ) => n !== 'default' )
				.forEach( ( name ) => {
					const result = evaluate( className[ name ], {
						className: name,
						context: contextValue,
					} );
					if ( ! result )
						element.props.class = element.props.class
							.replace(
								new RegExp( `(^|\\s)${ name }(\\s|$)`, 'g' ),
								' '
							)
							.trim();
					else if (
						! new RegExp( `(^|\\s)${ name }(\\s|$)` ).test(
							element.props.class
						)
					)
						element.props.class += ` ${ name }`;

					useEffect( () => {
						// This seems necessary because Preact doesn't change the class names
						// on the hydration, so we have to do it manually. It doesn't need
						// deps because it only needs to do it the first time.
						if ( ! result ) {
							element.ref.current.classList.remove( name );
						} else {
							element.ref.current.classList.add( name );
						}
					}, [] );
				} );
		}
	);

	// wp-bind:[attribute]
	directive(
		'bind',
		( { directives: { bind }, element, context, evaluate } ) => {
			const contextValue = useContext( context );
			Object.entries( bind )
				.filter( ( n ) => n !== 'default' )
				.forEach( ( [ attribute, path ] ) => {
					element.props[ attribute ] = evaluate( path, {
						context: contextValue,
					} );
				} );
		}
	);

	// wp-link
	directive(
		'link',
		( {
			directives: {
				link: { default: link },
			},
			props: { href },
			element,
		} ) => {
			useEffect( () => {
				// Prefetch the page if it is in the directive options.
				if ( clientSideTransitions && link?.prefetch ) {
					prefetch( href );
				}
			} );

			// Don't do anything if it's falsy.
			if ( clientSideTransitions && link !== false ) {
				element.props.onclick = async ( event ) => {
					event.preventDefault();

					// Fetch the page (or return it from cache).
					await navigate( href );

					// Update the scroll, depending on the option. True by default.
					if ( link?.scroll === 'smooth' ) {
						window.scrollTo( {
							top: 0,
							left: 0,
							behavior: 'smooth',
						} );
					} else if ( link?.scroll !== false ) {
						window.scrollTo( 0, 0 );
					}
				};
			}
		}
	);
};
