<?php
final class Order_Fragment_Renderer {
    public function render( $locale, $render ) {
        return $this->render_fragment( $locale, $render );
    }
    private function render_fragment( $locale, $render ) {
        $switched = switch_to_locale( $locale );
        try {
            return $render();
        } finally {
            if ( $switched ) { restore_previous_locale(); }
        }
    }
}
