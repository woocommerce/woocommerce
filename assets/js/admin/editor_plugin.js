(
	function(){
	
		var icon_url = '../wp-content/plugins/woocommerce/assets/images/icons/wc_icon.png';
	
		tinymce.create(
			"tinymce.plugins.WooCommerceShortcodes",
			{
				init: function(d,e) {},
				createControl:function(d,e)
				{
				
					if(d=="woocommerce_shortcodes_button"){
					
						d=e.createMenuButton( "woocommerce_shortcodes_button",{
							title:"Insert WooCommerce Shortcode",
							image:icon_url,
							icons:false
							});
							
							var a=this;d.onRenderMenu.add(function(c,b){
								
								
								a.addImmediate(b,"Product price/cart button", '[add_to_cart id="" sku=""]');
								a.addImmediate(b,"Product by SKU/ID", '[product id="" sku=""]');
								a.addImmediate(b,"Products by SKU/ID", '[products ids="" skus=""]');
								a.addImmediate(b,"Products by category slug", '[product_category category="" per_page="12" columns="4" orderby="date" order="desc"]');
								
								b.addSeparator();
								
								a.addImmediate(b,"Recent products", '[recent_products per_page="12" columns="4" orderby="date" order="desc"]');
								a.addImmediate(b,"Featured products", '[featured_products per_page="12" columns="4" orderby="date" order="desc"]');
								
								b.addSeparator();
								
								a.addImmediate(b,"Shop Messages", '[woocommerce_messages]');
								
								b.addSeparator();
								
								c=b.addMenu({title:"WooCommerce Pages"});
										a.addImmediate(c,"Cart","[woocommerce_cart]" );
										a.addImmediate(c,"Checkout","[woocommerce_checkout]" );
										a.addImmediate(c,"Order tracking","[woocommerce_order_tracking]" );
										a.addImmediate(c,"My Account","[woocommerce_my_account]" );
										a.addImmediate(c,"Edit Address","[woocommerce_edit_address]" );
										a.addImmediate(c,"Change Password","[woocommerce_change_password]" );
										a.addImmediate(c,"View Order","[woocommerce_view_order]" );
										a.addImmediate(c,"Pay","[woocommerce_pay]" );
										a.addImmediate(c,"Thankyou","[woocommerce_thankyou]" );

							});
						return d
					
					} // End IF Statement
					
					return null
				},
		
				addImmediate:function(d,e,a){d.add({title:e,onclick:function(){tinyMCE.activeEditor.execCommand( "mceInsertContent",false,a)}})}
				
			}
		);
		
		tinymce.PluginManager.add( "WooCommerceShortcodes", tinymce.plugins.WooCommerceShortcodes);
	}
)();