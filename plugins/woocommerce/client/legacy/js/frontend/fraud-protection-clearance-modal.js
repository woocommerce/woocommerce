/**
 * Fraud Protection OTP Modal
 *
 * Handles the display and interaction of the OTP challenge modal.
 */
(function($) {
	'use strict';

	const FraudProtectionModal = {
		modal: null,
		modalShown: false,
		currentStep: 1,
		challengeId: null,
		resendCountdown: 0,
		resendTimer: null,
		isProcessing: false,

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


		// Create modal HTML
		this.createModal();

		// Bind button actions
		this.bindActions();

		// Show modal immediately on page load for pending sessions
		this.showModal();
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
		 * TODO: move rendering to the server side.
		 */
		createModal: function() {
			const modalHTML = `
				<div class="wc-fraud-protection-modal-overlay" style="display: none;">
					<div class="wc-fraud-protection-modal">
						<div class="wc-fraud-protection-modal-content">
							<h2>Email Verification Required</h2>

							<!-- Step 1: Email Input -->
							<div class="wc-fraud-protection-step wc-fraud-protection-step-1">
								<p>To continue, please verify your email address.</p>
								<div class="wc-fraud-protection-form-group">
									<label for="wc-fraud-protection-email">Email Address</label>
									<input
										type="email"
										id="wc-fraud-protection-email"
										class="wc-fraud-protection-input"
										placeholder="Enter your email address"
										value=""
										required
									/>
								</div>
								<div class="wc-fraud-protection-error-message" style="display: none;"></div>
								<button type="button" class="button wc-fraud-request-otp">
									Request Verification Code
								</button>
							</div>

							<!-- Step 2: OTP Verification -->
							<div class="wc-fraud-protection-step wc-fraud-protection-step-2" style="display: none;">
								<p>We've sent a 6-digit verification code to your email address.</p>
								<div class="wc-fraud-protection-form-group">
									<label for="wc-fraud-protection-otp">Verification Code</label>
									<input
										type="text"
										id="wc-fraud-protection-otp"
										class="wc-fraud-protection-input wc-fraud-protection-otp-input"
										placeholder="Enter 6-digit code"
										maxlength="6"
										pattern="[0-9]{6}"
										inputmode="numeric"
										autocomplete="one-time-code"
										required
									/>
								</div>
								<div class="wc-fraud-protection-attempts" style="display: none;"></div>
								<div class="wc-fraud-protection-error-message" style="display: none;"></div>
								<button type="button" class="button wc-fraud-verify-otp">
									Verify Code
								</button>
								<div class="wc-fraud-protection-resend-container">
									<a href="#" class="wc-fraud-resend-otp">Resend code</a>
									<span class="wc-fraud-resend-countdown" style="display: none;"></span>
								</div>
							</div>
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

			// Prefill email if available
			if (wcFraudProtection.userEmail) {
				$('#wc-fraud-protection-email').val(wcFraudProtection.userEmail);
			}
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

			// Focus on email input
			setTimeout(function() {
				$('#wc-fraud-protection-email').focus();
			}, 350);
		},

		/**
		 * Hide the modal.
		 */
		hideModal: function() {
			this.modal.fadeOut(300);
			this.modalShown = false;
			$('body').removeClass('wc-fraud-protection-modal-open');

			// Clear any timers
			this.stopResendCountdown();
		},

		/**
		 * Switch to a specific step.
		 *
		 * @param {number} step - Step number (1 or 2)
		 */
		switchToStep: function(step) {
			this.currentStep = step;

			if (step === 1) {
				$('.wc-fraud-protection-step-1').show();
				$('.wc-fraud-protection-step-2').hide();
				this.clearErrors();
			} else if (step === 2) {
				$('.wc-fraud-protection-step-1').hide();
				$('.wc-fraud-protection-step-2').show();
				this.clearErrors();

				// Auto-focus on OTP input
				setTimeout(function() {
					$('#wc-fraud-protection-otp').focus();
				}, 100);
			}
		},

		/**
		 * Show loading state.
		 */
		showLoading: function() {
			$('.wc-fraud-protection-step').hide();
			$('.wc-fraud-protection-loading').show();
		},

		/**
		 * Hide loading state.
		 */
		hideLoading: function() {
			$('.wc-fraud-protection-loading').hide();
			$('.wc-fraud-protection-step-' + this.currentStep).show();
		},


		/**
		 * Bind button actions.
		 */
		bindActions: function() {
			const self = this;

			// Request OTP button
			$('.wc-fraud-request-otp').on('click', function() {
				self.requestOtp();
			});

			// Verify OTP button
			$('.wc-fraud-verify-otp').on('click', function() {
				self.verifyOtp();
			});

			// Resend OTP link
			$('.wc-fraud-resend-otp').on('click', function(e) {
				e.preventDefault();
				if (!$(this).hasClass('disabled') && self.resendCountdown === 0) {
					self.resendOtp();
				}
			});

			// Enter key submission
			$('#wc-fraud-protection-email').on('keypress', function(e) {
				if (e.which === 13) {
					e.preventDefault();
					self.requestOtp();
				}
			});

			$('#wc-fraud-protection-otp').on('keypress', function(e) {
				if (e.which === 13) {
					e.preventDefault();
					self.verifyOtp();
				}
			});

			// Auto-format OTP input (numbers only)
			$('#wc-fraud-protection-otp').on('input', function() {
				this.value = this.value.replace(/[^0-9]/g, '');
			});

			// Close on ESC key (only if not processing)
			$(document).on('keydown', function(e) {
				if (e.key === 'Escape' && self.modalShown && !self.isProcessing) {
					// Don't allow closing if session is blocked
					if (wcFraudProtection.sessionStatus === 'blocked') {
						return;
					}
					self.hideModal();
				}
			});
		},

		/**
		 * Request OTP code via REST API.
		 */
		requestOtp: function() {
			const self = this;
			const email = $('#wc-fraud-protection-email').val().trim();

			// Validate email
			if (!email) {
				this.showError('Please enter your email address.');
				return;
			}

			if (!this.isValidEmail(email)) {
				this.showError('Please enter a valid email address.');
				return;
			}

			// Prevent multiple submissions
			if (this.isProcessing) {
				return;
			}

			this.isProcessing = true;
			this.clearErrors();
			this.showLoading();

			// Make REST API call
			$.ajax({
				url: wcFraudProtection.restUrl + '/request',
				method: 'POST',
				data: JSON.stringify({
					email: email
				}),
				contentType: 'application/json',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', wcFraudProtection.restNonce);
				},
				success: function(response) {
					self.isProcessing = false;

					if (response.challenge_id) {
						// OTP challenge initiated
						self.challengeId = response.challenge_id;
						self.switchToStep(2);
						self.hideLoading();
						self.startResendCountdown(30);
					} else if (response.session_status === 'allowed') {
						// Session was allowed without challenge
						wcFraudProtection.sessionStatus = 'allowed';
						self.handleSuccess();
					} else if (response.session_status === 'blocked') {
						// Session was blocked
						wcFraudProtection.sessionStatus = 'blocked';
						self.redirectToShop();
					} else {
						self.hideLoading();
						self.showError('Unexpected response from server. Please try again.');
					}
				},
				error: function(xhr) {
					self.isProcessing = false;
					self.hideLoading();

					var errorMessage = 'Failed to send verification code. Please try again.';

					if (xhr.responseJSON && xhr.responseJSON.message) {
						errorMessage = xhr.responseJSON.message;
					}

					self.showError(errorMessage);
				}
			});
		},

		/**
		 * Verify OTP code via REST API.
		 */
		verifyOtp: function() {
			const self = this;
			const otpCode = $('#wc-fraud-protection-otp').val().trim();

			// Validate OTP code
			if (!otpCode) {
				this.showError('Please enter the verification code.');
				return;
			}

			if (otpCode.length !== 6 || !/^\d{6}$/.test(otpCode)) {
				this.showError('Please enter a valid 6-digit code.');
				return;
			}

			if (!this.challengeId) {
				this.showError('Session expired. Please request a new code.');
				this.switchToStep(1);
				return;
			}

			// Prevent multiple submissions
			if (this.isProcessing) {
				return;
			}

			this.isProcessing = true;
			this.clearErrors();
			this.showLoading();

			// Make REST API call
			$.ajax({
				url: wcFraudProtection.restUrl + '/verify',
				method: 'POST',
				data: JSON.stringify({
					challenge_id: this.challengeId,
					otp_code: otpCode
				}),
				contentType: 'application/json',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', wcFraudProtection.restNonce);
				},
				success: function(response) {
					self.isProcessing = false;

					if (response.success && response.session_status === 'allowed') {
						// Verification successful
						wcFraudProtection.sessionStatus = 'allowed';
						self.handleSuccess();
					} else if (response.session_status === 'blocked') {
						// Session blocked
						wcFraudProtection.sessionStatus = 'blocked';
						self.redirectToShop();
					} else {
						// Verification failed
						self.hideLoading();

						var errorMessage = response.message || 'Verification failed. Please try again.';
						self.showError(errorMessage);

						// Show remaining attempts if available
						if (response.attempts_remaining !== undefined) {
							self.showAttemptsRemaining(response.attempts_remaining);
						}

						// Clear OTP input
						$('#wc-fraud-protection-otp').val('').focus();
					}
				},
				error: function(xhr) {
					self.isProcessing = false;
					self.hideLoading();

					var errorMessage = 'Verification failed. Please try again.';
					var attemptsRemaining = null;

					if (xhr.responseJSON) {
						if (xhr.responseJSON.message) {
							errorMessage = xhr.responseJSON.message;
						}

						if (xhr.responseJSON.data && xhr.responseJSON.data.attempts_remaining !== undefined) {
							attemptsRemaining = xhr.responseJSON.data.attempts_remaining;
						}

						// Handle specific error codes
						if (xhr.responseJSON.code === 'otp_expired') {
							errorMessage = 'Your verification code has expired. Please request a new one.';
							setTimeout(function() {
								self.switchToStep(1);
								self.challengeId = null;
							}, 2000);
						} else if (xhr.responseJSON.code === 'max_attempts') {
							errorMessage = 'Maximum verification attempts reached. Please request a new code.';
							setTimeout(function() {
								self.switchToStep(1);
								self.challengeId = null;
							}, 2000);
						}
					}

					self.showError(errorMessage);

					if (attemptsRemaining !== null) {
						self.showAttemptsRemaining(attemptsRemaining);
					}

					// Clear OTP input
					$('#wc-fraud-protection-otp').val('').focus();
				}
			});
		},

		/**
		 * Resend OTP code via REST API.
		 */
		resendOtp: function() {
			const self = this;

			if (!this.challengeId) {
				this.showError('Session expired. Please start over.');
				this.switchToStep(1);
				return;
			}

			// Prevent multiple submissions
			if (this.isProcessing) {
				return;
			}

			this.isProcessing = true;
			this.clearErrors();
			$('.wc-fraud-resend-otp').addClass('disabled');

			// Make REST API call
			$.ajax({
				url: wcFraudProtection.restUrl + '/resend',
				method: 'POST',
				data: JSON.stringify({
					challenge_id: this.challengeId
				}),
				contentType: 'application/json',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', wcFraudProtection.restNonce);
				},
				success: function(response) {
					self.isProcessing = false;

					if (response.success) {
						self.showSuccess('Verification code resent successfully.');
						self.startResendCountdown(30);

						// Auto-hide success message after 3 seconds
						setTimeout(function() {
							$('.wc-fraud-protection-success-message').fadeOut(300, function() {
								$(this).remove();
							});
						}, 3000);
					} else {
						$('.wc-fraud-resend-otp').removeClass('disabled');
						self.showError(response.message || 'Failed to resend code. Please try again.');
					}
				},
				error: function(xhr) {
					self.isProcessing = false;
					$('.wc-fraud-resend-otp').removeClass('disabled');

					var errorMessage = 'Failed to resend verification code. Please try again.';

					if (xhr.responseJSON && xhr.responseJSON.message) {
						errorMessage = xhr.responseJSON.message;
					}

					// Handle max attempts error
					if (xhr.responseJSON && xhr.responseJSON.code === 'max_attempts') {
						errorMessage = 'Maximum email attempts reached. Please start over.';
						setTimeout(function() {
							self.switchToStep(1);
							self.challengeId = null;
						}, 2000);
					}

					self.showError(errorMessage);
				}
			});
		},

		/**
		 * Handle successful verification.
		 */
		handleSuccess: function() {
			const self = this;

			// Show brief success message
			$('.wc-fraud-protection-modal-content h2').text('Verification Successful');
			$('.wc-fraud-protection-step').hide();
			$('.wc-fraud-protection-loading').hide();
			$('.wc-fraud-protection-modal-content').append(
				'<p class="wc-fraud-protection-success">Your email has been verified successfully.</p>'
			);

			// Auto-dismiss and reload/update after 1 second
			setTimeout(function() {
				self.hideModal();

				if (wcFraudProtection.isCheckout) {
					// Refresh checkout to show payment methods
					$('body').trigger('update_checkout');
				} else {
					// Reload page to enable add to cart
					window.location.reload();
				}
			}, 1000);
		},

		/**
		 * Start resend countdown timer.
		 *
		 * @param {number} seconds - Countdown duration in seconds
		 */
		startResendCountdown: function(seconds) {
			const self = this;

			this.resendCountdown = seconds;
			$('.wc-fraud-resend-otp').addClass('disabled');
			$('.wc-fraud-resend-countdown').show();

			this.updateResendCountdownDisplay();

			this.resendTimer = setInterval(function() {
				self.resendCountdown--;

				if (self.resendCountdown <= 0) {
					self.stopResendCountdown();
				} else {
					self.updateResendCountdownDisplay();
				}
			}, 1000);
		},

		/**
		 * Stop resend countdown timer.
		 */
		stopResendCountdown: function() {
			if (this.resendTimer) {
				clearInterval(this.resendTimer);
				this.resendTimer = null;
			}

			this.resendCountdown = 0;
			$('.wc-fraud-resend-otp').removeClass('disabled');
			$('.wc-fraud-resend-countdown').hide();
		},

		/**
		 * Update resend countdown display.
		 */
		updateResendCountdownDisplay: function() {
			const text = 'Resend available in ' + this.resendCountdown + 's';
			$('.wc-fraud-resend-countdown').text(text);
		},

		/**
		 * Show error message in modal.
		 *
		 * @param {string} message - Error message
		 */
		showError: function(message) {
			this.clearErrors();

			const $errorElement = $('.wc-fraud-protection-step-' + this.currentStep + ' .wc-fraud-protection-error-message');
			$errorElement.html('<p>' + message + '</p>').show();
		},

		/**
		 * Show success message in modal.
		 *
		 * @param {string} message - Success message
		 */
		showSuccess: function(message) {
			this.clearErrors();

			const $stepContainer = $('.wc-fraud-protection-step-' + this.currentStep);

			// Remove any existing success message
			$stepContainer.find('.wc-fraud-protection-success-message').remove();

			// Add new success message after error message div
			$stepContainer.find('.wc-fraud-protection-error-message').after(
				'<div class="wc-fraud-protection-success-message" style="color: #2e7d32; margin-top: 10px;"><p>' + message + '</p></div>'
			);
		},

		/**
		 * Show remaining attempts counter.
		 *
		 * @param {number} attemptsRemaining - Number of attempts remaining
		 */
		showAttemptsRemaining: function(attemptsRemaining) {
			const $attemptsElement = $('.wc-fraud-protection-attempts');

			if (attemptsRemaining > 0) {
				const text = attemptsRemaining === 1
					? '1 attempt remaining'
					: attemptsRemaining + ' attempts remaining';
				$attemptsElement.html('<p>' + text + '</p>').show();
			} else {
				$attemptsElement.html('<p>No attempts remaining</p>').show();
			}
		},

		/**
		 * Clear all error and success messages.
		 */
		clearErrors: function() {
			$('.wc-fraud-protection-error-message').hide().empty();
			$('.wc-fraud-protection-success-message').remove();
			$('.wc-fraud-protection-attempts').hide().empty();
		},

		/**
		 * Validate email format.
		 *
		 * @param {string} email - Email address to validate
		 * @return {boolean} True if valid
		 */
		isValidEmail: function(email) {
			const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			return re.test(email);
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		FraudProtectionModal.init();
	});

})(jQuery);
