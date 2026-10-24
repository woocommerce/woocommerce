<?php
class WC_Status_Text {
    private function prefix( $status ) { return 'Status: ' . $status; }
    public function pending_label() { return $this->prefix( 'pending' ); }
}
