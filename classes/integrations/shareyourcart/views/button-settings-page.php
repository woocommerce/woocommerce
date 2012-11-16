<?php if(!class_exists('ShareYourCartBase',false)) die('Access Denied'); ?>
<?php if(!$this->isActive()) return; //if the plugin is not active, do not show this page ?>
<script type="text/javascript">
  if(_gaq) _gaq.push(['_trackPageview', '/admin/button-settings-view']);
</script>

<div class="wrap">
    <h3>Button options</h3>
	<?php if($show_header):?>
	
	<?php echo $this->getUpdateNotification(); ?>
	
    <h2>
        <a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart" class="shareyourcart-logo" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin/button-settings-view/logo-click']);">
            <img src="<?php echo $this->createUrl(dirname(__FILE__).'/../img/shareyourcart-logo.png'); ?>"/>
        </a>
		<div class="syc-slogan"><?php echo SyC::t('sdk','Increase your social media exposure by 10%!'); ?></div>
		<br clear="all" /> 
    </h2>
	<?php endif; ?>

    <?php if (!empty($status_message)): ?>
        <div class="updated settings-error"><p><strong>
                    <?php echo $status_message; ?>
                </strong></p></div>
    <?php endif; ?>

    <div id="visual-options">       		        	
        <fieldset>
            <div>
                <input type="radio" value="1" id="button_type_1" <?php if ($current_button_type == '1'||$current_button_type == '') echo 'checked' ?> name="button_type" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin/button-settings-view/default-click']);" />
                <label for="button_type_1"><?php echo SyC::t('sdk','Use Standard Button'); ?></label> 
            </div>
            <table class="form-table shareyourcart_button_standard" name="shareyourcart_button_standard">
                <tr >
                    <th class="titledesc" scope="row"><?php echo SyC::t('sdk','Button skin'); ?></th>
                    <td class="forminp">
                        <select name="button_skin" id="button_skin" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin/button-settings-view/button-skin-click']);">
                            <option name="orange" <?php echo $current_skin == 'orange' ? 'selected="selected"' : ''; ?> value="orange"><?php echo SyC::t('sdk','Orange'); ?></option>
                            <option name="blue" <?php echo $current_skin == 'blue' ? 'selected="selected"' : ''; ?> value="blue"><?php echo SyC::t('sdk','Blue'); ?></option>
                            <option name="light" <?php echo $current_skin == 'light' ? 'selected="selected"' : ''; ?> value="light"><?php echo SyC::t('sdk','Light'); ?></option>
                            <option name="dark" <?php echo $current_skin == 'dark' ? 'selected="selected"' : ''; ?> value="dark"><?php echo SyC::t('sdk','Dark'); ?></option>
                        </select>                        
                    </td>
                </tr>
                <tr >
                    <th class="titledesc" scope="row"><?php echo SyC::t('sdk','Button position'); ?></th>
                    <td class="forminp">
                        <select name="button_position" id="button_position" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin/button-settings-view/button-position-click']);">
                            <option name="normal" <?php echo $current_position == 'normal' ? 'selected="selected"' : ''; ?> value="normal"><?php echo SyC::t('sdk','Normal'); ?></option>
                            <option name="floating" <?php echo $current_position == 'floating' ? 'selected="selected"' : ''; ?> value="floating"><?php echo SyC::t('sdk','Floating'); ?></option>
                        </select>                        
                    </td>
                </tr>       
              <!--  <tr align="center"> since we switched to <a> on the button, this does not seem to be needed anymore
                    <td>
                        <input class="buttonCheckbox" name="show_on_single_row" <?php echo $show_on_single_row ? 'checked="checked"' : ''; ?>  type='checkbox' onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin/button-settings-view/toggle-show-on-single-row-click']);"><?php echo SyC::t('sdk','Check this if you want the button to be shown on it\'s own row'); ?></input>
                    </td>
                </tr> -->                               
            </table>
        </fieldset>
        <fieldset>
            <input  type="radio" value="2" id="button_type_2" name="button_type" <?php if ($current_button_type == '2') echo 'checked' ?> onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin/button-settings-view/image-button-click']);"/>
            <label  for="button_type_2"><?php echo SyC::t('sdk','Use Image Button'); ?></label>
            <?php if (empty($button_img)){ ?><br /><?php } ?>
            <table class="form-table shareyourcart_button_image" name="shareyourcart_button_image">
                <tr>
                    <th scope="row" class="titledesc"><label><?php echo SyC::t('sdk','Image:'); ?></label></th>
                
                    <td class="forminp">
                        <?php if (!empty($button_img)): ?>
                            <img src="<?php echo $button_img ?>" height="40" />
                        <?php endif; ?>
                        
                        <input type="hidden" name="MAX_FILE_SIZE" value="100000000000" />
                        <input type="file" accept="image/gif, image/jpeg, image/jpg, image/png" name="button-img" id="button-img" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin/button-settings-view/upload-normal-img-click']);" />
                    </td>
                </tr> 

                <tr>
                    <th scope="row" class="titledesc"><label><?php echo SyC::t('sdk','Hover image:'); ?></label></th>
               
                    <td class="forminp">
                        <?php if (!empty($button_img_hover)): ?>
                            <img src="<?php echo $button_img_hover ?>" height="40" />
                        <?php endif; ?>
                        
                        <input type="hidden" name="MAX_FILE_SIZE" value="100000000000" />
                        <input type="file" accept="image/gif, image/jpeg, image/jpg, image/png" name="button-img-hover" id="button-img-hover" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin/button-settings-view/upload-hover-img-click']);" />
                    </td>
                </tr>                               
            </table>
        </fieldset>
        <fieldset>
            <input  type="radio" value="3" id="button_type_3" name="button_type" <?php if ($current_button_type == '3') echo 'checked' ?> onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin/button-settings-view/custom-button-click']);"/>
            <label  for="button_type_3"><?php echo SyC::t('sdk','Build your own HTML button'); ?></label>  
            <table class="form-table shareyourcart_button_html" name="shareyourcart_button_html">
                <tr>
                    <th>
                        HTML:
                    </th>              
                    <td>
                        <textarea id="syc_button_textarea" class="buttonTextarea" rows="7" cols="56" name="button_html"><?php echo ($button_html!=''?$button_html:'<button>'.SyC::t('sdk','Get a {value} discount',array('{value}' => '<div class="shareyourcart-discount"></div>')).'</button>'); ?></textarea>                    
                    </td>
                </tr>                           
            </table>

            <?php echo $html; ?>
        </fieldset> 
        <fieldset>
            <table class="form-table " name="shareyourcart_settings">
                <tr>
                    <th scope="row" valign="top"><?php echo SyC::t('sdk','Show button by default on: '); ?></th>
                    <td>
                        <input class="buttonCheckbox" name="show_on_product" <?php echo $show_on_product ? 'checked="checked"' : ''; ?>  type='checkbox' onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin/button-settings-view/toggle-show-on-product-click']);"><?php echo SyC::t('sdk','Product page'); ?></input>
                        <br />
                        <input class="buttonCheckbox" name="show_on_checkout" <?php echo $show_on_checkout ? 'checked="checked"' : ''; ?> type='checkbox' onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin/button-settings-view/toggle-show-on-checkout-click']);"><?php echo SyC::t('sdk','Checkout page'); ?></input>                        
                    </td>
                </tr>
    			 <tr>
                    <th scope="row" valign="top"><?php echo SyC::t('sdk','Position product button after: '); ?></th>
                    <td>
                        <input name="product_button_position" class="regular-text" value="<?php echo $this->getProductButtonPosition(); ?>" />
                        <span class="description"><?php echo SyC::t('sdk','<strong>jQuery selector</strong>. Start with <strong>{elem}</strong> to position the button before the actual object',array('{elem}' => "/*before*/")); ?></span>
                    </td>
                </tr>
    			 <tr>
                    <th scope="row" valign="top"><?php echo SyC::t('sdk','Position cart button after: '); ?></th>
                    <td>
                        <input name="cart_button_position" class="regular-text" value="<?php echo $this->getCartButtonPosition(); ?>" />
                        <span class="description"><?php echo SyC::t('sdk','<strong>jQuery selector</strong>. Start with <strong>{elem}</strong> to position the button before the actual object',array('{elem}' => "/*before*/")); ?></span>
                    </td>
                </tr>
            </table>
        </fieldset>        
              
                
        <div class="submit">
            <input type="submit" class="button-primary" name="syc-visual-form" id="syc-visual-form" value="<?php echo SyC::t('sdk','Save changes'); ?>" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin/button-settings-view/save-click']);">
        </div>            
                
            
            <br />     
    </div>

	<?php if($show_footer):?>
    <h2><?php echo SyC::t('sdk','Contact'); ?></h2>
    <p><?php echo SyC::t('sdk',"If you've got 30 seconds, we'd {link-1} love to know what ideal outcome you'd like ShareYourCart to help bring to your business</a>, or if you have a private question, you can {link-2} contact us directly</a>", array('{link-1}' => '<a href="http://shareyourcart.uservoice.com" target="_blank" title="forum" class="api-link" onclick=" if(_gaq) _gaq.push([\'_trackPageview\', \'/admin/documentation/forum-click\']);">', '{link-2}' => '<a href="http://www.shareyourcart.com/contact" target="_blank" class="api-link" onclick=" if(_gaq) _gaq.push([\'_trackPageview\', \'/admin/documentation/contact-click\']);">')); ?></p>
    <br />
	<?php endif; ?>
</div>