/**
 * External dependencies
 */
import { Admin as CoreAdmin } from '@wordpress/e2e-test-utils-playwright';

export class Admin extends CoreAdmin {
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
			.getByLabel( 'Add Pattern' )
			// Keep WP v6.7 compatibility.
			.or( this.page.getByLabel( 'Add New Pattern' ) )
			.click();
		await this.page
			.getByRole( 'menuitem', { name: 'Add Pattern' } )
			// Keep WP v6.7 compatibility.
			.or(
				this.page.getByRole( 'menuitem', { name: 'Add New Pattern' } )
			)
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
}
