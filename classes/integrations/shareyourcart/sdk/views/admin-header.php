<link rel="stylesheet" type="text/css" href="<?php echo $this->createUrl(dirname(__FILE__).'/../css/admin-style.css'); ?>" />
<script type="text/javascript">

<?php if($this->SHAREYOURCART_URL == "www.shareyourcart.com"): //only if in production mode anonymously monitor the usage ?>
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