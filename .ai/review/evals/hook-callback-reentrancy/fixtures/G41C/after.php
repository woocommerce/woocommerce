<?php
final class Downloadable_Attachment_Guids {
    private $reading = false;

    public function register() {
        add_filter( 'get_the_guid', array( $this, 'filter_guid' ), 10, 2 );
    }

    public function filter_guid( $guid, $attachment_id ) {
        if ( $this->reading || $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
            return $guid;
        }
        $this->reading = true;
        try {
            $path = get_post_meta( $attachment_id, '_wp_attached_file', true );
            return 0 === stripos( (string) $path, 'woocommerce_uploads/' ) ? '' : $guid;
        } finally {
            $this->reading = false;
        }
    }

}

( new Downloadable_Attachment_Guids() )->register();

// A media plugin resolves virtual attachments while filtering the stored file path.
add_filter( 'get_post_metadata', static function ( $value, $object_id, $meta_key, $single ) {
    if ( '_wp_attached_file' === $meta_key && $single ) {
        $guid = get_the_guid( $object_id );
        if ( is_string( $guid ) && 0 === strpos( $guid, 'rml://' ) ) {
            return 'shared/library-file.jpg';
        }
    }
    return $value;
}, 10, 4 );
