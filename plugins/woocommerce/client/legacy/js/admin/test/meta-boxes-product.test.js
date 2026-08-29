global.jQuery = jest.fn();

const {
	teardown,
	restore,
	bindPostboxEvents,
} = require( '../meta-boxes-product' );

const $ = function ( target ) {
	const element =
		typeof target === 'string' ? document.querySelector( target ) : target;
	const listeners = [];

	return {
		on: function ( namespacedEvent, handler ) {
			const event = namespacedEvent.split( '.' )[ 0 ];
			const listener = ( domEvent ) => handler( domEvent, domEvent.detail );

			element.addEventListener( event, listener );
			listeners.push( { event, listener } );
			return this;
		},
		off: function () {
			listeners.forEach( ( { event, listener } ) => {
				element.removeEventListener( event, listener );
			} );
		},
		is: function ( selector ) {
			return element.matches( selector );
		},
	};
};

function renderEditor( value = '' ) {
	document.body.innerHTML =
		'<div id="poststuff"><div id="postexcerpt">' +
		'<button class="handle-order-higher" aria-disabled="false">' +
		'<span>Move</span></button>' +
		'<textarea id="excerpt" aria-hidden="true"></textarea>' +
		'</div></div>';

	const textarea = document.querySelector( '#excerpt' );
	textarea.value = value;
	return textarea;
}

function installTinyMce( {
	hidden = false,
	savedContent = '',
	deferInit = false,
} = {} ) {
	const textarea = document.querySelector( '#excerpt' );
	const resolveInitializations = [];
	const createEditor = ( isHidden ) => ( {
		getElement: () => textarea,
		isHidden: () => isHidden,
		setHidden: ( value ) => ( isHidden = value ),
	} );
	let editor = createEditor( hidden );

	window.tinymce = {
		get: jest.fn( () => editor ),
		init: jest.fn( () => {
			const initializedEditor = ( editor = createEditor( false ) );

			if ( ! deferInit ) {
				return Promise.resolve( [ editor ] );
			}

			return new Promise( ( resolve ) => {
				resolveInitializations.push( () =>
					resolve( [ initializedEditor ] )
				);
			} );
		} ),
		execCommand: jest.fn( () => {
			textarea.value = savedContent;
			editor = null;
		} ),
		resolveInit: ( index ) => resolveInitializations[ index ](),
	};

	return window.tinymce;
}

describe( 'Product short description editor across metabox moves', () => {
	let unbindPostboxEvents;

	beforeEach( () => {
		unbindPostboxEvents = null;
		window.tinyMCEPreInit = {
			mceInit: { excerpt: { selector: '#excerpt' } },
		};
		window.switchEditors = {
			go: jest.fn( () => {
				const editor = window.tinymce.get( 'excerpt' );
				editor.getElement().value = '<p>normalized</p>';
				editor.setHidden( true );
			} ),
		};
	} );

	afterEach( async () => {
		if ( unbindPostboxEvents ) {
			unbindPostboxEvents();
		}

		await restore();
		jest.useRealTimers();
		jest.restoreAllMocks();
		delete window.tinymce;
		delete window.tinyMCEPreInit;
		delete window.switchEditors;
		document.body.innerHTML = '';
	} );

	test( 'preserves Visual content and rebuilds from the excerpt settings', async () => {
		const textarea = renderEditor();
		const settings = window.tinyMCEPreInit.mceInit.excerpt;
		const tinymce = installTinyMce( {
			savedContent: '<p>written in the editor</p>',
		} );

		teardown();
		expect( textarea.value ).toBe( '<p>written in the editor</p>' );
		expect( textarea.hasAttribute( 'aria-hidden' ) ).toBe( false );

		await restore();
		expect( tinymce.init ).toHaveBeenCalledWith( settings );
	} );

	test( 'preserves raw Text mode through rapid consecutive moves', async () => {
		const content = '<custom-element>raw</custom-element>\n';
		const textarea = renderEditor( content );
		const originalSetup = jest.fn();
		const settings = window.tinyMCEPreInit.mceInit.excerpt;
		settings.setup = originalSetup;
		const tinymce = installTinyMce( {
			hidden: true,
			savedContent: 'stale editor content',
			deferInit: true,
		} );

		teardown();
		expect( textarea.value ).toBe( content );
		const firstRestore = restore();
		teardown();
		const secondRestore = restore();

		tinymce.resolveInit( 0 );
		await firstRestore;
		expect( window.switchEditors.go ).not.toHaveBeenCalled();
		expect( textarea.value ).toBe( content );
		tinymce.resolveInit( 1 );
		await secondRestore;

		expect( tinymce.get( 'excerpt' ).isHidden() ).toBe( true );
		expect( window.switchEditors.go ).toHaveBeenCalledTimes( 1 );
		expect( window.switchEditors.go ).toHaveBeenCalledWith(
			'excerpt',
			'html'
		);
		expect( textarea.value ).toBe( content );
		expect( tinymce.init.mock.calls[ 0 ][ 0 ] ).toBe( settings );
		expect( tinymce.init.mock.calls[ 1 ][ 0 ] ).toBe( settings );
		expect( settings.setup ).toBe( originalSetup );
	} );

	test( 'uses the editor textarea when a drag helper duplicates its id', () => {
		const textarea = renderEditor( 'real content' );
		const tinymce = installTinyMce( {
			hidden: true,
			savedContent: 'stale editor content',
		} );
		const clone = document.querySelector( '#postexcerpt' ).cloneNode( true );
		clone.removeAttribute( 'id' );
		clone.querySelector( 'textarea' ).value = 'clone content';
		document.body.prepend( clone );

		teardown();

		expect( tinymce.execCommand ).toHaveBeenCalledWith(
			'mceRemoveEditor',
			false,
			'excerpt'
		);
		expect( textarea.value ).toBe( 'real content' );
		expect( clone.querySelector( 'textarea' ).value ).toBe( 'clone content' );
	} );

	test( 'restores through the order-button and sortable event paths', async () => {
		jest.useFakeTimers();
		renderEditor( 'content' );
		const tinymce = installTinyMce();
		unbindPostboxEvents = bindPostboxEvents( $ );
		const button = document.querySelector( '.handle-order-higher' );
		button.addEventListener( 'click', ( event ) => event.stopPropagation() );

		button.querySelector( 'span' ).click();

		expect( tinymce.execCommand ).toHaveBeenCalledTimes( 1 );
		jest.runOnlyPendingTimers();
		await Promise.resolve();
		expect( tinymce.init ).toHaveBeenCalledTimes( 1 );

		const poststuff = document.querySelector( '#poststuff' );
		const ui = { item: $( document.querySelector( '#postexcerpt' ) ) };

		poststuff.dispatchEvent( new CustomEvent( 'sortstart', { detail: ui } ) );
		poststuff.dispatchEvent( new CustomEvent( 'sortstop', { detail: ui } ) );
		await Promise.resolve();

		expect( tinymce.execCommand ).toHaveBeenCalledTimes( 2 );
		expect( tinymce.init ).toHaveBeenCalledTimes( 2 );
	} );
} );
