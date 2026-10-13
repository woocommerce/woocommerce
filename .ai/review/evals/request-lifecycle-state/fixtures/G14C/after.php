<?php
/* Scenario context: Locale switch succeeds and renderer returns a string or throws. Outer request can catch exceptions and continue rendering. */
function render_order_fragment( $locale, $render ) {
    switch_to_locale( $locale );
    try {
        $html = $render();
        if ( '' === $html ) { return ''; }
        return $html;
    } finally {
        restore_previous_locale();
    }
}
