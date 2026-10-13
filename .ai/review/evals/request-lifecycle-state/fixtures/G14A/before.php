<?php
/* Scenario context: Supported locales are provided; switch_to_locale succeeds. An extension render callback may throw; the outer request handler catches it and renders another fragment in the original request locale. */
function render_order_fragment( $locale, $render ) {
    switch_to_locale( $locale );
    try {
        return $render();
    } finally {
        restore_previous_locale();
    }
}
