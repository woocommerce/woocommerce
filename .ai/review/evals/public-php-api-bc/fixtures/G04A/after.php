<?php
/* Scenario context: Synthetic production fixture. WC_Export_Feed shipped in a previous release. External classes implement rows only. The complete core implementor is shown. */
interface WC_Export_Feed {
    public function rows();
    public function get_entry_count();
}
class WC_Core_Export_Feed implements WC_Export_Feed {
    public function rows() { return array(); }
    public function get_entry_count() { return 0; }
}
