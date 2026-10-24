<?php
final class WC_Eval_Mailer_Service {
    private $load;
    public function __construct() {
        $this->load = static function() {
            static $emails = null;
            if ( null === $emails ) { $emails = WC()->mailer()->get_emails(); }
            return $emails;
        };
    }
    public function get_emails() { return ( $this->load )(); }
}
add_action( 'init', static function() { $GLOBALS['wc_eval_mailer'] = new WC_Eval_Mailer_Service(); } );
