/**
 * Tests for keeping the Product short description editor alive across metabox moves.
 *
 * Moving a postbox detaches and re-inserts its DOM node, which reloads the
 * TinyMCE iframe and wipes the editor document. See
 * https://github.com/woocommerce/woocommerce/issues/32113.
 */

global.jQuery = jest.fn();

const {
	teardown: teardownShortDescriptionEditor,
	restore: restoreShortDescriptionEditor,
} = require( '../meta-boxes-product' );

/**
 * Install a TinyMCE stub that mimics the parts of the real API this code leans on.
 *
 * The important fidelity detail is that remove() flushes the editor's content
 * into the textarea unconditionally, because TinyMCE's Editor.remove() calls
 * save() whenever the editor still has a body, and for a textarea target that
 * write is not gated by the is_removing flag. That is what makes the Visual-mode
 * path work without an explicit save(), and what would clobber the user's typing
 * in Text mode if the snapshot/restore pair were removed.
 *
 * @param {Object}  options            Stub options.
 * @param {boolean} options.hidden     Whether the editor is in "Text" mode.
 * @param {string}  options.content    Content the editor would flush on removal.
 * @param {boolean} options.hasEditor  Whether an editor instance exists at all.
 * @return {Object} The installed tinymce stub.
 */
function stubTinyMce( { hidden = false, content = '', hasEditor = true } = {} ) {
	// TinyMCE caches the element it was bound to, so this deliberately captures
	// the node now rather than looking the id up later.
	const target = document.querySelector( '#postexcerpt #excerpt' );
	let editor = hasEditor
		? { isHidden: () => hidden, getElement: () => target }
		: null;

	const tinymce = {
		get: jest.fn( () => editor ),
		init: jest.fn(),
		execCommand: jest.fn( ( command ) => {
			if ( 'mceRemoveEditor' === command ) {
				if ( target ) {
					target.value = content;
				}

				editor = null;
			}
		} ),
	};

	window.tinymce = tinymce;

	return tinymce;
}

/**
 * Add a copy of the postbox carrying a duplicate id, as core's sortable helper
 * does while a postbox is being dragged.
 *
 * @param {string} value Value to put in the duplicate textarea.
 * @return {HTMLElement} The inserted copy.
 */
function insertDragHelperClone( value ) {
	const postbox = document.querySelector( '#postexcerpt' );
	const clone = postbox.cloneNode( true );

	clone.removeAttribute( 'id' );

	const cloned = clone.querySelector( 'textarea' );
	cloned.value = value;
	// The clone goes earlier in tree order, so a browser resolves the duplicate
	// id to it rather than to the real textarea. jsdom keeps returning the
	// original, so make getElementById behave the way a browser would; without
	// this the tests would pass even when the code looks the id up itself.
	postbox.parentElement.insertBefore( clone, postbox );
	jest.spyOn( document, 'getElementById' ).mockImplementation( ( id ) =>
		'excerpt' === id ? cloned : null
	);

	return clone;
}

/**
 * Render the markup wp_editor() produces for the short description box.
 *
 * @param {string} value Initial textarea value.
 */
function renderEditorMarkup( value = '' ) {
	document.body.innerHTML =
		'<div id="sortables">' +
		'<div id="postexcerpt">' +
		'<textarea id="excerpt" aria-hidden="true"></textarea>' +
		'</div>' +
		'</div>';
	document.querySelector( '#postexcerpt #excerpt' ).value = value;
}

describe( 'Product short description editor across metabox moves', () => {
	beforeEach( () => {
		// WordPress always prints these alongside a wp_editor() instance.
		window.tinyMCEPreInit = { mceInit: { excerpt: { selector: '#excerpt' } } };
	} );

	afterEach( () => {
		// Clear any torn-down state before removing the stubs, since the module
		// keeps that flag between tests.
		restoreShortDescriptionEditor();

		jest.restoreAllMocks();
		delete window.tinymce;
		delete window.tinyMCEPreInit;
		document.body.innerHTML = '';
	} );

	describe( 'Text mode', () => {
		test( 'keeps what the user typed in the textarea', () => {
			renderEditorMarkup( 'typed in the textarea' );
			// The hidden editor still holds older content and would flush it.
			stubTinyMce( { hidden: true, content: 'stale editor content' } );

			teardownShortDescriptionEditor();

			expect( document.querySelector( '#postexcerpt #excerpt' ).value ).toBe(
				'typed in the textarea'
			);
		} );

		test( 'leaves re-initialization to core', () => {
			renderEditorMarkup( 'typed in the textarea' );
			const tinymce = stubTinyMce( { hidden: true, content: 'stale' } );

			teardownShortDescriptionEditor();
			restoreShortDescriptionEditor();

			expect( tinymce.init ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'Visual mode', () => {
		test( 'carries the editor content into the textarea', () => {
			renderEditorMarkup( '' );
			stubTinyMce( { content: '<p>written in the editor</p>' } );

			teardownShortDescriptionEditor();

			expect( document.querySelector( '#postexcerpt #excerpt' ).value ).toBe(
				'<p>written in the editor</p>'
			);
		} );

		test( 'rebuilds the editor from its own stored settings', () => {
			renderEditorMarkup( '' );
			const tinymce = stubTinyMce( { content: 'content' } );
			const excerptSettings = { selector: '#excerpt', toolbar1: 'bold' };
			window.tinyMCEPreInit = {
				mceInit: {
					excerpt: excerptSettings,
					// A later-initialized editor must not supply the settings.
					content: { selector: '#content', toolbar1: 'italic' },
				},
			};

			teardownShortDescriptionEditor();
			restoreShortDescriptionEditor();

			expect( tinymce.init ).toHaveBeenCalledWith( excerptSettings );
		} );

		test( 'stops hiding the textarea from assistive tech while it is visible', () => {
			renderEditorMarkup( '' );
			stubTinyMce( { content: 'content' } );

			teardownShortDescriptionEditor();

			expect(
				document.querySelector( '#postexcerpt #excerpt' ).hasAttribute( 'aria-hidden' )
			).toBe( false );
		} );
	} );

	// While a postbox is dragged, core's sortable helper is a clone of it that
	// carries a second element with the same id, so looking the id up in the
	// document resolves to the wrong node. The code has to ask the editor which
	// element it owns instead.
	describe( 'while a drag helper clone duplicates the id', () => {
		test( 'takes the textarea from the editor rather than the document', () => {
			renderEditorMarkup( 'typed in the textarea' );
			stubTinyMce( { hidden: true, content: 'stale editor content' } );
			const clone = insertDragHelperClone( 'clone value' );

			teardownShortDescriptionEditor();

			expect( document.querySelector( '#postexcerpt #excerpt' ).value ).toBe(
				'typed in the textarea'
			);
			expect( clone.querySelector( 'textarea' ).value ).toBe(
				'clone value'
			);
		} );

		test( 'leaves the clone untouched when clearing aria-hidden', () => {
			renderEditorMarkup( '' );
			stubTinyMce( { content: 'content' } );
			const clone = insertDragHelperClone( '' );

			teardownShortDescriptionEditor();

			expect(
				document.querySelector( '#postexcerpt #excerpt' ).hasAttribute( 'aria-hidden' )
			).toBe( false );
			expect(
				clone.querySelector( 'textarea' ).hasAttribute( 'aria-hidden' )
			).toBe( true );
		} );
	} );

	describe( 'when there is nothing to tear down', () => {
		test( 'does nothing without a TinyMCE instance', () => {
			renderEditorMarkup( 'untouched' );
			const tinymce = stubTinyMce( { hasEditor: false } );

			expect( () => teardownShortDescriptionEditor() ).not.toThrow();
			expect( tinymce.execCommand ).not.toHaveBeenCalled();
			expect( document.querySelector( '#postexcerpt #excerpt' ).value ).toBe(
				'untouched'
			);
		} );

		test( 'does nothing when TinyMCE is unavailable', () => {
			renderEditorMarkup( 'untouched' );

			expect( () => teardownShortDescriptionEditor() ).not.toThrow();
			expect( document.querySelector( '#postexcerpt #excerpt' ).value ).toBe(
				'untouched'
			);
		} );

		test( 'restore is a no-op when no teardown happened', () => {
			renderEditorMarkup( '' );
			const tinymce = stubTinyMce( { content: 'content' } );

			restoreShortDescriptionEditor();

			expect( tinymce.init ).not.toHaveBeenCalled();
			expect( tinymce.execCommand ).not.toHaveBeenCalled();
		} );
	} );
} );
