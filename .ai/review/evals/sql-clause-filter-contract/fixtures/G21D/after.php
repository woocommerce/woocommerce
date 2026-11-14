<?php
/* Internal maintenance report; query receives trusted SQL order terms. Ties have no specified ordering. */
class WC_Internal_Catalog_Order { private function build_orderby( $orderby ) { global $wpdb; return empty( $orderby ) ? "{$wpdb->posts}.ID ASC" : $orderby . ", {$wpdb->posts}.ID ASC"; } public function query( $orderby ) { global $wpdb; $order = $this->build_orderby( $orderby ); return $wpdb->get_col( "SELECT ID FROM {$wpdb->posts}" . ( $order ? ' ORDER BY ' . $order : '' ) ); } }
