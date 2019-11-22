/** @format */
/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import domReady from '@wordpress/dom-ready';

/**
 * WooCommerce dependencies
 */
import { getAdminLink } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import { queueRecordEvent } from 'lib/tracks';

/**
 * Returns a promise and resolves when the post begins to publish.
 *
 * @return {Promise} Promise for overlay existence.
 */
const saveStarted = () => {
	if ( document.querySelector( '.editor-post-publish-button' ) === null ) {
		const promise = new Promise( resolve => {
			requestAnimationFrame( resolve );
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
	if ( null === document.querySelector( '.post-publish-panel__postpublish' ) ) {
		const promise = new Promise( resolve => {
			requestAnimationFrame( resolve );
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
		const postId = document.querySelector( '#post_ID' ).value;
		const notificationType =
			null !== document.querySelector( '.components-snackbar__content' ) ? 'snackbar' : 'default';

		apiFetch( {
			path: '/wc-admin/options',
			method: 'POST',
			data: {
				show_on_front: 'page',
				page_on_front: postId,
			},
		} );

		dispatch( 'core/notices' ).removeNotice( 'SAVE_POST_NOTICE_ID' );
		dispatch( 'core/notices' ).createSuccessNotice(
			__( 'Your homepage was published.', 'woocommerce-admin' ),
			{
				id: 'WOOCOMMERCE_ONBOARDING_HOME_PAGE_NOTICE',
				type: notificationType,
				actions: [
					{
						label: __( 'Continue setup.', 'woocommerce-admin' ),
						onClick: () => {
							queueRecordEvent( 'tasklist_appearance_continue_setup', {} );
							window.location = getAdminLink( 'admin.php?page=wc-admin&task=appearance' );
						},
					},
				],
			}
		);
	} );
};

domReady( () => {
	const publishButton = document.querySelector( '.editor-post-publish-panel__toggle' );
	if ( publishButton ) {
		publishButton.addEventListener( 'click', function() {
			saveStarted().then( () => {
				const confirmButton = document.querySelector( '.editor-post-publish-button' );
				if ( confirmButton ) {
					confirmButton.addEventListener( 'click', onboardingHomepageNotice );
				}
			} );
		} );
	}
} );
