<?php
class WC_Status_Text {
    private function prefix( string $status ): string { return 'Status: ' . $status; }
    public function pending_label() { return $this->prefix( 'pending' ); }
}
