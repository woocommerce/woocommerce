<?php
/* Scenario context: Service construction occurs on init, but this getter is invoked only when sending an actual email. No constructor calls the getter. WC mailer initialization is unchanged and remains lazy; returned array is unchanged. */
class WC_Eval_Mailer_Service { private $emails = null; public function get_emails() { if ( null === $this->emails ) { $this->emails = WC()->mailer()->get_emails(); } return $this->emails; } }
