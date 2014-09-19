<?php

/**
 * Texy! - human-readable text to HTML converter.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @license    GNU GENERAL PUBLIC LICENSE version 2 or 3
 * @link       http://texy.info
 * @package    Texy
 */


// @param $mode
// @return $dtd

$strict = $mode === Texy::HTML4_STRICT || $mode === Texy::XHTML1_STRICT;


// attributes
$coreattrs = array('id'=>1,'class'=>1,'style'=>1,'title'=>1,'xml:id'=>1); // extra: xml:id
$i18n = array('lang'=>1,'dir'=>1,'xml:lang'=>1); // extra: xml:lang
$attrs = $coreattrs + $i18n + array('onclick'=>1,'ondblclick'=>1,'onmousedown'=>1,'onmouseup'=>1,
	'onmouseover'=>1, 'onmousemove'=>1,'onmouseout'=>1,'onkeypress'=>1,'onkeydown'=>1,'onkeyup'=>1);
$cellalign = $attrs + array('align'=>1,'char'=>1,'charoff'=>1,'valign'=>1);

// content elements

// %block;
$b = array('ins'=>1,'del'=>1,'p'=>1,'h1'=>1,'h2'=>1,'h3'=>1,'h4'=>1,
	'h5'=>1,'h6'=>1,'ul'=>1,'ol'=>1,'dl'=>1,'pre'=>1,'div'=>1,'blockquote'=>1,'noscript'=>1,
	'noframes'=>1,'form'=>1,'hr'=>1,'table'=>1,'address'=>1,'fieldset'=>1);

if (!$strict) $b += array(
	'dir'=>1,'menu'=>1,'center'=>1,'iframe'=>1,'isindex'=>1, // transitional
	'marquee'=>1, // proprietary
);

// %inline;
$i = array('ins'=>1,'del'=>1,'tt'=>1,'i'=>1,'b'=>1,'big'=>1,'small'=>1,'em'=>1,
	'strong'=>1,'dfn'=>1,'code'=>1,'samp'=>1,'kbd'=>1,'var'=>1,'cite'=>1,'abbr'=>1,'acronym'=>1,
	'sub'=>1,'sup'=>1,'q'=>1,'span'=>1,'bdo'=>1,'a'=>1,'object'=>1,'img'=>1,'br'=>1,'script'=>1,
	'map'=>1,'input'=>1,'select'=>1,'textarea'=>1,'label'=>1,'button'=>1,'%DATA'=>1);

if (!$strict) $i += array(
	'u'=>1,'s'=>1,'strike'=>1,'font'=>1,'applet'=>1,'basefont'=>1, // transitional
	'embed'=>1,'wbr'=>1,'nobr'=>1,'canvas'=>1, // proprietary
);


$bi = $b + $i;

// build DTD
$dtd = array(
'html' => array(
	$strict ? $i18n + array('xmlns'=>1) : $i18n + array('version'=>1,'xmlns'=>1), // extra: xmlns
	array('head'=>1,'body'=>1),
),
'head' => array(
	$i18n + array('profile'=>1),
	array('title'=>1,'script'=>1,'style'=>1,'base'=>1,'meta'=>1,'link'=>1,'object'=>1,'isindex'=>1),
),
'title' => array(
	array(),
	array('%DATA'=>1),
),
'body' => array(
	$attrs + array('onload'=>1,'onunload'=>1),
	$strict ? array('script'=>1) + $b : $bi,
),
'script' => array(
	array('charset'=>1,'type'=>1,'src'=>1,'defer'=>1,'event'=>1,'for'=>1),
	array('%DATA'=>1),
),
'style' => array(
	$i18n + array('type'=>1,'media'=>1,'title'=>1),
	array('%DATA'=>1),
),
'p' => array(
	$strict ? $attrs : $attrs + array('align'=>1),
	$i,
),
'h1' => array(
	$strict ? $attrs : $attrs + array('align'=>1),
	$i,
),
'h2' => array(
	$strict ? $attrs : $attrs + array('align'=>1),
	$i,
),
'h3' => array(
	$strict ? $attrs : $attrs + array('align'=>1),
	$i,
),
'h4' => array(
	$strict ? $attrs : $attrs + array('align'=>1),
	$i,
),
'h5' => array(
	$strict ? $attrs : $attrs + array('align'=>1),
	$i,
),
'h6' => array(
	$strict ? $attrs : $attrs + array('align'=>1),
	$i,
),
'ul' => array(
	$strict ? $attrs : $attrs + array('type'=>1,'compact'=>1),
	array('li'=>1),
),
'ol' => array(
	$strict ? $attrs : $attrs + array('type'=>1,'compact'=>1,'start'=>1),
	array('li'=>1),
),
'li' => array(
	$strict ? $attrs : $attrs + array('type'=>1,'value'=>1),
	$bi,
),
'dl' => array(
	$strict ? $attrs : $attrs + array('compact'=>1),
	array('dt'=>1,'dd'=>1),
),
'dt' => array(
	$attrs,
	$i,
),
'dd' => array(
	$attrs,
	$bi,
),
'pre' => array(
	$strict ? $attrs : $attrs + array('width'=>1),
	array_flip(array_diff(array_keys($i), array('img','object','applet','big','small','sub','sup','font','basefont'))),
),
'div' => array(
	$strict ? $attrs : $attrs + array('align'=>1),
	$bi,
),
'blockquote' => array(
	$attrs + array('cite'=>1),
	$strict ? array('script'=>1) + $b : $bi,
),
'noscript' => array(
	$attrs,
	$bi,
),
'form' => array(
	$attrs + array('action'=>1,'method'=>1,'enctype'=>1,'accept'=>1,'name'=>1,'onsubmit'=>1,'onreset'=>1,'accept-charset'=>1),
	$strict ? array('script'=>1) + $b : $bi,
),
'table' => array(
	$attrs + array('summary'=>1,'width'=>1,'border'=>1,'frame'=>1,'rules'=>1,'cellspacing'=>1,'cellpadding'=>1,'datapagesize'=>1),
	array('caption'=>1,'colgroup'=>1,'col'=>1,'thead'=>1,'tbody'=>1,'tfoot'=>1,'tr'=>1),
),
'caption' => array(
	$strict ? $attrs : $attrs + array('align'=>1),
	$i,
),
'colgroup' => array(
	$cellalign + array('span'=>1,'width'=>1),
	array('col'=>1),
),
'thead' => array(
	$cellalign,
	array('tr'=>1),
),
'tbody' => array(
	$cellalign,
	array('tr'=>1),
),
'tfoot' => array(
	$cellalign,
	array('tr'=>1),
),
'tr' => array(
	$strict ? $cellalign : $cellalign + array('bgcolor'=>1),
	array('td'=>1,'th'=>1),
),
'td' => array(
	$cellalign + array('abbr'=>1,'axis'=>1,'headers'=>1,'scope'=>1,'rowspan'=>1,'colspan'=>1),
	$bi,
),
'th' => array(
	$cellalign + array('abbr'=>1,'axis'=>1,'headers'=>1,'scope'=>1,'rowspan'=>1,'colspan'=>1),
	$bi,
),
'address' => array(
	$attrs,
	$strict ? $i : array('p'=>1) + $i,
),
'fieldset' => array(
	$attrs,
	array('legend'=>1) + $bi,
),
'legend' => array(
	$strict ? $attrs + array('accesskey'=>1) : $attrs + array('accesskey'=>1,'align'=>1),
	$i,
),
'tt' => array(
	$attrs,
	$i,
),
'i' => array(
	$attrs,
	$i,
),
'b' => array(
	$attrs,
	$i,
),
'big' => array(
	$attrs,
	$i,
),
'small' => array(
	$attrs,
	$i,
),
'em' => array(
	$attrs,
	$i,
),
'strong' => array(
	$attrs,
	$i,
),
'dfn' => array(
	$attrs,
	$i,
),
'code' => array(
	$attrs,
	$i,
),
'samp' => array(
	$attrs,
	$i,
),
'kbd' => array(
	$attrs,
	$i,
),
'var' => array(
	$attrs,
	$i,
),
'cite' => array(
	$attrs,
	$i,
),
'abbr' => array(
	$attrs,
	$i,
),
'acronym' => array(
	$attrs,
	$i,
),
'sub' => array(
	$attrs,
	$i,
),
'sup' => array(
	$attrs,
	$i,
),
'q' => array(
	$attrs + array('cite'=>1),
	$i,
),
'span' => array(
	$attrs,
	$i,
),
'bdo' => array(
	$coreattrs + array('lang'=>1,'dir'=>1),
	$i,
),
'a' => array(
	$attrs + array('charset'=>1,'type'=>1,'name'=>1,'href'=>1,'hreflang'=>1,'rel'=>1,'rev'=>1,'accesskey'=>1,'shape'=>1,'coords'=>1,'tabindex'=>1,'onfocus'=>1,'onblur'=>1),
	$i,
),
'object' => array(
	$attrs + array('declare'=>1,'classid'=>1,'codebase'=>1,'data'=>1,'type'=>1,'codetype'=>1,'archive'=>1,'standby'=>1,'height'=>1,'width'=>1,'usemap'=>1,'name'=>1,'tabindex'=>1),
	array('param'=>1) + $bi,
),
'map' => array(
	$attrs + array('name'=>1),
	array('area'=>1) + $b,
),
'select' => array(
	$attrs + array('name'=>1,'size'=>1,'multiple'=>1,'disabled'=>1,'tabindex'=>1,'onfocus'=>1,'onblur'=>1,'onchange'=>1),
	array('option'=>1,'optgroup'=>1),
),
'optgroup' => array(
	$attrs + array('disabled'=>1,'label'=>1),
	array('option'=>1),
),
'option' => array(
	$attrs + array('selected'=>1,'disabled'=>1,'label'=>1,'value'=>1),
	array('%DATA'=>1),
),
'textarea' => array(
	$attrs + array('name'=>1,'rows'=>1,'cols'=>1,'disabled'=>1,'readonly'=>1,'tabindex'=>1,'accesskey'=>1,'onfocus'=>1,'onblur'=>1,'onselect'=>1,'onchange'=>1),
	array('%DATA'=>1),
),
'label' => array(
	$attrs + array('for'=>1,'accesskey'=>1,'onfocus'=>1,'onblur'=>1),
	$i, // - label by TexyHtml::$prohibits
),
'button' => array(
	$attrs + array('name'=>1,'value'=>1,'type'=>1,'disabled'=>1,'tabindex'=>1,'accesskey'=>1,'onfocus'=>1,'onblur'=>1),
	$bi, // - a input select textarea label button form fieldset, by TexyHtml::$prohibits
),
'ins' => array(
	$attrs + array('cite'=>1,'datetime'=>1),
	0, // special case
),
'del' => array(
	$attrs + array('cite'=>1,'datetime'=>1),
	0, // special case
),

// empty elements
'img' => array(
	$attrs + array('src'=>1,'alt'=>1,'longdesc'=>1,'name'=>1,'height'=>1,'width'=>1,'usemap'=>1,'ismap'=>1),
	FALSE,
),
'hr' => array(
	$strict ? $attrs : $attrs + array('align'=>1,'noshade'=>1,'size'=>1,'width'=>1),
	FALSE,
),
'br' => array(
	$strict ? $coreattrs : $coreattrs + array('clear'=>1),
	FALSE,
),
'input' => array(
	$attrs + array('type'=>1,'name'=>1,'value'=>1,'checked'=>1,'disabled'=>1,'readonly'=>1,'size'=>1,'maxlength'=>1,'src'=>1,'alt'=>1,'usemap'=>1,'ismap'=>1,'tabindex'=>1,'accesskey'=>1,'onfocus'=>1,'onblur'=>1,'onselect'=>1,'onchange'=>1,'accept'=>1),
	FALSE,
),
'meta' => array(
	$i18n + array('http-equiv'=>1,'name'=>1,'content'=>1,'scheme'=>1),
	FALSE,
),
'area' => array(
	$attrs + array('shape'=>1,'coords'=>1,'href'=>1,'nohref'=>1,'alt'=>1,'tabindex'=>1,'accesskey'=>1,'onfocus'=>1,'onblur'=>1),
	FALSE,
),
'base' => array(
	$strict ? array('href'=>1) : array('href'=>1,'target'=>1),
	FALSE,
),
'col' => array(
	$cellalign + array('span'=>1,'width'=>1),
	FALSE,
),
'link' => array(
	$attrs + array('charset'=>1,'href'=>1,'hreflang'=>1,'type'=>1,'rel'=>1,'rev'=>1,'media'=>1),
	FALSE,
),
'param' => array(
	array('id'=>1,'name'=>1,'value'=>1,'valuetype'=>1,'type'=>1),
	FALSE,
),

// special "base content"
'%BASE' => array(
	NULL,
	array('html'=>1,'head'=>1,'body'=>1,'script'=>1) + $bi,
),
);



if ($strict) return $dtd;


// LOOSE DTD
$dtd += array(
// transitional
'dir' => array(
	$attrs + array('compact'=>1),
	array('li'=>1),
),
'menu' => array(
	$attrs + array('compact'=>1),
	array('li'=>1), // it's inline-li, ignored
),
'center' => array(
	$attrs,
	$bi,
),
'iframe' => array(
	$coreattrs + array('longdesc'=>1,'name'=>1,'src'=>1,'frameborder'=>1,'marginwidth'=>1,'marginheight'=>1,'scrolling'=>1,'align'=>1,'height'=>1,'width'=>1),
	$bi,
),
'noframes' => array(
	$attrs,
	$bi,
),
'u' => array(
	$attrs,
	$i,
),
's' => array(
	$attrs,
	$i,
),
'strike' => array(
	$attrs,
	$i,
),
'font' => array(
	$coreattrs + $i18n + array('size'=>1,'color'=>1,'face'=>1),
	$i,
),
'applet' => array(
	$coreattrs + array('codebase'=>1,'archive'=>1,'code'=>1,'object'=>1,'alt'=>1,'name'=>1,'width'=>1,'height'=>1,'align'=>1,'hspace'=>1,'vspace'=>1),
	array('param'=>1) + $bi,
),
'basefont' => array(
	array('id'=>1,'size'=>1,'color'=>1,'face'=>1),
	FALSE,
),
'isindex' => array(
	$coreattrs + $i18n + array('prompt'=>1),
	FALSE,
),

// proprietary
'marquee' => array(
	Texy::ALL,
	$bi,
),
'nobr' => array(
	array(),
	$i,
),
'canvas' => array(
	Texy::ALL,
	$i,
),
'embed' => array(
	Texy::ALL,
	FALSE,
),
'wbr' => array(
	array(),
	FALSE,
),
);

// transitional modified
$dtd['a'][0] += array('target'=>1);
$dtd['area'][0] += array('target'=>1);
$dtd['body'][0] += array('background'=>1,'bgcolor'=>1,'text'=>1,'link'=>1,'vlink'=>1,'alink'=>1);
$dtd['form'][0] += array('target'=>1);
$dtd['img'][0] += array('align'=>1,'border'=>1,'hspace'=>1,'vspace'=>1);
$dtd['input'][0] += array('align'=>1);
$dtd['link'][0] += array('target'=>1);
$dtd['object'][0] += array('align'=>1,'border'=>1,'hspace'=>1,'vspace'=>1);
$dtd['script'][0] += array('language'=>1);
$dtd['table'][0] += array('align'=>1,'bgcolor'=>1);
$dtd['td'][0] += array('nowrap'=>1,'bgcolor'=>1,'width'=>1,'height'=>1);
$dtd['th'][0] += array('nowrap'=>1,'bgcolor'=>1,'width'=>1,'height'=>1);

// missing: FRAMESET, FRAME, BGSOUND, XMP, ...
