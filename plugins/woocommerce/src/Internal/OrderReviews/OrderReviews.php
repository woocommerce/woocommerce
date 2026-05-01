<?php
/**
 * OrderReviews wrapper class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderReviews;

/**
 * Single bootstrap entry point for the OrderReviews feature.
 *
 * The WC dependency container instantiates this class and auto-calls
 * `init()` with the listed sub-services as arguments. Each sub-service
 * is in turn constructed and has its own `init()` auto-called, which is
 * where it registers its hooks. The wrapper itself has no work to do
 * beyond existing as a single registration point so that
 * `class-woocommerce.php` does not grow a new line every time another
 * sub-service joins the feature.
 *
 * @internal Just for internal use.
 *
 * @since 10.8.0
 */
class OrderReviews {

	/**
	 * Resolve and bootstrap the feature's sub-services.
	 *
	 * Listing each sub-service as an `init()` argument tells the WC
	 * container to construct it and auto-call its own `init()` method.
	 *
	 * @internal
	 *
	 * @param Scheduler         $scheduler  Schedules the delayed review-request email.
	 * @param Endpoint          $endpoint   Renders the tokenised landing page.
	 * @param SubmissionHandler $submission Handles the AJAX form submission.
	 */
	final public function init( Scheduler $scheduler, Endpoint $endpoint, SubmissionHandler $submission ): void {
		// No body needed: the container has already constructed and
		// initialised every arg, so their hooks are live by this point.
		unset( $scheduler, $endpoint, $submission );
	}
}
