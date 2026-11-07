<?php
final class Order_Fragment_Renderer {
    public function render( $locale, $render ) {
        $switched = switch_to_locale( $locale );
        try {
            return $this->render_fragment( $render );
        } finally {
            if ( $switched ) { restore_previous_locale(); }
        }
    }
    private function render_fragment( $render ) {
        return $render();
    }
}
