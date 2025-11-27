/**
 * Fraud Protection Clearance Modal
 *
 * Handles the display and interaction of the session clearance modal.
 */
(function($) {
	'use strict';

	const FraudProtectionModal = {
		modal: null,
		modalShown: false,

		/**
		 * Initialize the modal functionality.
		 */
		init: function() {
			// If session is allowed, no friction needed
			if (wcFraudProtection.sessionStatus === 'allowed') {
				return;
			}

			// If session is blocked, redirect to shop immediately (no modal)
			if (wcFraudProtection.sessionStatus === 'blocked') {
				this.redirectToShop();
				return;
			}

			// Only show modal for 'pending' sessions
			if (wcFraudProtection.sessionStatus !== 'pending') {
				return;
			}

			// Check if fraud protection should apply to current page
			var applyTo = wcFraudProtection.applyTo || 'both';
			var shouldApplyToCheckout = (applyTo === 'checkout' || applyTo === 'both') && wcFraudProtection.isCheckout;
			var shouldApplyToCart = (applyTo === 'cart' || applyTo === 'both') && wcFraudProtection.isProduct;

			// If neither applies to current page, do nothing
			if (!shouldApplyToCheckout && !shouldApplyToCart) {
				return;
			}

			// Create modal HTML
			this.createModal();

			// Show modal on checkout page automatically (if enabled for checkout)
			if (shouldApplyToCheckout) {
				this.showModal();
			}

			// Intercept add to cart button clicks on product pages (if enabled for cart)
			if (shouldApplyToCart) {
				this.interceptAddToCart();
			}

			// Bind button actions
			this.bindActions();
		},

		/**
		 * Redirect to shop page.
		 */
		redirectToShop: function() {
			if (window.location.href !== wcFraudProtection.shopUrl) {
				window.location.href = wcFraudProtection.shopUrl;
			}
		},

		/**
		 * Create modal HTML and append to body.
		 */
		createModal: function() {
			const modalHTML = `
				<div class="wc-fraud-protection-modal-overlay" style="display: none;">
					<div class="wc-fraud-protection-modal">
						<div class="wc-fraud-protection-modal-content">
							<h2>Session Clearance Required</h2>
							<p>This is a mock fraud protection clearance check. In production, this would perform actual fraud detection.</p>
							<p class="wc-fraud-protection-notice">Please choose an action:</p>
						</div>
						<div class="wc-fraud-protection-actions">
							<button type="button" class="button wc-fraud-allow-session">
								Allow Session
							</button>
							<button type="button" class="button wc-fraud-block-session">
								Block Session
							</button>
						</div>
						<div class="wc-fraud-protection-loading" style="display: none;">
							<span class="spinner is-active"></span>
							<p>Processing...</p>
						</div>
					</div>
				</div>
			`;

			$('body').append(modalHTML);
			this.modal = $('.wc-fraud-protection-modal-overlay');
		},

		/**
		 * Show the modal.
		 */
		showModal: function() {
			if (this.modalShown) {
				return;
			}

			this.modal.fadeIn(300);
			this.modalShown = true;

			// Prevent body scroll when modal is open
			$('body').addClass('wc-fraud-protection-modal-open');
		},

		/**
		 * Hide the modal.
		 */
		hideModal: function() {
			this.modal.fadeOut(300);
			this.modalShown = false;
			$('body').removeClass('wc-fraud-protection-modal-open');
		},

		/**
		 * Intercept add to cart button clicks.
		 */
		interceptAddToCart: function() {
			const self = this;

			// For simple add to cart buttons
			$(document).on('click', '.add_to_cart_button, .single_add_to_cart_button', function(e) {
				if (wcFraudProtection.sessionStatus !== 'allowed') {
					e.preventDefault();
					e.stopImmediatePropagation();
					self.showModal();
					return false;
				}
			});

			// For variable products form submission
			$('form.cart').on('submit', function(e) {
				if (wcFraudProtection.sessionStatus !== 'allowed') {
					e.preventDefault();
					e.stopImmediatePropagation();
					self.showModal();
					return false;
				}
			});
		},

		/**
		 * Bind button actions.
		 */
		bindActions: function() {
			const self = this;

			// Allow button
			$('.wc-fraud-allow-session').on('click', function() {
				self.performAction('allow');
			});

			// Block button
			$('.wc-fraud-block-session').on('click', function() {
				self.performAction('block');
			});

			// Close on ESC key
			$(document).on('keydown', function(e) {
				if (e.key === 'Escape' && self.modalShown) {
					// Don't allow closing if session is blocked
					if (wcFraudProtection.sessionStatus === 'blocked') {
						return;
					}
					self.hideModal();
				}
			});
		},

		/**
		 * Perform allow/block action via REST API.
		 *
		 * @param {string} action - 'allow' or 'block'
		 */
		performAction: function(action) {
			const self = this;

			// Show loading state
			$('.wc-fraud-protection-actions').hide();
			$('.wc-fraud-protection-loading').show();

			// Make REST API call
			$.ajax({
				url: wcFraudProtection.restUrl,
				method: 'POST',
				data: JSON.stringify({
					action: action
				}),
				contentType: 'application/json',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', wcFraudProtection.restNonce);
				},
				success: function(response) {
					if (response.success) {
						wcFraudProtection.sessionStatus = response.status;

						if (action === 'allow') {
							// Session allowed - close modal and continue
							self.hideModal();

							// Reload page to enable functionality
							if (wcFraudProtection.isCheckout) {
								// Refresh checkout
								$('body').trigger('update_checkout');
							} else {
								// On product page, allow add to cart to proceed
								window.location.reload();
							}
						} else {
							// Session blocked - redirect to shop
							window.location.href = wcFraudProtection.shopUrl;
						}
					} else {
						self.showError('Failed to process action. Please try again.');
					}
				},
				error: function(xhr, status, error) {
					console.error('Fraud protection error:', error);
					self.showError('An error occurred. Please refresh the page and try again.');
				}
			});
		},

		/**
		 * Show error message in modal.
		 *
		 * @param {string} message - Error message
		 */
		showError: function(message) {
			$('.wc-fraud-protection-loading').hide();
			$('.wc-fraud-protection-actions').show();

			const errorHTML = `<p class="wc-fraud-protection-error" style="color: #d63638;">${message}</p>`;
			$('.wc-fraud-protection-modal-content').append(errorHTML);

			// Remove error after 5 seconds
			setTimeout(function() {
				$('.wc-fraud-protection-error').fadeOut(300, function() {
					$(this).remove();
				});
			}, 5000);
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		FraudProtectionModal.init();
	});

})(jQuery);
