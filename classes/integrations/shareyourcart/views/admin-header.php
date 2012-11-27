<?php if(!class_exists('ShareYourCartBase',false)) die('Access Denied'); ?>

<script type="text/javascript">

window.onload = function() {
document.getElementById('syc-form').addEventListener('submit', changetext, false);
};

var changetext = function(){
	var textarea = document.getElementById('syc_button_textarea').value;
	document.getElementById('syc_button_textarea').value = encodeURIComponent(textarea);
}
</script>