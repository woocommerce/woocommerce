jQuery(document).ready(function($) {
	
	// Tabs
	$('.woocommerce_tabs .panel').hide();
	
	$('.woocommerce_tabs ul.tabs li a').click(function(){
		
		var $tab = $(this);
		var $tabs_wrapper = $tab.closest('.woocommerce_tabs');
		
		$('ul.tabs li', $tabs_wrapper).removeClass('active');
		$('div.panel', $tabs_wrapper).hide();
		$('div' + $tab.attr('href')).show();
		$tab.parent().addClass('active');
		
		return false;	
	});
	
	$('.woocommerce_tabs').each(function() {
		var hash = window.location.hash;
		if (hash.toLowerCase().indexOf("comment-") >= 0) {
			$('ul.tabs li.reviews_tab a', $(this)).click();
		} else {
			$('ul.tabs li:first a', $(this)).click();
		}
	});
	
	// Star ratings for comments
	$('#rating').hide().before('<p class="stars"><span><a class="star-1" href="#">1</a><a class="star-2" href="#">2</a><a class="star-3" href="#">3</a><a class="star-4" href="#">4</a><a class="star-5" href="#">5</a></span></p>');
	
	$('p.stars a').click(function(){
		var $star = $(this);
		$('#rating').val( $star.text() );
		$('p.stars a').removeClass('active');
		$star.addClass('active');
		return false;
	});
	
	$('#review_form #submit').live('click', function(){
		var rating = $('#rating').val();
		
		if ( $('#rating').size() > 0 && !rating && woocommerce_params.review_rating_required == 'yes' ) {
			alert(woocommerce_params.required_rating_text);
			return false;
		}
	});
	
});