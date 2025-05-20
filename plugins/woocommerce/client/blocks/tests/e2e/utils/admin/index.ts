/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import {
	Admin as CoreAdmin,
	PageUtils,
	Editor,
} from '@wordpress/e2e-test-utils-playwright';

type AdminConstructorProps = {
	page: Page;
	pageUtils: PageUtils;
	editor: Editor;
	wpCoreVersion: number;
};

export class Admin extends CoreAdmin {
	wpCoreVersion: number;

	constructor( {
		page,
		pageUtils,
		editor,
		wpCoreVersion,
	}: AdminConstructorProps ) {
		super( { page, pageUtils, editor } );
		this.wpCoreVersion = wpCoreVersion;
	}

	async visitWidgetEditor() {
		await this.page.goto( '/wp-admin/widgets.php' );
		await this.page
			.getByRole( 'dialog', { name: 'Welcome to block Widgets' } )
			.getByRole( 'button', { name: 'Close' } )
			.click();
	}

	async createNewPattern( name: string, synced = true ) {
		await this.page.goto( '/wp-admin/site-editor.php?postType=wp_block' );
		await this.page.getByRole( 'button', { name: 'Patterns' } ).click();

		await this.page
			.getByLabel(
				this.wpCoreVersion >= 6.8 ? 'Add Pattern' : 'Add New Pattern'
			)
			.click();

		await this.page
			.getByRole( 'menuitem', {
				name:
					this.wpCoreVersion >= 6.8
						? 'Add Pattern'
						: 'Add New Pattern',
			} )
			.click();

		await this.page.getByLabel( 'Name' ).fill( name );

		if ( ! synced ) {
			// Synced toggle is enabled by default.
			await this.page.getByLabel( 'Synced' ).click();
		}

		await this.page.getByRole( 'button', { name: 'Add' } ).click();

		const welcomePopUp = async () => {
			await this.page
				.getByRole( 'button', {
					name: 'Get started',
				} )
				.click();
		};

		const editorLoaded = async () => {
			await this.page
				.getByRole( 'heading', {
					name: 'pattern',
				} )
				.waitFor();
		};

		await Promise.any( [ welcomePopUp(), editorLoaded() ] );
	}

	/**
	 * Clicks the 'Save changes' button on an admin page and waits for it to become disabled to ensure the page is saved.
	 */
	async saveAdminPage() {
		const saveButton = this.page.getByRole( 'button', {
			name: 'Save changes',
		} );
		await saveButton.click();
		await saveButton.isDisabled();
	}
}
