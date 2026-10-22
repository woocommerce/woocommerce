/**
 * Internal dependencies
 */
import { initDomTracking } from '../dom-tracking';
import { recordEvent } from '..';

jest.mock( '..', () => ( {
	recordEvent: jest.fn(),
	isEventTrackingEnabled: () => true,
} ) );

// Sidebar tabs as rendered by WordPress 7.0 and 7.1 (components Tabs) and 7.2 (Base UI Tabs).
const SIDEBAR_TABS_WP70 = `
	<div class="components-panel__header interface-complementary-area-header editor-sidebar__panel-tabs">
		<div role="tablist">
			<button type="button" role="button" data-tab-id="edit-post/document">Email</button>
			<button type="button" role="button" data-tab-id="edit-post/block">Block</button>
		</div>
	</div>`;
const SIDEBAR_TABS_WP71 = `
	<div class="components-panel__header interface-complementary-area-header editor-sidebar__panel-tabs">
		<div role="tablist">
			<button type="button" role="tab" data-tab-id="edit-post/document">Email</button>
			<button type="button" role="tab" data-tab-id="edit-post/block">Block</button>
		</div>
	</div>`;
const SIDEBAR_TABS_WP72 = `
	<div class="components-panel__header interface-complementary-area-header editor-sidebar__panel-tabs">
		<div role="tablist">
			<button type="button" role="tab" id="base-ui-:rl:"><span>Email</span></button>
			<button type="button" role="tab" id="base-ui-:rm:"><span>Block</span></button>
		</div>
	</div>`;

// Header "Options" toggle as rendered by WordPress 7.1 (DropdownMenu) and 7.2 (Base UI Menu).
const optionsToggleWp71 = ( isOpened: boolean ) => `
	<div class="editor-header__settings">
		<button type="button" aria-expanded="${ isOpened }" class="components-button components-dropdown-menu__toggle is-compact has-icon${
			isOpened ? ' is-opened' : ''
		}" aria-label="Options"><svg></svg></button>
	</div>`;
const optionsToggleWp72 = ( isOpened: boolean ) => `
	<div class="editor-header__settings">
		<button type="button" aria-haspopup="menu" aria-expanded="${ isOpened }" class="components-button is-compact has-icon" aria-label="Options"><svg></svg></button>
	</div>`;

// Header "View" (preview) toggle as rendered by WordPress 7.2 (Base UI Menu).
const previewToggleWp72 = ( isOpened: boolean ) => `
	<button type="button" aria-haspopup="menu" aria-expanded="${ isOpened }" class="components-button editor-preview-dropdown__toggle is-compact has-icon" aria-label="View"><svg></svg></button>`;

function click( selector: string ) {
	const element = document.querySelector( selector );
	if ( ! element ) {
		throw new Error( `No element matches ${ selector }` );
	}
	element.dispatchEvent(
		new window.MouseEvent( 'click', { bubbles: true } )
	);
}

describe( 'initDomTracking', () => {
	beforeAll( () => {
		initDomTracking();
	} );

	afterEach( () => {
		document.body.innerHTML = '';
		jest.mocked( recordEvent ).mockClear();
	} );

	describe.each( [
		[ 'WordPress 7.0', SIDEBAR_TABS_WP70 ],
		[ 'WordPress 7.1', SIDEBAR_TABS_WP71 ],
		[ 'WordPress 7.2', SIDEBAR_TABS_WP72 ],
	] )( 'sidebar tabs (%s)', ( _version, markup ) => {
		it( 'records the document tab selection', () => {
			document.body.innerHTML = markup;
			click( '[role="tablist"] > :first-child' );
			expect( recordEvent ).toHaveBeenCalledTimes( 1 );
			expect( recordEvent ).toHaveBeenCalledWith(
				'sidebar_tab_selected',
				{ tab: 'document' }
			);
		} );

		it( 'records the block tab selection', () => {
			document.body.innerHTML = markup;
			click( '[role="tablist"] > :last-child' );
			expect( recordEvent ).toHaveBeenCalledTimes( 1 );
			expect( recordEvent ).toHaveBeenCalledWith(
				'sidebar_tab_selected',
				{ tab: 'block' }
			);
		} );
	} );

	it( 'ignores tabs outside the editor sidebar header', () => {
		document.body.innerHTML = `
			<div class="block-editor-inserter__tabs">
				<div role="tablist"><button type="button" role="tab">Blocks</button></div>
			</div>`;
		click( '[role="tab"]' );
		expect( recordEvent ).not.toHaveBeenCalled();
	} );

	describe.each( [
		[ 'WordPress 7.1', optionsToggleWp71 ],
		[ 'WordPress 7.2', optionsToggleWp72 ],
	] )( 'header options toggle (%s)', ( _version, markup ) => {
		it.each( [ false, true ] )(
			'records the toggle with isOpened %s',
			( isOpened ) => {
				document.body.innerHTML = markup( isOpened );
				click( '[aria-label="Options"] svg' );
				expect( recordEvent ).toHaveBeenCalledTimes( 1 );
				expect( recordEvent ).toHaveBeenCalledWith(
					'header_more_menu_dropdown_toggle',
					{ isOpened }
				);
			}
		);
	} );

	it( 'does not record the block toolbar options toggle as the header menu', () => {
		document.body.innerHTML = `
			<div class="block-editor-block-toolbar">
				<button type="button" class="components-button components-dropdown-menu__toggle" aria-label="Options"></button>
			</div>`;
		click( '[aria-label="Options"]' );
		expect( recordEvent ).not.toHaveBeenCalled();
	} );

	it.each( [ false, true ] )(
		'records the WordPress 7.2 preview toggle with isOpened %s',
		( isOpened ) => {
			document.body.innerHTML = previewToggleWp72( isOpened );
			click( '.editor-preview-dropdown__toggle' );
			expect( recordEvent ).toHaveBeenCalledWith(
				'header_preview_dropdown_clicked',
				{ isOpened }
			);
		}
	);
} );
