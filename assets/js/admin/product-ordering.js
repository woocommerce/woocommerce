/**
 * Based on Simple Page Ordering by 10up (http://wordpress.org/extend/plugins/simple-page-ordering/)
 *
 * Modified - products have no children (non hierarchical)
 */
( function( $ ){
$( document ).ready(function(){
	$('table.widefat tbody th, table.widefat tbody td').css('cursor','move');

	$("table.widefat tbody").sortable({
		items: 'tr:not(.inline-edit-row)',
		cursor: 'move',
		axis: 'y',
		containment: 'table.widefat',
		scrollSensitivity: 40,
		helper: function(event, ui) {
			ui.each(function() { $(this).width($(this).width()); });
			return ui;
		},
		start: function(event, ui) {
			ui.placeholder.children().each(function(){
				var $original = ui.item.children().eq( ui.placeholder.children().index(this) ),
					$this = $( this );

				$.each( $original[0].attributes, function( k, attr ){
					$this.attr( attr.name, attr.value );
				} );
			});
			if ( ! ui.item.hasClass('alternate') ) {
				ui.item.css( 'background-color', '#ffffff' );
			}
			ui.item.children('td,th').css('border-bottom-width','0');
			ui.item.css( 'outline', '1px solid #dfdfdf' );
		},
		stop: function(event, ui) {
			ui.item.removeAttr('style');
			ui.item.children('td,th').css('border-bottom-width','1px');
		},
		update: function(event, ui) {
			$('table.widefat tbody th, table.widefat tbody td').css('cursor','default');
			$("table.widefat tbody").sortable('disable');

			var postid = ui.item.find('.check-column input').val();	// this post id
			var postparent = ui.item.find('.post_parent').html(); // post parent

			var prevpostid = ui.item.prev().find('.check-column input').val();
			var nextpostid = ui.item.next().find('.check-column input').val();

			// show spinner
			ui.item.find('.check-column input').hide().after('<img alt="processing" src="images/wpspin_light.gif" class="waiting" style="margin-left: 6px;" />');

			// go do the sorting stuff via ajax
			$.post( ajaxurl, { action: 'woocommerce_product_ordering', id: postid, previd: prevpostid, nextid: nextpostid }, function(response){
				$.each(response, function(key,value) { $('#inline_'+key+' .menu_order').html(value); });
				ui.item.find('.check-column input').show().siblings('img').remove();
				$('table.widefat tbody th, table.widefat tbody td').css('cursor','move');
				$("table.widefat tbody").sortable('enable');
			});

			// fix cell colors
			$( 'table.widefat tbody tr' ).each(function(){
				var i = $('table.widefat tbody tr').index(this);
				if ( i%2 === 0 ){
					$(this).addClass('alternate');
				} else {
					$(this).removeClass('alternate');
				}
			});
		}
	});
});
}( jQuery ) );
