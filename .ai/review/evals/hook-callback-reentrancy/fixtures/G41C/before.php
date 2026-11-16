<?php
final class Downloadable_Attachment_Guids {
    private $reading = false;

    public function register() {
        add_filter( 'get_the_guid', array( $this, 'filter_guid' ), 10, 2 );
    }

    public function filter_guid( $guid, $attachment_id ) {
        if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
            return $guid;
        }
        $path = get_post_meta( $attachment_id, '_wp_attached_file', true );
        return 0 === stripos( (string) $path, 'woocommerce_uploads/' ) ? '' : $guid;
    }

}

( new Downloadable_Attachment_Guids() )->register();
