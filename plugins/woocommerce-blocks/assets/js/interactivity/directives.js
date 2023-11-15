import {
	useContext,
	useMemo,
	useEffect,
	useLayoutEffect,
	useRef,
} from 'preact/hooks';
import { deepSignal, peek } from 'deepsignal';
import { useSignalEffect } from './utils';
import { directive } from './hooks';
import { prefetch, navigate } from './router';

const isObject = ( item ) =>
	item && typeof item === 'object' && ! Array.isArray( item );

const mergeDeepSignals = ( target, source, overwrite ) => {
	for ( const k in source ) {
		if ( isObject( peek( target, k ) ) && isObject( peek( source, k ) ) ) {
			mergeDeepSignals(
				target[ `$${ k }` ].peek(),
				source[ `$${ k }` ].peek(),
				overwrite
			);
		} else if ( overwrite || typeof peek( target, k ) === 'undefined' ) {
			target[ `$${ k }` ] = source[ `$${ k }` ];
		}
	}
};

export default () => {
	// data-wc-context
	directive(
		'context',
		( {
			directives: {
				context: { default: newContext },
			},
			props: { children },
			context: inheritedContext,
		} ) => {
			const { Provider } = inheritedContext;
			const inheritedValue = useContext( inheritedContext );
			const currentValue = useRef( deepSignal( {} ) );
			currentValue.current = useMemo( () => {
				const newValue = deepSignal( newContext );
				mergeDeepSignals( newValue, inheritedValue );
				mergeDeepSignals( currentValue.current, newValue, true );
				return currentValue.current;
			}, [ newContext, inheritedValue ] );

			return (
				<Provider value={ currentValue.current }>{ children }</Provider>
			);
		},
		{ priority: 5 }
	);

	// data-wc-effect--[name]
	directive( 'effect', ( { directives: { effect }, context, evaluate } ) => {
		const contextValue = useContext( context );
		Object.values( effect ).forEach( ( path ) => {
			useSignalEffect( () => {
				return evaluate( path, { context: contextValue } );
			} );
		} );
	} );

	// data-wc-layout-init--[name]
	directive(
		'layout-init',
		( {
			directives: { 'layout-init': layoutInit },
			context,
			evaluate,
		} ) => {
			const contextValue = useContext( context );
			Object.values( layoutInit ).forEach( ( path ) => {
				useLayoutEffect( () => {
					return evaluate( path, { context: contextValue } );
				}, [] );
			} );
		}
	);

	// data-wc-init--[name]
	directive( 'init', ( { directives: { init }, context, evaluate } ) => {
		const contextValue = useContext( context );
		Object.values( init ).forEach( ( path ) => {
			useEffect( () => {
				return evaluate( path, { context: contextValue } );
			}, [] );
		} );
	} );

	// data-wc-on--[event]
	directive( 'on', ( { directives: { on }, element, evaluate, context } ) => {
		const contextValue = useContext( context );
		const events = new Map();
		Object.entries( on ).forEach( ( [ name, path ] ) => {
			const event = name.split( '--' )[ 0 ];
			if ( ! events.has( event ) ) events.set( event, new Set() );
			events.get( event ).add( path );
		} );
		events.forEach( ( paths, event ) => {
			element.props[ `on${ event }` ] = ( event ) => {
				paths.forEach( ( path ) => {
					evaluate( path, { event, context: contextValue } );
				} );
			};
		} );
	} );

	// data-wc-class--[classname]
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
					const currentClass = element.props.class || '';
					const classFinder = new RegExp(
						`(^|\\s)${ name }(\\s|$)`,
						'g'
					);
					if ( ! result )
						element.props.class = currentClass
							.replace( classFinder, ' ' )
							.trim();
					else if ( ! classFinder.test( currentClass ) )
						element.props.class = currentClass
							? `${ currentClass } ${ name }`
							: name;

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

	// data-wc-bind--[attribute]
	directive(
		'bind',
		( { directives: { bind }, element, context, evaluate } ) => {
			const contextValue = useContext( context );
			Object.entries( bind )
				.filter( ( n ) => n !== 'default' )
				.forEach( ( [ attribute, path ] ) => {
					const result = evaluate( path, {
						context: contextValue,
					} );
					element.props[ attribute ] = result;

					// This seems necessary because Preact doesn't change the attributes
					// on the hydration, so we have to do it manually. It doesn't need
					// deps because it only needs to do it the first time.
					useEffect( () => {
						// aria- and data- attributes have no boolean representation.
						// A `false` value is different from the attribute not being
						// present, so we can't remove it.
						// We follow Preact's logic: https://github.com/preactjs/preact/blob/ea49f7a0f9d1ff2c98c0bdd66aa0cbc583055246/src/diff/props.js#L131C24-L136
						if ( result === false && attribute[ 4 ] !== '-' ) {
							element.ref.current.removeAttribute( attribute );
						} else {
							element.ref.current.setAttribute(
								attribute,
								result === true && attribute[ 4 ] !== '-'
									? ''
									: result
							);
						}
					}, [] );
				} );
		}
	);

	// data-wc-navigation-link
	directive(
		'navigation-link',
		( {
			directives: {
				'navigation-link': { default: link },
			},
			props: { href },
			element,
		} ) => {
			useEffect( () => {
				// Prefetch the page if it is in the directive options.
				if ( link?.prefetch ) {
					prefetch( href );
				}
			} );

			// Don't do anything if it's falsy.
			if ( link !== false ) {
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

	// data-wc-show
	directive(
		'show',
		( {
			directives: {
				show: { default: show },
			},
			element,
			evaluate,
			context,
		} ) => {
			const contextValue = useContext( context );

			if ( ! evaluate( show, { context: contextValue } ) )
				element.props.children = (
					<template>{ element.props.children }</template>
				);
		}
	);

	// data-wc-ignore
	directive(
		'ignore',
		( {
			element: {
				type: Type,
				props: { innerHTML, ...rest },
			},
		} ) => {
			// Preserve the initial inner HTML.
			const cached = useMemo( () => innerHTML, [] );
			return (
				<Type
					dangerouslySetInnerHTML={ { __html: cached } }
					{ ...rest }
				/>
			);
		}
	);

	// data-wc-text
	directive(
		'text',
		( {
			directives: {
				text: { default: text },
			},
			element,
			evaluate,
			context,
		} ) => {
			const contextValue = useContext( context );
			element.props.children = evaluate( text, {
				context: contextValue,
			} );
		}
	);
};
