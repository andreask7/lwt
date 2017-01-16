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
Global variables used in LWT jQuery functions
***************************************************************/

var TEXTPOS = -1;
var OPENED = 0;
var WID = 0;
var TID = 0;
var WBLINK1 = '';
var WBLINK2 = '';
var WBLINK3 = '';
var SOLUTION = '';
var ADDFILTER = '';
var RTL = 0;
var ANN_ARRAY = {};
var DELIMITER = '';
var JQ_TOOLTIP = 0;

/**************************************************************
LWT jQuery functions
***************************************************************/

function setTransRoman(tra, rom) {
	if($('textarea[name="WoTranslation"]').length == 1)
		$('textarea[name="WoTranslation"]').val(tra);
	if($('input[name="WoRomanization"]').length == 1)
		$('input[name="WoRomanization"]').val(rom);
	makeDirty();
}

function getUTF8Length(string) {
	var utf8length = 0;
	for (var n = 0; n < string.length; n++) {
		var c = string.charCodeAt(n);
		if (c < 128) {
			utf8length++;
		}
		else if((c > 127) && (c < 2048)) {
			utf8length = utf8length+2;
		}
		else {
			utf8length = utf8length+3;
		}
	}
	return utf8length;
}

function scrollToAnchor(aid){
  document.location.href = '#' + aid;
}

function changeImprAnnText() {
	var textid = $('#editimprtextdata').attr('data_id');
	$(this).prev('input:radio').attr('checked', 'checked');
	var elem = $(this).attr('name');
	var thedata = JSON.stringify($('form').serializeObject());
	$.post('ajax_save_impr_text.php', { id: textid, elem: elem, data : thedata }
		, function(d) {
				if(d != 'OK')
					alert('Saving your changes failed, please reload page and try again!');
			}
	);
}

function changeImprAnnRadio() {
	var textid = $('#editimprtextdata').attr('data_id');
	var elem = $(this).attr('name');
	var thedata = JSON.stringify($('form').serializeObject());
	$.post('ajax_save_impr_text.php', { id: textid, elem: elem, data : thedata }
		, function(d) {
				if(d != 'OK')
					alert('Saving your changes failed, please reload page and try again!');
			}
	);
}

function addTermTranslation(wordid,txid,word,lang) {
	var thedata = $(txid).val().trim();
	var pagepos = $(document).scrollTop();
	if((thedata == '') || (thedata == '*')) {
		alert('Text Field is empty or = \'*\'!');
		return;
	}
	$.post('ajax_add_term_transl.php', { id: wordid, data : thedata, text: word, lang: lang }
		, function(d) {
				if(d == '') {
					alert('Adding translation to term OR term creation failed, please reload page and try again!');
				} else {
					do_ajax_edit_impr_text(pagepos,d);
				}
			}
	);
}

function changeTableTestStatus(wordid,up) {
	$.post('ajax_chg_term_status.php', { id: wordid, data: (up ? 1 : 0) }
		, function(data) {
				if(data != '') {
					$('#STAT' + wordid).html(data);
				}
			}
	);
}

function check() {
	var count = 0;
	$('.notempty').each( function(n) {
		if($(this).val().trim()=='') count++;
	} );
	if (count > 0) {
		alert('ERROR\n\n' + count + ' field(s) - marked with * - must not be empty!');
		return false;
	}
	count = 0;
	$('input.checkurl').each( function(n) {
		if($(this).val().trim().length > 0) {
			if(($(this).val().trim().indexOf('http://') != 0) &&   ($(this).val().trim().indexOf('https://') != 0) && ($(this).val().trim().indexOf('#') != 0)) {
				alert('ERROR\n\nField "' + $(this).attr('data_info') + '" must start with "http://" or "https://" if not empty.');
				count++;
			}
		}
	} );
	$('input.checkregexp').each( function(n) {
		var regexp = $(this).val().trim();
		if(regexp.length > 0) {
		$.ajax({type: "POST",url:'ajax_check_regexp.php', data:{ regex: regexp }
			, async: false}
			).always(function(data) {
				if(data != '') {
					alert(data);
					count++;
				}
			});
		}
	});
//to enable limits of custom feed texts/articl. change the following «input[class*="max_int_"]» into «input[class*="maxint_"]»
	$('input[class*="max_int_"]').each( function(n) {
		var maxvalue = parseInt($(this).attr("class").replace(/.*maxint_([0-9]+).*/, '$1'));
		if ($(this).val().trim().length > 0) {
			if ($(this).val() > maxvalue) {
				alert('ERROR\n\n Max Value of Field "' + $(this).attr('data_info') + '" is ' + maxvalue);
				count++;
			}
		}
	} );
	$('input.checkdicturl').each( function(n) {
		if($(this).val().trim().length > 0) {
			if(($(this).val().trim().indexOf('http://') != 0) &&   ($(this).val().trim().indexOf('https://') != 0) &&   ($(this).val().trim().indexOf('*http://') != 0) &&   ($(this).val().trim().indexOf('*https://') != 0) &&   ($(this).val().trim().indexOf('glosbe_api.php') != 0) &&   ($(this).val().trim().indexOf('ggl.php') != 0)) {
				alert('ERROR\n\nField "' + $(this).attr('data_info') + '" must start with "http://" or "https://" or "*http://" or "*https://" or "glosbe_api.php" or "ggl.php" if not empty.');
				count++;
			}
		}
	} );
	$('input.posintnumber').each( function(n) {
		if ($(this).val().trim().length > 0) {
			if (! (isInt($(this).val().trim()) && (($(this).val().trim() + 0) > 0))) {
				alert('ERROR\n\nField "' + $(this).attr('data_info') + '" must be an integer number > 0.');
				count++;
			}
		}
	} );
	$('input.zeroposintnumber').each( function(n) {
		if ($(this).val().trim().length > 0) {
			if (! (isInt($(this).val().trim()) && (($(this).val().trim() + 0) >= 0))) {
				alert('ERROR\n\nField "' + $(this).attr('data_info') + '" must be an integer number >= 0.');
				count++;
			}
		}
	} );
	$('textarea.checklength').each( function(n) {
		if($(this).val().trim().length > (0 + $(this).attr('data_maxlength'))) {
			alert('ERROR\n\nText is too long in field "' + $(this).attr('data_info') + '", please make it shorter! (Maximum length: ' + $(this).attr('data_maxlength') + ' char.)');
			count++;
		}
	} );
	$('textarea.checkbytes').each( function(n) {
		if(getUTF8Length($(this).val().trim()) > (0 + $(this).attr('data_maxlength'))) {
			alert('ERROR\n\nText is too long in field "' + $(this).attr('data_info') + '", please make it shorter! (Maximum length: ' + $(this).attr('data_maxlength') + ' bytes.)');
			count++;
		}
	} );
	$('input.noblanksnocomma').each( function(n) {
		if($(this).val().indexOf(' ') > 0 || $(this).val().indexOf(',') > 0) {
			alert('ERROR\n\nNo spaces or commas allowed in field "' + $(this).attr('data_info') + '", please remove!');
			count++;
		}
	} );
	return (count == 0);
}

function isInt(value) {
	for (var i = 0 ; i < value.length ; i++) {
		if ((value.charAt(i) < '0') || (value.charAt(i) > '9')) {
			return false;
		}
	}
	return true;
}

function markClick() {
	if($('input.markcheck:checked').length > 0) {
		$('#markaction').removeAttr('disabled');
	} else {
		$('#markaction').attr('disabled','disabled');
	}
}

function textModeChangeClick() {
	var showAll = $('#showallwords:checked').length;
	var showLeaning = $('#showlearningtranslations:checked').length;
	var text = $('#thetextid').text();
	window.parent.frames['ro'].location.href =
		'set_text_mode.php?mode=' + showAll +
		'&showLearning=' + showLeaning +
		'&text=' + text;
}

function textareaKeydown(event) {
	if (event.keyCode && event.keyCode == '13') {
		if (check()) $('input:submit').last().click();
		return false;
	} else {
		return true;
	}
}

function noShowAfter3Secs() {
	$('#hide3').slideUp();
}

function setTheFocus() {
	$('.setfocus').focus().select();
}

function word_click_event_do_test_test() {
	run_overlib_test(
		WBLINK1, WBLINK2, WBLINK3,
		$(this).attr('data_wid'),
		$(this).attr('data_text'),
		$(this).attr('data_trans'),
		$(this).attr('data_rom'),
		$(this).attr('data_status'),
		$(this).attr('data_sent'),
		$(this).attr('data_todo'));
	$('.todo').text(SOLUTION);
	return false;
}

function keydown_event_do_test_test(e) {
	if (e.which == 32 && OPENED == 0) {  // space : show sol.
		$('.word').click();
		cClick();
		window.parent.frames['ro'].location.href = 'show_word.php?wid=' + $('.word').attr('data_wid') + '&ann=';
		OPENED = 1;
		return false;
	}
	if (OPENED == 0) return true;
	if (e.which == 38) {  // up : status+1
		window.parent.frames['ro'].location.href =
			'set_test_status.php?wid=' + WID + '&stchange=1';
		return false;
	}
	if (e.which == 40) {  // down : status-1
		window.parent.frames['ro'].location.href =
			'set_test_status.php?wid=' + WID + '&stchange=-1';
		return false;
	}
	if (e.which == 27) {  // esc : dont change status
		window.parent.frames['ro'].location.href =
			'set_test_status.php?wid=' + WID + '&status=' + $('.word').attr('data_status');
		return false;
	}
	for (var i=1; i<=5; i++) {
		if (e.which == (48+i) || e.which == (96+i)) {  // 1,.. : status=i
			window.parent.frames['ro'].location.href =
				'set_test_status.php?wid=' + WID + '&status=' + i;
			return false;
		}
	}
	if (e.which == 73) {  // I : status=98
		window.parent.frames['ro'].location.href =
			'set_test_status.php?wid=' + WID + '&status=98';
		return false;
	}
	if (e.which == 87) {  // W : status=99
		window.parent.frames['ro'].location.href =
			'set_test_status.php?wid=' + WID + '&status=99';
		return false;
	}
	if (e.which == 69) {  // E : EDIT
		window.parent.frames['ro'].location.href =
			'edit_tword.php?wid=' + WID;
		return false;
	}
	return true;
}

function word_each_do_text_text(i) {
	var wid = $(this).attr('data_wid');
	if (wid != '') {
		var order = $(this).attr('data_order');
		if (order in ANN_ARRAY) {
			if (wid == ANN_ARRAY[order][1]) {
				var ann = ANN_ARRAY[order][2];
				var re = new RegExp("([" + DELIMITER + "][ ]{0,1}|^)(" + ann.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ")($|[ ]{0,1}[" + DELIMITER + "])","");
				if(!re.test($(this).attr('data_trans').replace( / \[.*$/, ''))){
					var trans = ann + ' / ' + $(this).attr('data_trans');
					$(this).attr('data_trans', trans.replace( ' / *', ''));
				}
				$(this).attr('data_ann',ann);
			}
		}
	}
	if(!JQ_TOOLTIP)this.title = make_tooltip($(this).text(), $(this).attr('data_trans'),
		$(this).attr('data_rom'), $(this).attr('data_status'));
}

function mword_each_do_text_text(i) {
	if ($(this).attr('data_status') != '') {
		var wid = $(this).attr('data_wid');
		if (wid != '') {
			var order = parseInt($(this).attr('data_order'));
			for (var j = 2; j <= 16; j = j+2) {
				var index = (order+j).toString();
				if (index in ANN_ARRAY) {
					if (wid == ANN_ARRAY[index][1]) {
						var ann = ANN_ARRAY[index][2];
						var re = new RegExp("([" + DELIMITER + "][ ]{0,1}|^)(" + ann.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ")($|[ ]{0,1}[" + DELIMITER + "])","");
						if(!re.test($(this).attr('data_trans').replace( / \[.*$/, ''))){
							var trans = ann + ' / ' + $(this).attr('data_trans');
							$(this).attr('data_trans', trans.replace( ' / *', ''));
						}
						$(this).attr('data_ann',ann);
						break;
					}
				}
			}
		}
		if(!JQ_TOOLTIP)this.title = make_tooltip($(this).attr('data_text'),
		$(this).attr('data_trans'), $(this).attr('data_rom'),
		$(this).attr('data_status'));
	}
}

function word_dblclick_event_do_text_text() {
	var t = parseInt($("#totalcharcount").text(),10);
	if ( t == 0 ) return;
	var p = 100 * ($(this).attr('data_pos')-5) / t;
	if (p < 0) p = 0;
	if (typeof (window.parent.frames['h'].new_pos) == 'function')
		window.parent.frames['h'].new_pos(p);
}

function word_click_event_do_text_text() {
	var status = $(this).attr('data_status');
	var ann = '';
	if ((typeof $(this).attr('data_ann')) != 'undefined')
		ann = $(this).attr('data_ann');

	if ( status < 1 ) {
		run_overlib_status_unknown(WBLINK1,WBLINK2,WBLINK3,JQ_TOOLTIP?make_tooltip($(this).text(),$(this).attr('data_trans'),$(this).attr('data_rom'),status):$(this).attr("title"),
			TID,$(this).attr('data_order'),$(this).text(),RTL);
		top.frames['ro'].location.href='edit_word.php?tid=' + TID + '&ord=' +
			$(this).attr('data_order') + '&wid=';
	}
	else if ( status == 99 )
		run_overlib_status_99(WBLINK1,WBLINK2,WBLINK3,JQ_TOOLTIP?make_tooltip($(this).text(),$(this).attr('data_trans'),$(this).attr('data_rom'),status):$(this).attr("title"),
			TID,$(this).attr('data_order'),$(this).text(),$(this).attr('data_wid'),RTL,ann);
	else if ( status == 98 )
		run_overlib_status_98(WBLINK1,WBLINK2,WBLINK3,JQ_TOOLTIP?make_tooltip($(this).text(),$(this).attr('data_trans'),$(this).attr('data_rom'),status):$(this).attr("title"),
			TID,$(this).attr('data_order'),$(this).text(),$(this).attr('data_wid'),RTL,ann);
	else
		run_overlib_status_1_to_5(WBLINK1,WBLINK2,WBLINK3,JQ_TOOLTIP?make_tooltip($(this).text(),$(this).attr('data_trans'),$(this).attr('data_rom'),status):$(this).attr("title"),
			TID,$(this).attr('data_order'),$(this).text(),$(this).attr('data_wid'),status,RTL,ann);
	return false;
}

function mword_click_event_do_text_text() {
	var status = $(this).attr('data_status');
	if (status != '') {
		var ann = '';
		if ((typeof $(this).attr('data_ann')) != 'undefined')
			ann = $(this).attr('data_ann');
		run_overlib_multiword(WBLINK1,WBLINK2,WBLINK3,JQ_TOOLTIP?make_tooltip($(this).text(),$(this).attr('data_trans'),$(this).attr('data_rom'),status):$(this).attr("title"),
		TID, $(this).attr('data_order'),$(this).attr('data_text'),
		$(this).attr('data_wid'), status,$(this).attr('data_code'), ann);
	}
	return false;
}

function mword_drag_n_drop_select(event) {
	if(JQ_TOOLTIP)$('.ui-tooltip').remove();
	var context = $(this).parent();
	context.one('mouseup mouseout',$(this),function(){
		clearTimeout(to);
		$('.nword').removeClass('nword');
		$('.tword').removeClass('tword');
		$('.lword').removeClass('lword');
		$('.wsty',context).css('background-color','').css('border-bottom-color','');
		$('#pe').remove();
	});

	to=setTimeout(function(){
		var pos;
		context.off('mouseout');
		$('.wsty',context).css('background-color','inherit').css('border-bottom-color','rgba(0,0,0,0)').not('.hide,.word').each(function(){
			f=parseInt($(this).attr('data_code'))*2 + parseInt($(this).attr('data_order')) - 1;
			h='';
			$(this).nextUntil($('[id^="ID-'+ f +'-"]',context),'[id$="-1"]').each(function(){
				l=$(this).attr('data_order');
				if(typeof l != 'undefined'){
					h+='<span class="tword" data_order="' + l + '">' + $(this).text() + '</span>';
				}else {
					h+='<span class="nword" data_order="' + $(this).attr('id').split('-')[1] + '">' + $(this).text() + '</span>';
				}
			});
			$(this).html(h);
		});
		$('#pe').remove();
		$('body').append('<style id="pe">#'+ context.attr('id') + ' .wsty:after,#'+ context.attr('id') + ' .wsty:before{opacity:0}</style>');

		$('[id$="-1"]',context).not('.hide,.wsty').addClass('nword').each(function(){
			$(this).attr('data_order',$(this).attr('id').split('-')[1]);
		});
		$('.word',context).not('.hide').each(function(){
			$(this).html('<span class="tword" data_order="' + $(this).attr('data_order') + '">' + $(this).text() + '</span>');
		});
		if(event.data.annotation==1)$('.wsty',context).not('.hide').each(function(){$(this).children('.tword').last().attr('data_ann',$(this).attr('data_ann')).attr('data_trans',$(this).attr('data_trans')).addClass('content' + $(this).removeClass('status1 status2 status3 status4 status5 status98 status99').attr('data_status'));});
		if(event.data.annotation==3)$('.wsty',context).not('.hide').each(function(){$(this).children('.tword').first().attr('data_ann',$(this).attr('data_ann')).attr('data_trans',$(this).attr('data_trans')).addClass('content' + $(this).removeClass('status1 status2 status3 status4 status5 status98 status99').attr('data_status'));});
		$(context).one('mouseover','.tword',function(){
			$('html').one('mouseup',function(){
				$('.wsty',context).each(function(){$(this).addClass('status' + $(this).attr('data_status'));});
				if(!$(this).hasClass('tword')){
					$('span',context).removeClass('nword tword lword');
					$('.wsty',context).css('background-color','').css('border-bottom-color','');
					$('#pe').remove();
				}
			});
			pos=parseInt($(this).attr('data_order'));
			$('.lword',context).removeClass('lword');
			$(this).addClass('lword');
			$(context).on('mouseleave',function(){
				$('.lword',context).removeClass('lword');
			});
			$(context).one('mouseup','.nword,.tword',function(ev){
				if(ev.handled !== true){
					var len = $('.lword.tword',context).length;
					if(len>0){
						g=$('.lword',context).first().attr('data_order');
						if(len>1){
							var text = $('.lword',context).map(function() {return $( this ).text();}).get().join( "" );
							if(text.length >250){
								alert('selected text is too long!!!');
							}else{
								top.frames['ro'].location.href='edit_mword.php?tid=' + TID + '&len=' + len + '&ord=' + g + '&txt=' + text;
							}
						}
						else{
							top.frames['ro'].location.href='edit_word.php?tid=' + TID + '&ord=' + g + '&txt=' + $('#ID-'+ g +'-1').text();
						}
					}
					$('span',context).removeClass('tword nword');
					ev.handled = true;
				}
			});
		});
		$(context).hoverIntent({over:function(){
			$('.lword',context).removeClass('lword');
			var lpos=parseInt($(this).attr('data_order'));
			$(this).addClass('lword');
			if(lpos>pos){
				for(var i=pos;i<lpos;i++){
					$('.tword[data_order="' + i + '"],.nword[data_order="' + i + '"]',context).addClass('lword');
				}
			}
			else{
				for(var i=pos;i>lpos;i--){
					$('.tword[data_order="' + i + '"],.nword[data_order="' + i + '"]',context).addClass('lword');
				}
			}
		},out:function(){}, sensitivity: 18, selector:".tword"});
	}, 300);
}

function word_hover_over () {
	if (!$(".tword")[0]){
		var v = $(this).attr("class").replace(/.*(TERM[^ ]*)( .*)*/, '$1');
		$( "." + v ).addClass("hword");
		if(JQ_TOOLTIP){
			$(this).trigger( "mouseover" );
		}
	}
}

function word_hover_out () {
	$( ".hword" ).removeClass("hword");
	if(JQ_TOOLTIP)$('.ui-helper-hidden-accessible>div[style]').remove();
}

jQuery.fn.extend({
 tooltip_wsty_content: function() {
	var re = new RegExp("([" + DELIMITER + "])(?! )","g");
    var title = ($( this ).hasClass('mwsty'))? "<p><b style='font-size:120%'>" + $( this ).attr('data_text') + "</b></p>": "<p><b style='font-size:120%'>" + $( this ).text() + "</b></p>",
	roman = $( this ).attr('data_rom'),
	trans = $( this ).attr('data_trans').replace(re,"$1 "),
	statname='',
	status = parseInt($( this ).attr('data_status'));
	if(status==0)statname = 'Unknown [?]';
	else if(status<5)statname = 'Learning [' + status + ']';
	if(status==5)statname = 'Learned [5]';
	if(status==98)statname = 'Ignored [Ign]';
	if(status==99)statname = 'Well Known [WKn]';
	if (roman != '') {
		title += "<p><b>Roman.</b>: " + roman + "</p>";
	}
	if (trans != '' && trans != '*') {
		if($( this ).attr('data_ann')){
			var ann = $( this ).attr('data_ann');
			if(ann != '' && ann != '*'){
				var re = new RegExp("(.*[" + DELIMITER + "][ ]{0,1}|^)(" + ann.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ")($|[ ]{0,1}[" + DELIMITER + "].*$| \\[.*$)","");
				trans = trans.replace(re,'$1<span style="color:red">$2</span>$3');
			}
		}
		title += '<p><b>Transl.</b>: ' + trans + "</p>";
	}
	title += '<p><b>Status</b>: <span class="status' + status + '">' + statname + '</span></p>';
	return title;
	}
});

jQuery.fn.extend({
	 tooltip_wsty_init: function(){
		 $( this ).tooltip({
		      position: { my: "left top+10", at: "left bottom", collision: "flipfit" },
		      items: ".hword",
		      show: {easing: 'easeOutCirc'},
		      content: function() {return $(this).tooltip_wsty_content();}
			});
	 }
});

function keydown_event_do_text_text(e) {

	if (e.which == 27) {  // esc = reset all
		TEXTPOS = -1;
		$('span.uwordmarked').removeClass('uwordmarked');
		$('span.kwordmarked').removeClass('kwordmarked');
		cClick();
		return false;
	}

	if (e.which == 13) {  // return = edit next unknown word
		$('span.uwordmarked').removeClass('uwordmarked');
		var unknownwordlist = $('span.status0.word:not(.hide):first');
		if (unknownwordlist.size() == 0) return false;
		$(window).scrollTo(unknownwordlist,{axis:'y', offset:-150});
		unknownwordlist.addClass('uwordmarked').click();
		cClick();
		return false;
	}

	var knownwordlist = $('span.word:not(.hide):not(.status0)' + ADDFILTER + ',span.mword:not(.hide)' + ADDFILTER);
	var l_knownwordlist = knownwordlist.size();
	if (l_knownwordlist == 0 && e.which < 40) return true;

	// the following only for a non-zero known words list
	if (e.which == 36) {  // home : known word navigation -> first
		$('span.kwordmarked').removeClass('kwordmarked');
		TEXTPOS = 0;
		curr = knownwordlist.eq(TEXTPOS);
		curr.addClass('kwordmarked');
		$(window).scrollTo(curr,{axis:'y', offset:-150});
		var ann = '';
		if ((typeof curr.attr('data_ann')) != 'undefined')
			ann = curr.attr('data_ann');
		window.parent.frames['ro'].location.href = 'show_word.php?wid=' + curr.attr('data_wid') + '&ann=' + encodeURIComponent(ann);
		return false;
	}
	if (e.which == 35) {  // end : known word navigation -> last
		$('span.kwordmarked').removeClass('kwordmarked');
		TEXTPOS = l_knownwordlist-1;
		curr = knownwordlist.eq(TEXTPOS);
		curr.addClass('kwordmarked');
		$(window).scrollTo(curr,{axis:'y', offset:-150});
		var ann = '';
		if ((typeof curr.attr('data_ann')) != 'undefined')
			ann = curr.attr('data_ann');
		window.parent.frames['ro'].location.href = 'show_word.php?wid=' + curr.attr('data_wid') + '&ann=' + encodeURIComponent(ann);
		return false;
	}
	if (e.which == 37) {  // left : known word navigation
		$('span.kwordmarked').removeClass('kwordmarked');
		TEXTPOS--;
		if (TEXTPOS < 0) TEXTPOS = l_knownwordlist - 1;
		curr = knownwordlist.eq(TEXTPOS);
		curr.addClass('kwordmarked');
		$(window).scrollTo(curr,{axis:'y', offset:-150});
		var ann = '';
		if ((typeof curr.attr('data_ann')) != 'undefined')
			ann = curr.attr('data_ann');
		window.parent.frames['ro'].location.href = 'show_word.php?wid=' + curr.attr('data_wid') + '&ann=' + encodeURIComponent(ann);
		return false;
	}
	if (e.which == 39 || e.which == 32) {  // space /right : known word navigation
		$('span.kwordmarked').removeClass('kwordmarked');
		TEXTPOS++;
		if (TEXTPOS >= l_knownwordlist) TEXTPOS = 0;
		curr = knownwordlist.eq(TEXTPOS);
		curr.addClass('kwordmarked');
		$(window).scrollTo(curr,{axis:'y', offset:-150});
		var ann = '';
		if ((typeof curr.attr('data_ann')) != 'undefined')
			ann = curr.attr('data_ann');
		window.parent.frames['ro'].location.href = 'show_word.php?wid=' + curr.attr('data_wid') + '&ann=' + encodeURIComponent(ann);
		return false;
	}

	if ((!$(".kwordmarked, .uwordmarked")[0]) && $(".hword:hover")[0]){
		curr = $(".hword:hover");
	}
	else{
		if (TEXTPOS < 0 || TEXTPOS >= l_knownwordlist) return true;
		curr = knownwordlist.eq(TEXTPOS);
	}
	var wid = curr.attr('data_wid'),
	ord = curr.attr('data_order'),
	stat = curr.attr('data_status'),
	txt = (curr.hasClass( "mwsty" ))?curr.attr('data_text'):curr.text(),
	dict='';

	for (var i=1; i<=5; i++) {
		if (e.which == (48+i) || e.which == (96+i)) {  // 1,.. : status=i
			if(stat=='0'){
				if(i==1){
					var sl = WBLINK3.replace(/.*[?&]sl=([a-zA-Z\-]*)(&.*)*$/,'$1');
					var tl = WBLINK3.replace(/.*[?&]tl=([a-zA-Z\-]*)(&.*)*$/,'$1');
					if(sl!=WBLINK3 && tl!=WBLINK3)i=i+'&sl='+sl+'&tl='+tl;
				}
			window.parent.frames['ro'].location.href =
			'set_word_on_hover.php?text=' + txt + '&tid=' + TID + '&status='+i;
			}
			else {
				window.parent.frames['ro'].location.href =
					'set_word_status.php?wid=' + wid + '&tid=' + TID + '&ord=' + ord + '&status=' + i;
				return false;
			}
		}
	}
	if (e.which == 73) {  // I : status=98
		if(stat=='0'){window.parent.frames['ro'].location.href =
			'set_word_on_hover.php?text=' + txt + '&tid=' + TID + '&status=98';}
		else {
			window.parent.frames['ro'].location.href =
			'set_word_status.php?wid=' + wid + '&tid=' + TID + '&ord=' + ord + '&status=98';
		return false;
		}
	}
	if (e.which == 87) {  // W : status=99
		if(stat=='0'){window.parent.frames['ro'].location.href =
			'set_word_on_hover.php?text=' + txt + '&tid=' + TID + '&status=99';}
		else {
			window.parent.frames['ro'].location.href =
			'set_word_status.php?wid=' + wid + '&tid=' + TID + '&ord=' + ord + '&status=99';
		}
		return false;
	}
	if (e.which == 80) { // P : pronounce term
		var lg = WBLINK3.replace(/.*[?&]sl=([a-zA-Z\-]*)(&.*)*$/,'$1');
		var audio = new Audio();
		audio.src ='tts.php?tl=' + lg + '&q=' + txt;
		audio.play();
		return false;
	}
	if (e.which == 84) {  // T : translate sentence
		if((WBLINK3.substr(0,8) == '*http://') || (WBLINK3.substr(0,9) == '*https://')){
			owin('trans.php?x=1&i=' + ord + '&t=' + TID);
		}
		else if ((WBLINK3.substr(0,7) == 'http://') || (WBLINK3.substr(0,8) == 'https://') || (WBLINK3.substr(0,7) == 'ggl.php')) {
			window.parent.frames['ru'].location.href =
			'trans.php?x=1&i=' + ord + '&t=' + TID;
		}
		return false;
	}
	if (e.which == 65) {  // A : set audio pos.
		var p = curr.attr('data_pos');
		var t = parseInt($("#totalcharcount").text(),10);
		if ( t == 0 ) return true;
		p = 100 * (p-5) / t;
		if (p < 0) p = 0;
		if (typeof (window.parent.frames['h'].new_pos) == 'function')
			window.parent.frames['h'].new_pos(p);
		else
			return true;
		return false;
	}
	if (e.which == 71) { //  G : edit term and open GTr
		dict = '&nodict';
		setTimeout(function(){
			if((WBLINK3.substr(0,8) == '*http://') || (WBLINK3.substr(0,9) == '*https://'))
				owin(createTheDictUrl(WBLINK3.replace('*',''),txt));
			else
				window.parent.frames['ru'].location.href = createTheDictUrl(WBLINK3,txt);
		}, 10);
	}
	if (e.which == 69 || e.which == 71) { //  E / G: edit term
		if(curr.hasClass('mword'))
			window.parent.frames['ro'].location.href =
				'edit_mword.php?wid=' + wid + '&len=' + curr.attr('data_code') + '&tid=' + TID + '&ord=' + ord + dict;
		else if(stat=='0')
			window.parent.frames['ro'].location.href =
				'edit_word.php?wid=&tid=' + TID + '&ord=' + ord + dict;
		else {
			window.parent.frames['ro'].location.href =
				'edit_word.php?wid=' + wid + '&tid=' + TID + '&ord=' + ord + dict;
		}
		return false;
	}
	return true;
}

function do_ajax_save_setting(k, v) {
	$.post('ajax_save_setting.php', { k: k, v: v } );
}

function do_ajax_update_media_select() {
	$('#mediaselect').html('&nbsp; <img src="icn/waiting2.gif" />');
	$.post('ajax_update_media_select.php',
		function(data) { $('#mediaselect').html(data); }
	);
}

function do_ajax_show_sentences(lang,word,ctl,woid) {
	$('#exsent').html('<img src="icn/waiting2.gif" />');
	$.post('ajax_show_sentences.php', { lang: lang, word: word, ctl: ctl, woid: woid  },
		function(data) { $('#exsent').html(data); }
	);
}

function do_ajax_show_similar_terms() {
	$('#simwords').html('<img src="icn/waiting2.gif" />');
	$.post('ajax_show_similar_terms.php', { lang: $('#langfield').val(), word: $('#wordfield').val() },
		function(data) { $('#simwords').html(data); }
	);
}

function do_ajax_word_counts() {
	var t = $('.markcheck').map(function() {return $( this ).val();}).get().join( "," );
	$.post('ajax_word_counts.php', { id: t },
		function(data) {
			WORDCOUNTS=data;
			word_count_click();
			$('.barchart').removeClass('hide');
	},'json');
}

function set_word_counts(){
	$.each(WORDCOUNTS.totalu,function(key,value){
		var knownu = known = todo = stat0 = 0;
		var expr = WORDCOUNTS.expru[key]?parseInt((SUW&2)?WORDCOUNTS.expru[key]:WORDCOUNTS.expr[key]):0;
		if(!WORDCOUNTS.stat[key]){
			WORDCOUNTS.statu[key] = WORDCOUNTS.stat[key] = [];
		}
		$('#total_' + key).html((SUW&1?value:WORDCOUNTS.total[key]));
		$.each(WORDCOUNTS.statu[key],function(k,v){
			if(SUW&8)$('#stat_' + k + '_' + key).html(v);knownu += parseInt(v);
		});
		$.each(WORDCOUNTS.stat[key],function(k,v){
			if(!(SUW&8))$('#stat_' + k + '_' + key).html(v);known += parseInt(v);
		});
		$('#saved_' + key).html(known?((SUW&2?knownu:known)-expr + '+' + expr):0);
		todo = SUW&4?(parseInt(value) + parseInt( WORDCOUNTS.expru[key]||0) - parseInt(knownu)):(parseInt(WORDCOUNTS.total[key]) + parseInt( WORDCOUNTS.expr[key]||0) - parseInt(known));
		$('#todo_' + key).html(todo);
		stat0 = SUW&8?(parseInt(value) + parseInt( WORDCOUNTS.expru[key]||0) - parseInt(knownu)):(parseInt(WORDCOUNTS.total[key]) + parseInt( WORDCOUNTS.expr[key]||0) - parseInt(known));
		$('#stat_0_' + key).html(stat0);
	});
	$( '.barchart' ).each(function(){
		var id = $(this).find('span').first().attr('id').split('_')[2];
		var v = SUW&8?parseInt(WORDCOUNTS.expru[id]||0) + parseInt(WORDCOUNTS.totalu[id]):parseInt(WORDCOUNTS.expr[id]||0) + parseInt(WORDCOUNTS.total[id]);
		$(this).children('li').each(function(){
			var h = (v - $(this).children('span').text()) * 25 / v;
			$(this).css('border-top-width', h + 'px');
		});
	});
}

function word_count_click(){
	$(".wc_cont").children().each( function() {
		if(parseInt($(this).attr('data_wo_cnt'))==1){
			$(this).html("u");
		}
		else{
			$(this).html("t");
		}
		SUW = (parseInt($('#chart').attr('data_wo_cnt'))<<3) + (parseInt($('#unknown').attr('data_wo_cnt'))<<2) + (parseInt($('#saved').attr('data_wo_cnt'))<<1) + (parseInt($('#total').attr('data_wo_cnt')));
		set_word_counts();
	});
}

function do_ajax_edit_impr_text(pagepos, word) {
	if (word=='') $('#editimprtextdata').html('<img src="icn/waiting2.gif" />');
	var textid = $('#editimprtextdata').attr('data_id');
	$.post('ajax_edit_impr_text.php', { id: textid, word: word },
		function(data) {
			eval(data);
			$.scrollTo(pagepos);
			$('input.impr-ann-text').change(changeImprAnnText);
			$('input.impr-ann-radio').change(changeImprAnnRadio);
		}
	);
}

$.fn.serializeObject = function()
{
    var o = {};
    var a = this.serializeArray();
    $.each(a, function() {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};

$( window ).load(function(){
	$(":input,.wrap_checkbox span,.wrap_radio span,a:not([name^=rec]),select,#mediaselect span.click,#forwbutt,#backbutt").each(function (i) { $(this).attr('tabindex', i + 1); });
	$(".wrap_radio span").bind("keydown", function(e){
		if(e.keyCode==32){
		$(this).parent().parent().find('input[type=radio]').trigger( "click" );
		return false;
		}
	});
});

$(document).ready( function() {
	$('.edit_area').editable('inline_edit.php',
		{
			type      : 'textarea',
			indicator : '<img src="icn/indicator.gif">',
			tooltip   : 'Click to edit...',
			submit    : 'Save',
			cancel    : 'Cancel',
			rows      : 3,
			cols      : 35
		}
	);
	$( "select" ).wrap( "<label class='wrap_select'></label>" );
	$('form').attr('autocomplete','off');
	$( 'input[type="file"]' ).each(function(){
		if(!$(this).is(":visible")){
			$(this).before('<button class="button-file">Choose File</button>')
			 .after('<span style="position:relative" class="fakefile"></span>')
			 .on('change',function () {
				var txt=this.value.replace('C:\\fakepath\\','');
				if(txt.length > 85)txt=txt.replace(/.*(.{80})$/, ' ... $1');
				$(this).next().text(txt);
			 })
			 .on('onmouseout',function () {
				var txt=this.value.replace('C:\\fakepath\\','');
				if(txt.length > 85)txt=txt.replace(/.*(.{80})$/, ' ... $1');
				$(this).next().text(txt);
			 });
		}
	});
	$( 'input[type="checkbox"]' ).each(function(z){
		if(typeof z == 'undefined')z=1;
		if(typeof $(this).attr('id') == 'undefined'){
			$(this).attr('id','cb_'+z++);
		}
		$(this).after('<label class="wrap_checkbox" for="'+$(this).attr('id')+'"><span></span></label>');
	});
	$('span[class*="tts_"]').click( function() {
		var lg = $(this).attr("class").replace(/.*tts_([a-zA-Z-]+).*/, '$1');
		var txt = $(this).text();
		var audio = new Audio();
		audio.src ='tts.php?tl=' + lg + '&q=' + txt;
		audio.play();
	});
	$(document).mouseup(function(){
		$("button,input[type=button],.wrap_radio span,.wrap_checkbox span").blur();
	});
	$(".wrap_checkbox span").bind("keydown", function(e){
		if(e.keyCode==32){
		$(this).parent().parent().find('input[type=checkbox]').trigger( "click" );
		return false;
		}
	});
	$( 'input[type="radio"]' ).each(function(z){
		if(typeof z == 'undefined')z=1;
		if(typeof $(this).attr('id') == 'undefined'){
			$(this).attr('id','rb_'+z++);
		}
		$(this).after('<label class="wrap_radio" for="'+$(this).attr('id')+'"><span></span></label>');
	});
	$('.button-file').click(function(){$(this).next('input[type="file"]').click();return false;});
	$('input.impr-ann-text').change(changeImprAnnText);
	$('input.impr-ann-radio').change(changeImprAnnRadio);
	$('form.validate').submit(check);
	$('input.markcheck').click(markClick);
	$('#showallwords').click(textModeChangeClick);
	$('#showlearningtranslations').click(textModeChangeClick);
	$('textarea.textarea-noreturn').keydown(textareaKeydown);
	$('#termtags').tagit(
		{
			availableTags : TAGS,
			fieldName : 'TermTags[TagList][]'
		}
	);
	$('#texttags').tagit(
		{
			availableTags : TEXTTAGS,
			fieldName : 'TextTags[TagList][]'
		}
	);
	markClick();
	setTheFocus();
	if ($('#simwords').length > 0 && $('#langfield').length > 0 && $('#wordfield').length > 0) {
  	$('#wordfield').blur(do_ajax_show_similar_terms);
  	do_ajax_show_similar_terms();
	}
	window.setTimeout(noShowAfter3Secs,3000);
} );
