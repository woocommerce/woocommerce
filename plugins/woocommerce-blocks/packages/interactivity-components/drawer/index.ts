/**
 * External dependencies
 */
import { getContext, store } from '@woocommerce/interactivity';

export type DrawerContext = {
	isInitiallyOpen: boolean;
	isOpen: boolean;
	isOpenContextProperty: string;
};

const getIsOpenFromContext = ( context: DrawerContext, parentContext: any ) => {
	const isOpenContextProperty = context.isOpenContextProperty;

	if ( isOpenContextProperty.split( '::' ).length === 2 ) {
		const [ , property ] = isOpenContextProperty.split( '::' );
		return parentContext[ property.split( '.' )[ 1 ] ];
	}

	return false;
};

const getParentContextPropertyName = ( namespace: string ) => {
	if ( namespace.split( '::' ).length === 2 ) {
		const [ , property ] = namespace.split( '::' );
		return property.split( '.' )[ 1 ];
	}

	return '';
};

const getParentContextNamespace = ( namespace: string ) => {
	if ( namespace.split( '::' ).length === 2 ) {
		return namespace.split( '::' )[ 0 ];
	}

	return '';
};

store( 'woocommerce/interactivity-drawer', {
	state: {
		get slideClasses() {
			const context = getContext< DrawerContext >();
			const parentContext = getContext(
				getParentContextNamespace( context.isOpenContextProperty )
			);
			const isOpen = getIsOpenFromContext( context, parentContext );

			const baseClass =
				'wc-block-components-drawer__screen-overlay wc-block-components-drawer__screen-overlay';
			const slide = isOpen
				? '--with-slide-in'
				: '--with-slide-out wc-block-components-drawer__screen-overlay--is-hidden';

			return `${ baseClass }${ slide }`;
		},
	},

	actions: {
		initialize: () => {
			const context = getContext< DrawerContext >();
			const parentContext = getContext(
				getParentContextNamespace( context.isOpenContextProperty )
			);

			const handleEscapeKey = ( e: KeyboardEvent ) => {
				if ( e.code === 'Escape' && ! e.defaultPrevented ) {
					e.preventDefault();

					const parentContextPropertyName =
						getParentContextPropertyName(
							context.isOpenContextProperty
						);

					parentContext[ parentContextPropertyName ] = false;
				}
			};

			// Attach keydown event listener to document - note that when we update Interactivity API we can use on-window directive to handle this.
			document.addEventListener( 'keydown', handleEscapeKey );

			// Return a cleanup function to remove the event listener
			return () => {
				document.removeEventListener( 'keydown', handleEscapeKey );
			};
		},
	},
} );
