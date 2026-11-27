<?php
class WC_Item_Writer {
    protected function delete_items( $store, $id ) { $store->delete_items( $id ); }
    public function replace_items( $store, $id, $items ) {
        $this->delete_items( $store, $id );
        $store->insert_items( $id, $items );
    }
}

class Extension_Item_Writer extends WC_Item_Writer {
    protected function delete_items( $store, $id ) {
        $store->delete_extension_items( $id );
        parent::delete_items( $store, $id );
    }
}
