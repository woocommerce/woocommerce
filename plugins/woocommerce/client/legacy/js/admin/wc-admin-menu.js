document.addEventListener( 'DOMContentLoaded', () => {

	const adminMenu = document.getElementById( 'adminmenu' );
	if ( ! adminMenu ) {
		return;
	}

	const addonsSubMenuLinks = adminMenu.querySelectorAll(
		'#toplevel_page_woocommerce' +
		' .wp-submenu' +
		' li a[href="admin.php?page=wc-addons"]'
	);

	addonsSubMenuLinks.forEach( ( el, index ) => {
		if ( ! el.innerHTML ) {
			el.parentNode.remove();
		}
	} );

} );
