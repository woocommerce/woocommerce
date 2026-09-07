describe( 'Product short description editor after an iframe unload', () => {
	let onEditorInit;
	let editor;
	let editorWindow;
	let textarea;

	beforeEach( () => {
		jest.useFakeTimers();
		jest.resetModules();
		global.jQuery = jest.fn( () => ( {
			on: ( event, handler ) => {
				if ( 'tinymce-editor-init' === event ) {
					onEditorInit = handler;
				}
			},
		} ) );
		require( '../meta-boxes-product' );

		document.body.innerHTML =
			'<textarea id="excerpt" aria-hidden="true"></textarea>';
		textarea = document.querySelector( '#excerpt' );
		editorWindow = new window.EventTarget();
		editor = {
			id: 'excerpt',
			getWin: () => editorWindow,
			getElement: () => textarea,
			isHidden: jest.fn( () => false ),
			save: jest.fn( () => {
				textarea.value = '<p>Unsaved visual content</p>';
			} ),
			remove: jest.fn( () => {
				textarea.value = 'Stale iframe content';
			} ),
		};
		window.tinymce = {
			get: jest.fn( () => editor ),
			init: jest.fn(),
		};
		window.tinyMCEPreInit = {
			mceInit: { excerpt: { selector: '#excerpt', setup: jest.fn() } },
		};
	} );

	afterEach( () => {
		jest.clearAllTimers();
		jest.useRealTimers();
		delete global.jQuery;
		delete window.tinymce;
		delete window.tinyMCEPreInit;
		document.body.innerHTML = '';
	} );

	function unloadEditor() {
		editorWindow.dispatchEvent( new window.Event( 'pagehide' ) );
	}

	test( 'saves before the iframe unloads and rebuilds after the move using the original settings', () => {
		onEditorInit( {}, editor );
		unloadEditor();

		expect( editor.save ).toHaveBeenCalledTimes( 1 );
		expect( editor.remove ).not.toHaveBeenCalled();
		expect( window.tinymce.init ).not.toHaveBeenCalled();

		// Simulate the document being lost before the deferred restoration runs.
		textarea.value = '';
		jest.runOnlyPendingTimers();

		expect( editor.remove ).toHaveBeenCalledTimes( 1 );
		expect( textarea.value ).toBe( '<p>Unsaved visual content</p>' );
		expect( textarea.hasAttribute( 'aria-hidden' ) ).toBe( false );
		expect( window.tinymce.init ).toHaveBeenCalledWith(
			window.tinyMCEPreInit.mceInit.excerpt
		);
	} );

	test( 'preserves raw Text-mode content without initializing a hidden editor', () => {
		const content = '<custom-element>Raw text</custom-element>\n';
		textarea.value = content;
		editor.isHidden.mockReturnValue( true );
		onEditorInit( {}, editor );

		unloadEditor();
		jest.runOnlyPendingTimers();

		expect( editor.save ).not.toHaveBeenCalled();
		expect( editor.remove ).toHaveBeenCalledTimes( 1 );
		expect( textarea.value ).toBe( content );
		expect( window.tinymce.init ).not.toHaveBeenCalled();
	} );

	test( 'uses the original textarea when a drag helper duplicates its ID', () => {
		const clone = textarea.cloneNode( true );
		clone.value = 'Drag helper';
		document.body.prepend( clone );
		onEditorInit( {}, editor );

		unloadEditor();
		jest.runOnlyPendingTimers();

		expect( textarea.value ).toBe( '<p>Unsaved visual content</p>' );
		expect( clone.value ).toBe( 'Drag helper' );
	} );

	test( 'does not handle the main product description editor', () => {
		editor.id = 'content';
		onEditorInit( {}, editor );

		unloadEditor();
		jest.runOnlyPendingTimers();

		expect( editor.save ).not.toHaveBeenCalled();
		expect( editor.remove ).not.toHaveBeenCalled();
		expect( window.tinymce.init ).not.toHaveBeenCalled();
	} );

	test.each( [ 'removed', 'replaced' ] )(
		'does not restore an editor that was %s before the deferred callback',
		( change ) => {
			onEditorInit( {}, editor );
			unloadEditor();
			if ( 'removed' === change ) {
				textarea.remove();
			} else {
				window.tinymce.get.mockReturnValue( {} );
			}
			jest.runOnlyPendingTimers();

			expect( editor.remove ).not.toHaveBeenCalled();
			expect( window.tinymce.init ).not.toHaveBeenCalled();
		}
	);

	test( 'handles an iframe unload only once per editor instance', () => {
		onEditorInit( {}, editor );
		unloadEditor();
		unloadEditor();
		jest.runOnlyPendingTimers();

		expect( editor.save ).toHaveBeenCalledTimes( 1 );
		expect( editor.remove ).toHaveBeenCalledTimes( 1 );
		expect( window.tinymce.init ).toHaveBeenCalledTimes( 1 );
	} );
} );
