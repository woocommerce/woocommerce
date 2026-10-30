/**
 * External dependencies
 */
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { initDomTracking } from '../dom-tracking';
import { recordEvent } from '../event-pipeline';

jest.mock( '../event-pipeline', () => ( {
	recordEvent: jest.fn(),
	isEventTrackingEnabled: () => true,
} ) );

jest.mock( '@wordpress/data', () => ( {
	select: jest.fn(),
} ) );

// The editor switches its sidebar before the click reaches the document.
function setActiveSidebar( sidebar: string | null ) {
	jest.mocked( select ).mockReturnValue( {
		getActiveComplementaryArea: () => sidebar,
	} );
}

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
		it.each( [
			[ ':first-child', 'edit-post/document', 'document' ],
			[ ':last-child', 'edit-post/block', 'block' ],
		] )(
			'records the tab the editor switched to (%s)',
			( tabSelector, sidebar, tab ) => {
				document.body.innerHTML = markup;
				setActiveSidebar( sidebar );
				click( `[role="tablist"] > ${ tabSelector }` );
				expect( select ).toHaveBeenCalledWith( 'core/interface' );
				expect( recordEvent ).toHaveBeenCalledTimes( 1 );
				expect( recordEvent ).toHaveBeenCalledWith(
					'sidebar_tab_selected',
					{ tab }
				);
			}
		);

		it( 'records nothing when another sidebar is active', () => {
			document.body.innerHTML = markup;
			setActiveSidebar( 'woocommerce/email-editor-styles' );
			click( '[role="tablist"] > :first-child' );
			expect( recordEvent ).not.toHaveBeenCalled();
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

	// WordPress 7.1 menus are open or closed by the time the click reaches the document.
	// WordPress 7.2 menus update `aria-expanded` after the click event.
	describe( 'header menu toggles', () => {
		beforeEach( () => {
			jest.useFakeTimers();
		} );

		afterEach( () => {
			jest.useRealTimers();
		} );

		it.each( [ false, true ] )(
			'records the WordPress 7.1 options toggle with isOpened %s',
			( isOpened ) => {
				document.body.innerHTML = optionsToggleWp71( isOpened );
				click( '[aria-label="Options"] svg' );
				jest.runAllTimers();
				expect( recordEvent ).toHaveBeenCalledTimes( 1 );
				expect( recordEvent ).toHaveBeenCalledWith(
					'header_more_menu_dropdown_toggle',
					{ isOpened }
				);
			}
		);

		it.each( [
			[
				'options',
				optionsToggleWp72,
				'[aria-label="Options"]',
				'header_more_menu_dropdown_toggle',
			],
			[
				'preview',
				previewToggleWp72,
				'.editor-preview-dropdown__toggle',
				'header_preview_dropdown_clicked',
			],
		] )(
			'records the WordPress 7.2 %s toggle state after the click',
			( _toggle, markup, selector, eventName ) => {
				for ( const isOpened of [ true, false ] ) {
					document.body.innerHTML = markup( ! isOpened );
					click( selector );
					document
						.querySelector( selector )
						?.setAttribute( 'aria-expanded', String( isOpened ) );
					jest.runAllTimers();
					expect( recordEvent ).toHaveBeenLastCalledWith( eventName, {
						isOpened,
					} );
				}
				expect( recordEvent ).toHaveBeenCalledTimes( 2 );
			}
		);

		it( 'does not record the block toolbar options toggle as the header menu', () => {
			document.body.innerHTML = `
				<div class="block-editor-block-toolbar">
					<button type="button" class="components-button components-dropdown-menu__toggle" aria-label="Options"></button>
				</div>`;
			click( '[aria-label="Options"]' );
			jest.runAllTimers();
			expect( recordEvent ).not.toHaveBeenCalled();
		} );
	} );
} );
