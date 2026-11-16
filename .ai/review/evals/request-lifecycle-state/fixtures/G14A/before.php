<?php
/* Fragment renderer. Locale is one of the installed locales. */
function render_order_fragment( $locale, $render ) {
    switch_to_locale( $locale );
    try {
        return $render();
    } finally {
        restore_previous_locale();
    }
}
