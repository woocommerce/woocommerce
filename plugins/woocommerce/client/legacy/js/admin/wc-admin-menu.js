document.addEventListener( 'DOMContentLoaded', () => {

	const addonsSubMenuLinks = document.querySelectorAll(
		'#adminmenu' +
		' #toplevel_page_woocommerce' +
		' .wp-submenu' +
		' li a[href="admin.php?page=wc-addons"]'
	);

	addonsSubMenuLinks.forEach( ( el, index ) => {
		if ( ! el.innerHTML ) {
			el.parentNode.remove();
		}
	} );

} );
