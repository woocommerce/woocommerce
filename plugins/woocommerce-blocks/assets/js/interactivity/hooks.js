/**
 * External dependencies
 */
import { h, options, createContext } from 'preact';
import { useRef } from 'preact/hooks';

/**
 * Internal dependencies
 */
import { store } from './wpx';

// Main context.
const context = createContext( {} );

// WordPress Directives.
const directives = {};
export const directive = ( name, cb ) => {
	directives[ name ] = cb;
};

// WordPress Components.
const components = {};
export const component = ( name, Comp ) => {
	components[ name ] = Comp;
};

// Resolve the path to some property of the wpx object.
const resolve = ( path, context ) => {
	let current = { ...store, context };
	path.split( '.' ).forEach( ( p ) => ( current = current[ p ] ) );
	return current;
};

// Generate the evaluate function.
const getEvaluate =
	( { ref } = {} ) =>
	( path, extraArgs = {} ) => {
		const value = resolve( path, extraArgs.context );
		return typeof value === 'function'
			? value( {
					state: store.state,
					...( ref !== undefined ? { ref } : {} ),
					...extraArgs,
			  } )
			: value;
	};

// Directive wrapper.
const WpDirective = ( { type, wp, props: originalProps } ) => {
	const ref = useRef( null );
	const element = h( type, { ...originalProps, ref, _wrapped: true } );
	const props = { ...originalProps, children: element };
	const evaluate = getEvaluate( { ref: ref.current } );
	const directiveArgs = { directives: wp, props, element, context, evaluate };

	for ( const d in wp ) {
		const wrapper = directives[ d ]?.( directiveArgs );
		if ( wrapper !== undefined ) props.children = wrapper;
	}

	return props.children;
};

// Preact Options Hook called each time a vnode is created.
const old = options.vnode;
options.vnode = ( vnode ) => {
	const type = vnode.type;
	const wp = vnode.props.wp;

	if ( typeof type === 'string' && type.startsWith( 'wp-' ) ) {
		vnode.props.children = h(
			components[ type ],
			{ ...vnode.props, context, evaluate: getEvaluate() },
			vnode.props.children
		);
	} else if ( wp ) {
		const props = vnode.props;
		delete props.wp;
		if ( ! props._wrapped ) {
			vnode.props = { type: vnode.type, wp, props };
			vnode.type = WpDirective;
		} else {
			delete props._wrapped;
		}
	}

	if ( old ) old( vnode );
};
