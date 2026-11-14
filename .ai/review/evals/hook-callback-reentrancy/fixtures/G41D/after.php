<?php
final class Attachment_Guid_Scheme {
    public function register() {
        add_filter( 'get_the_guid', array( $this, 'filter_guid' ), 10, 2 );
    }

    public function filter_guid( $guid, $attachment_id ) {
        return is_string( $guid ) && 0 === strpos( $guid, 'legacy-media://' )
            ? 'media://' . substr( $guid, strlen( 'legacy-media://' ) )
            : $guid;
    }
}

( new Attachment_Guid_Scheme() )->register();
