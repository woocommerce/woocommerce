<?php
/* Scenario context: Synthetic production fixture. Extensions subclass WC_Item_Writer and override delete_items to delete associated extension rows. Store insert/delete methods operate on core rows only. */
class WC_Item_Writer {
    protected function delete_items( $store, $id ) { $store->delete_items( $id ); }
    public function replace_items( $store, $id, $items ) {
        $this->delete_items( $store, $id );
        $store->insert_items( $id, $items );
    }
}
