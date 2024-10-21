/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import { getAdminLink } from '@woocommerce/settings';
import { queueRecordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */

/**
 * Returns a promise and resolves when the post begins to publish.
 *
 * @return {Promise} Promise for overlay existence.
 */
const saveStarted = () => {
	if (
		! document
			.querySelector( '.editor-post-publish-button' )
			.classList.contains( 'is-busy' )
	) {
		const promise = new Promise( ( resolve ) => {
			window.requestAnimationFrame( resolve );
		} );
		return promise.then( () => saveStarted() );
	}

	return Promise.resolve( true );
};

/**
 * Returns a promise and resolves when the post has been saved and notices have shown.
 *
 * @return {Promise} Promise for overlay existence.
 */
const saveCompleted = () => {
	if (
		document
			.querySelector( '.editor-post-publish-button' )
			.classList.contains( 'is-busy' )
	) {
		const promise = new Promise( ( resolve ) => {
			window.requestAnimationFrame( resolve );
		} );
		return promise.then( () => saveCompleted() );
	}

	return Promise.resolve( true );
};

/**
 * Displays a notice on page save and updates the hompage options.
 */
const onboardingHomepageNotice = () => {
	const saveButton = document.querySelector( '.editor-post-publish-button' );
	if ( saveButton.classList.contains( 'is-clicked' ) ) {
		return;
	}

	saveButton.classList.add( 'is-clicked' );

	saveCompleted().then( () => {
		const notificationType =
			document.querySelector( '.components-snackbar__content' ) !== null
				? 'snackbar'
				: 'default';

		dispatch( 'core/notices' ).removeNotice( 'SAVE_POST_NOTICE_ID' );
		dispatch( 'core/notices' ).createSuccessNotice(
			__( '🏠 Nice work creating your store’s homepage!', 'woocommerce' ),
			{
				id: 'WOOCOMMERCE_ONBOARDING_HOME_PAGE_NOTICE',
				type: notificationType,
				actions: [
					{
						label: __( 'Continue setup.', 'woocommerce' ),
						onClick: () => {
							queueRecordEvent(
								'tasklist_appearance_continue_setup',
								{}
							);
							window.location.href = getAdminLink(
								'admin.php?page=wc-admin&task=appearance'
							);
						},
					},
				],
			}
		);
	} );
};

domReady( () => {
	const publishButton = document.querySelector(
		'.editor-post-publish-button'
	);
	if ( publishButton ) {
		publishButton.addEventListener(
			'click',
			saveStarted().then( () => onboardingHomepageNotice() )
		);
	}
} );
