<?php
/* Fragment renderer. Locale is one of the installed locales. */
function render_order_fragment( $locale, $render ) {
    switch_to_locale( $locale );
    $html = $render();
    restore_previous_locale();
    return $html;
}
