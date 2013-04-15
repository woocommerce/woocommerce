<?php if(!class_exists('ShareYourCartBase',false)) die('Access Denied'); ?>
<link rel="stylesheet" type="text/css" href="<?php echo $this->getUrl(dirname(__FILE__).'/../css/admin-style.css'); ?>" />

<?php //check if there is an admin-style outside of the SDK, and include that one as well

$_reflection_ = new ReflectionClass(get_class($this));
$_file_ = dirname($_reflection_->getFileName())."/css/admin-style.css";
		
//check if there is a file in the specified location
if(file_exists($_file_)):?>
		
<link rel="stylesheet" type="text/css" href="<?php echo $this->getUrl($_file_); ?>" />

<?php endif; ?>


<script type="text/javascript">

<?php if($this->SHAREYOURCART_URL == "www.shareyourcart.com" && $this->SDK_ANALYTICS): //only if in production mode anonymously monitor the usage ?>
   var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-2900571-7']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
<?php endif; ?>

window.onload = function() {
document.getElementById('syc-form').addEventListener('submit', changetext, false);
}; 

var changetext = function(){
	var textarea = document.getElementById('syc_button_textarea').value;
	document.getElementById('syc_button_textarea').value = encodeURIComponent(textarea);	
}
</script>