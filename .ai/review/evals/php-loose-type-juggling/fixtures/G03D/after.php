<?php
/* Scenario context: Synthetic production fixture. Class shown is complete. prefix is private and only pending_label calls it with literal pending. Public return stays unchanged. */
class WC_Status_Text {
    private function prefix( string $status ): string { return 'Status: ' . $status; }
    public function pending_label() { return $this->prefix( 'pending' ); }
}
