<?php
final class WC_Eval_Mailer_Service {
    private $emails = null;
    public function get_emails() {
        if ( null === $this->emails ) { $this->emails = WC()->mailer()->get_emails(); }
        return $this->emails;
    }
}
add_action( 'init', static function() { $GLOBALS['wc_eval_mailer'] = new WC_Eval_Mailer_Service(); } );
