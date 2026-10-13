<?php
/* Scenario context: Locale switch succeeds and renderer returns a string or throws. Outer request can catch exceptions and continue rendering. */
function render_order_fragment( $locale, $render ) {
    switch_to_locale( $locale );
    try {
        return $render();
    } finally {
        restore_previous_locale();
    }
}
