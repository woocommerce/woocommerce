/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { Editor, FrontendUtils } from '@woocommerce/e2e-utils';

const selectors = {
	editor: {
		zoomWhileHoveringSetting:
			"xpath=//label[contains(text(), 'Zoom while hovering')]/preceding-sibling::span/input",
		fullScreenOnClickSetting:
			"xpath=//label[contains(text(), 'Open pop-up when clicked')]/preceding-sibling::span/input",
	},
};

export class ProductGalleryPage {
	editor: Editor;
	page: Page;
	frontendUtils: FrontendUtils;
	constructor( {
		editor,
		page,
		frontendUtils,
	}: {
		editor: Editor;
		page: Page;
		frontendUtils: FrontendUtils;
	} ) {
		this.editor = editor;
		this.page = page;
		this.frontendUtils = frontendUtils;
		this.editor = editor;
	}

	async addProductGalleryBlock( { cleanContent = true } ) {
		if ( cleanContent ) {
			await this.editor.setContent( '' );
		}
		await this.editor.insertBlock( {
			name: 'woocommerce/product-gallery',
		} );
	}

	async addAddToCartWithOptionsBlock() {
		await this.editor.insertBlock( {
			name: 'woocommerce/add-to-cart-form',
		} );
	}

	getZoomWhileHoveringSetting() {
		return this.page.locator( selectors.editor.zoomWhileHoveringSetting );
	}

	getFullScreenOnClickSetting() {
		return this.page.locator( selectors.editor.fullScreenOnClickSetting );
	}

	async toggleFullScreenOnClickSetting( enable: boolean ) {
		const button = this.page.locator(
			selectors.editor.fullScreenOnClickSetting
		);
		const isChecked = await button.isChecked();

		// Toggle the checkbox if it's not in the desired state.
		if ( enable && ! isChecked ) {
			await button.click();
		} else if ( ! enable && isChecked ) {
			await button.click();
		}
	}

	async toggleZoomWhileHoveringSetting( enable: boolean ) {
		const button = this.page.locator(
			selectors.editor.zoomWhileHoveringSetting
		);
		const isChecked = await button.isChecked();

		// Toggle the checkbox if it's not in the desired state.
		if ( enable && ! isChecked ) {
			await button.click();
		} else if ( ! enable && isChecked ) {
			await button.click();
		}
	}

	async getViewerBlock( { page }: { page: 'frontend' | 'editor' } ) {
		const blockName = 'woocommerce/product-gallery-large-image';
		if ( page === 'frontend' ) {
			return (
				await this.frontendUtils.getBlockByName( blockName )
			 ).filter( {
				has: this.page.locator( ':visible' ),
			} );
		}
		return this.editor.getBlockByName( blockName );
	}

	// Playwright doesn't have a locator for "carousel" images as all of them
	// are visible from its POV. So we need to use a custom function to get the visible image id.
	async getViewerImageId() {
		const viewerBlockLocator = await this.getViewerBlock( {
			page: 'frontend',
		} );

		// Find the scrollable container
		const container = await viewerBlockLocator
			.locator( '.wc-block-product-gallery-large-image__container' )
			.elementHandle();
		if ( ! container ) {
			return null;
		}

		// Get all images inside the container
		const images = await viewerBlockLocator
			.locator( 'img' )
			.elementHandles();

		for ( const imgHandle of images ) {
			const isInContainerViewport = await imgHandle.evaluate(
				( img, containerEl ) => {
					const imgRect = (
						img as HTMLElement
					 ).getBoundingClientRect();
					const containerRect = (
						containerEl as HTMLElement
					 ).getBoundingClientRect();

					// Check if the image is fully or mostly visible within the container
					const visibleWidth =
						Math.min( imgRect.right, containerRect.right ) -
						Math.max( imgRect.left, containerRect.left );
					const visibleHeight =
						Math.min( imgRect.bottom, containerRect.bottom ) -
						Math.max( imgRect.top, containerRect.top );

					const isVisible =
						visibleWidth > 0 &&
						visibleHeight > 0 &&
						visibleWidth >= imgRect.width * 0.8 && // at least 80% visible horizontally
						visibleHeight >= imgRect.height * 0.8; // at least 80% visible vertically

					return isVisible;
				},
				container
			);

			if ( isInContainerViewport ) {
				const dataImageId = await imgHandle.getAttribute(
					'data-image-id'
				);
				return dataImageId ?? null;
			}
		}
		return null;
	}

	async getThumbnailsBlock( { page }: { page: 'frontend' | 'editor' } ) {
		const blockName = 'woocommerce/product-gallery-thumbnails';
		if ( page === 'frontend' ) {
			return (
				await this.frontendUtils.getBlockByName( blockName )
			 ).filter( {
				has: this.page.locator( ':visible' ),
			} );
		}
		return this.editor.getBlockByName( blockName );
	}

	async getNextPreviousButtonsBlock( {
		page,
	}: {
		page: 'frontend' | 'editor';
	} ) {
		const blockName =
			'woocommerce/product-gallery-large-image-next-previous';
		if ( page === 'frontend' ) {
			return (
				await this.frontendUtils.getBlockByName( blockName )
			 ).filter( {
				has: this.page.locator( ':visible' ),
			} );
		}
		return this.editor.getBlockByName( blockName );
	}

	async clickNextButton() {
		await this.page.getByRole( 'button', { name: 'Next image' } ).click();
		// Wait for the transition to change
		// eslint-disable-next-line playwright/no-wait-for-timeout, no-restricted-syntax
		await this.page.waitForTimeout( 400 );
	}

	async clickPreviousButton() {
		await this.page
			.getByRole( 'button', { name: 'Previous image' } )
			.click();
		// Wait for the transition to change
		// eslint-disable-next-line playwright/no-wait-for-timeout, no-restricted-syntax
		await this.page.waitForTimeout( 400 );
	}

	async getBlock( { page }: { page: 'frontend' | 'editor' } ) {
		const blockName = 'woocommerce/product-gallery';
		if ( page === 'frontend' ) {
			return (
				await this.frontendUtils.getBlockByName( blockName )
			 ).filter( {
				has: this.page.locator( ':visible' ),
			} );
		}
		return this.editor.getBlockByName( blockName );
	}

	async getAddToCartWithOptionsBlock( {
		page,
	}: {
		page: 'frontend' | 'editor';
	} ) {
		const blockName = 'woocommerce/add-to-cart-form';
		if ( page === 'frontend' ) {
			return (
				await this.frontendUtils.getBlockByName( blockName )
			 ).filter( {
				has: this.page.locator( ':visible' ),
			} );
		}
		return this.editor.getBlockByName( blockName );
	}
}
