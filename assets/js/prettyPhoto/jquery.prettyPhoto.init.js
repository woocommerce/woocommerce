(function($) {
$(document).ready(function() {

	// Hide review form - it will be in a lightbox
	$('#review_form_wrapper').hide();

	// Lightbox
	$("a.zoom").prettyPhoto({
		social_tools: false,
		theme: 'pp_woocommerce',
		horizontal_padding: 40,
		opacity: 0.9
	});
	$("a.show_review_form").prettyPhoto({
		social_tools: false,
		theme: 'pp_woocommerce',
		horizontal_padding: 40,
		opacity: 0.9
	});
	$("a[rel^='prettyPhoto']").prettyPhoto({
		social_tools: false,
		theme: 'pp_woocommerce',
		horizontal_padding: 40,
		opacity: 0.9
	});

	// Open review form lightbox if accessed via anchor
	if( window.location.hash == '#review_form' ) {
		$('a.show_review_form').trigger('click');
	}

});
})(jQuery);