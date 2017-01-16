<?php

/**************************************************************
"Learning with Texts" (LWT) is free and unencumbered software
released into the PUBLIC DOMAIN.

Anyone is free to copy, modify, publish, use, compile, sell, or
distribute this software, either in source code form or as a
compiled binary, for any purpose, commercial or non-commercial,
and by any means.

In jurisdictions that recognize copyright laws, the author or
authors of this software dedicate any and all copyright
interest in the software to the public domain. We make this
dedication for the benefit of the public at large and to the
detriment of our heirs and successors. We intend this
dedication to be an overt act of relinquishment in perpetuity
of all present and future rights to this software under
copyright law.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE
AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS BE LIABLE
FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

For more information, please refer to [http://unlicense.org/].
***************************************************************/

/**************************************************************
Call: set_text_mode.php?text=[textid]&mode=0/1&showLeaning=0/1
Change the text display mode
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$tid = getreq('text') + 0;
$showAll = getreq('mode') + 0;
saveSetting('showallwords',$showAll);
$previousShowLearning = getSettingZeroOrOne('showlearningtranslations', 1);
$showLearning = getreq('showLearning');
saveSetting('showlearningtranslations', $showLearning);

pagestart("Text Display Mode changed", false);

echo '<p><span id="waiting"><img src="'.get_file_path('icn/waiting.gif').'" alt="Please wait" title="Please wait" />&nbsp;&nbsp;Please wait ...</span>';
flush();
?>

<script type="text/javascript">
//<![CDATA[
var method = <?php echo ($showLearning != $previousShowLearning ? '1' : '0'); ?>;  // 0 (jquery) or 1 (reload)
if (method) window.parent.frames['l'].location.reload();
else {
var context = window.parent.frames['l'].document;
<?php
if($showAll == 1){
?>
$('.mword',context).removeClass('wsty').addClass('mwsty').each(function(){
	var c = '&nbsp;' + $(this).attr('data_code') + '&nbsp;';
	$(this).html(c);
});
$('span',context).not('#totalcharcount').removeClass('hide');
<?php
}
else{
?>
$('.mword',context).removeClass('mwsty').addClass('wsty').each(function(){
	var c = $(this).attr('data_text');
	$(this).text(c);
	if($(this).not('.hide').length){
		u= parseInt($(this).attr('data_code')) *2 + parseInt($(this).attr('data_order')) -1;
		$(this).nextUntil('[id^="ID-' + u + '-"]',context).addClass('hide');
	}
});
<?php
}
?>
}
$('#waiting').html('<b>OK -- </b>');
//]]>
</script>

<?php

if ($showAll == 1)
	echo '<b><i>Show All</i></b> is set to <b>ON</b>.<br /><br />ALL terms are now shown, and all multi-word terms are shown as superscripts before the first word. The superscript indicates the number of words in the multi-word term.<br /><br />To concentrate more on the multi-word terms and to display them without superscript, set <i>Show All</i> to OFF.</p>';
else
	echo '<b><i>Show All</i></b> is set to <b>OFF</b>.<br /><br />Multi-word terms now hide single words and shorter or overlapping multi-word terms. The creation and deletion of multi-word terms can be a bit slow in long texts.<br /><br />To  manipulate ALL terms, set <i>Show All</i> to ON.</p>';

echo "<br /><br />";

if ($showLearning == 1)
	echo '<b><i>Learning Translations</i></b> is set to <b>ON</b>.<br /><br />Terms that have Learning Level&nbsp;1 will show their translations beneath the term in the reading mode.<br /><br />To hide the translations, set <i>Learning Translations</i> to OFF.</p>';
else
	echo '<b><i>Learning Translations</i></b> is set to <b>OFF</b>.<br /><br />No translations will be shown directly in the reading window.<br /><br />To see translations for terms with Learning Level&nbsp;1 underneath the terms in the reading window, set <i>Learning Translations</i> to ON.</p>';

pageend();

?>
