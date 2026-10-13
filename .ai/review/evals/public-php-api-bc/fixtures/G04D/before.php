<?php
/* Scenario context: Synthetic production fixture. Released subclasses implement rows returning array. No subclass defines get_entry_count, which is new to this fixture ecosystem. */
abstract class WC_Export_Base {
    abstract public function rows();
}
