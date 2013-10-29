(function($) {
$(document).ready(function() {

	// Lightbox
	$("a.zoom").prettyPhoto({
		social_tools: false,
		theme: 'pp_woocommerce',
		horizontal_padding: 20,
		opacity: 0.8,
		deeplinking: false
	});
	$("a[rel^='prettyPhoto']").prettyPhoto({
		social_tools: false,
		theme: 'pp_woocommerce',
		horizontal_padding: 20,
		opacity: 0.8,
		deeplinking: false
	});

});
})(jQuery);