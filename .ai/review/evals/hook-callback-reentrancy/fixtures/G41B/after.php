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
        $path = get_post_meta( $attachment_id, '_wp_attached_file', true );
        $this->reading = false;
        return 0 === stripos( (string) $path, 'woocommerce_uploads/' ) ? '' : $guid;
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

// A provider can fail once; the request catches the error and later renders another attachment.
add_filter( 'get_post_metadata', static function ( $value, $object_id, $meta_key, $single ) {
    static $first = true;
    if ( $first && '_wp_attached_file' === $meta_key && $single ) {
        $first = false;
        throw new RuntimeException( 'Media provider temporarily unavailable' );
    }
    return $value;
}, 5, 4 );

final class Attachment_Guid_Renderer {
    public function render( $attachment_ids ) {
        $guids = array();
        foreach ( $attachment_ids as $attachment_id ) {
            try {
                $guids[] = get_the_guid( $attachment_id );
            } catch ( RuntimeException $error ) {
                $guids[] = '';
            }
        }
        return $guids;
    }
}
