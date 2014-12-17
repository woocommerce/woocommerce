jQuery( function ( $ ) {
	$( '#webhook-options #topic' ).on( 'change', function() {
		var current            = $( this ).val(),
			action_event_field = $( '#webhook-options .action_event_field' ),
			custom_topic_field = $( '#webhook-options .custom_topic_field' );

		action_event_field.hide();
		custom_topic_field.hide();

		if ( 'action' === current ) {
			action_event_field.show();
		} else if ( 'custom' === current ) {
			custom_topic_field.show();
		}
	}).change();
});
