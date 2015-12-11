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
PHP Utility Functions
Plus (at end): Database Connect, .. Select, .. Updates
***************************************************************/

function load_feeds($currentfeed){
	global $tbpref;
	$cnt=0;
	$ajax=$feeds=array();
	echo '<script type="text/javascript">';
		if (isset($_REQUEST['check_autoupdate'])) {
			$c_feeds=array();
			$result = do_mysql_query("SELECT * FROM " . $tbpref . "newsfeeds where `NfOptions` like '%autoupdate=%'");
			while($row = mysqli_fetch_assoc($result)){
				if($autoupdate=get_nf_option($row['NfOptions'],'autoupdate')){
					if(strpos($autoupdate,'h')!==FALSE){
						$autoupdate=str_replace ('h','',$autoupdate);
						$autoupdate=60 * 60 * $autoupdate;
					}
					elseif(strpos($autoupdate,'d')!==FALSE){
						$autoupdate=str_replace ('d','',$autoupdate);
						$autoupdate=60 * 60 * 24 * $autoupdate;
					}
					elseif(strpos($autoupdate,'w')!==FALSE){
						$autoupdate=str_replace ('w','',$autoupdate);
						$autoupdate=60 * 60 * 24 * 7 * $autoupdate;
					}
					else continue;
					if(time()>($autoupdate + $row['NfUpdate'])){
						$ajax[$cnt]=  "$.ajax({type: 'POST',beforeSend: function(){ $('#feed_" . $row['NfID'] . "').replaceWith( '<div id=\"feed_" . $row['NfID'] . "\" class=\"msgblue\"><p>". addslashes($row['NfName']).": loading</p></div>' );},url:'ajax_load_feed.php', data: { NfID: '".$row['NfID']."', NfSourceURI: '". $row['NfSourceURI']."', NfName: '". addslashes($row['NfName'])."', NfOptions: '". $row['NfOptions']."', cnt: '". $cnt."' },success:function (data) {feedcnt+=1;$('#feedcount').text(feedcnt);$('#feed_" . $row['NfID'] . "').replaceWith( data );}})";
						$cnt+=1;
						$feeds[$row['NfID']]=$row['NfName'];
					}
				}
			}
			mysqli_free_result($result);
		}
		else{
		$sql="SELECT * FROM " . $tbpref . "newsfeeds WHERE NfID in ($currentfeed)";
		$result = do_mysql_query($sql);
		while($row = mysqli_fetch_assoc($result)){
			$ajax[$cnt]=  "$.ajax({type: 'POST',beforeSend: function(){ $('#feed_" . $row['NfID'] . "').replaceWith( '<div id=\"feed_" . $row['NfID'] . "\" class=\"msgblue\"><p>". addslashes($row['NfName']).": loading</p></div>' );},url:'ajax_load_feed.php', data: { NfID: '".$row['NfID']."', NfSourceURI: '". $row['NfSourceURI']."', NfName: '". addslashes($row['NfName'])."', NfOptions: '". $row['NfOptions']."', cnt: '". $cnt."' },success:function (data) {feedcnt+=1;$('#feedcount').text(feedcnt);$('#feed_" . $row['NfID'] . "').replaceWith( data );}})";
			$cnt+=1;
			$feeds[$row['NfID']]=$row['NfName'];
		}
		mysqli_free_result($result);
	}
	if(!empty($ajax)){
		$z=array();
		for($i=1;$i<=$cnt;$i++){
			$z[]='a'.$i;
		}
		echo "feedcnt=0;\n";
		echo '$(document).ready(function(){ $.when(',implode(',',$ajax),").then(function(",implode(',',$z),"){window.location.replace(\"",$_SERVER['PHP_SELF'],"\");});});";
	}
	else echo "window.location.replace(\"",$_SERVER['PHP_SELF'],"\");";
	echo "\n</script>\n";
	if($cnt!=1)echo "<div class=\"msgblue\"><p>UPDATING <span id=\"feedcount\">0</span>/",$cnt," FEEDS</p></div>";
	foreach($feeds as $k=>$v){
		echo "<div id='feed_$k' class=\"msgblue\"><p>". $v.": waiting</p></div>";
	}
	echo "<div class=\"center\"><button onclick='window.location.replace(\"",$_SERVER['PHP_SELF'],"\");'>Continue</button></div>";
}

// -------------------------------------------------------------


function write_rss_to_db($texts){
	global $tbpref;
	$texts=array_reverse($texts);
	$message1=$message2=$message3=$message4=0;
	foreach($texts as $text){
		$Nf_ID[]=$text['Nf_ID'];
	}
	$Nf_ID=array_unique ($Nf_ID);
	$Nf_tag='';
	foreach($Nf_ID as $feed_ID){
		foreach($texts as $text){
			if($feed_ID==$text['Nf_ID']){
				if($Nf_tag!='"'.implode('","', $text['TagList']).'"'){
					$Nf_tag= '"'.implode('","', $text['TagList']).'"';
					foreach($text['TagList'] as $tag){
						if(! in_array($tag, $_SESSION['TEXTTAGS'])) {
							do_mysql_query('insert into ' . $tbpref . 'tags2 (T2Text) values (' . convert_string_to_sqlsyntax($tag) . ')');
						}
					}
					$nf_max_texts=$text['Nf_Max_Texts'];
				}
			echo '<div class="msgblue"><p class="hide_message">+++ "' . $text['TxTitle']. '" added! +++</p></div>';
			do_mysql_query('INSERT INTO ' . $tbpref . 'texts (TxLgID,TxTitle,TxText,TxAudioURI,TxSourceURI)VALUES ('.$text['TxLgID'].',' . convert_string_to_sqlsyntax($text['TxTitle']) .','. convert_string_to_sqlsyntax($text['TxText']) .','. convert_string_to_sqlsyntax($text['TxAudioURI']) .','.convert_string_to_sqlsyntax($text['TxSourceURI']) .')');
			$id = get_last_key();
			splitCheckText(
			get_first_value(
			'select TxText as value from ' . $tbpref . 'texts where TxID = ' . $id), 
			get_first_value(
			'select TxLgID as value from ' . $tbpref . 'texts where TxID = ' . $id), 
			$id );
			do_mysql_query('insert into ' . $tbpref . 'texttags (TtTxID, TtT2ID) select ' . $id . ', T2ID from ' . $tbpref . 'tags2 where T2Text in (' . $Nf_tag .')');		
			}
		}
		get_texttags(1);
		$result=do_mysql_query("SELECT TtTxID FROM " . $tbpref . "texttags join " . $tbpref . "tags2 on TtT2ID=T2ID WHERE T2Text in (". $Nf_tag .")");
		$text_count=0;
		while($row = mysqli_fetch_assoc($result)){
			$text_item[$text_count++]=$row['TtTxID'];
		}
		mysqli_free_result($result);
		if($text_count>$nf_max_texts){
			sort($text_item,SORT_NUMERIC);
			$text_item=array_slice($text_item, 0,$text_count-$nf_max_texts);
			foreach ($text_item as $text_ID){
				$message3 += runsql('delete from ' . $tbpref . 'textitems2 where Ti2TxID = ' . $text_ID, 
				"");
				$message2 += runsql('delete from ' . $tbpref . 'sentences where SeTxID = ' . $text_ID, 
				"");
				$message4 += runsql('insert into ' . $tbpref . 'archivedtexts (AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI) select TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI from ' . $tbpref . 'texts where TxID = ' . $text_ID, "");
				$id = get_last_key();
				runsql('insert into ' . $tbpref . 'archtexttags (AgAtID, AgT2ID) select ' . $id . ', TtT2ID from ' . $tbpref . 'texttags where TtTxID = ' . $text_ID, "");	
				$message1 += runsql('delete from ' . $tbpref . 'texts where TxID = ' . $text_ID, "");
//				$message .= $message4 . " / " . $message1 . " / " . $message2 . " / " . $message3;
				adjust_autoincr('texts','TxID');
				adjust_autoincr('sentences','SeID');
				runsql("DELETE " . $tbpref . "texttags FROM (" . $tbpref . "texttags LEFT JOIN " . $tbpref . "texts on TtTxID = TxID) WHERE TxID IS NULL",'');		
			}
		}
	}
	if($message4>0 || $message1>0)return "Texts archived: " . $message1 . " / Sentences deleted: " . $message2 . " / Text items deleted: " . $message3;
	else return '';
}

// -------------------------------------------------------------

function print_last_feed_update($diff){
	$periods = array(
		array(60 * 60 * 24 * 365 , 'year'),
		array(60 * 60 * 24 * 30 , 'month'),
		array(60 * 60 * 24 * 7, 'week'),
		array(60 * 60 * 24 , 'day'),
		array(60 * 60 , 'hour'),
		array(60 , 'minute'),
		array(1 , 'second'),
	);
	if($diff>=1){
		for($key=0;$key<7;$key++){
			$x=intval($diff/$periods[$key][0]);
			if($x>=1){
				echo " last update: $x ";
				print_r($periods[$key][1]);
				if($x>1)echo 's';echo ' ago';break;
			}
		}
	}
	else echo ' up to date';
}

// -------------------------------------------------------------

function get_nf_option($str,$option){
	$arr=explode(',',$str);
	if($option=='all') $all=array();
	foreach($arr as $value){
		$res=explode('=',$value);
		if(trim($res[0])==$option)return $res[1];
		if($option=='all') $all[$res[0]]=$res[1];
	}
	if($option=='all') return $all;
	return NULL;
}

// -------------------------------------------------------------

function get_links_from_new_feed($NfSourceURI){
	$rss = new DOMDocument('1.0', 'utf-8');
	if(!$rss->load($NfSourceURI,LIBXML_NOCDATA | ENT_NOQUOTES))return false;
	$rss_data = array();
	$desc_count=0;
	$desc_nocount=0;
	$enc_count=0;
	$enc_nocount=0;
	if($rss->getElementsByTagName('rss')->length !== 0){
		$feed_tags=array('item' => 'item','title' => 'title','description' => 'description','link' => 'link');
	}
	elseif($rss->getElementsByTagName('feed')->length !== 0){
		$feed_tags=array('item' => 'entry','title' => 'title','description' => 'summary','link' => 'link');
	}
	else return false;
	foreach ($rss->getElementsByTagName($feed_tags['item']) as $node) {
		$item = array ( 
			'title' => preg_replace( array('/\s\s+/','/\ \&\ /','/\"/'), array(' ',' &amp; ','\"'), trim($node->getElementsByTagName($feed_tags['title'])->item(0)->nodeValue)),
			'desc' => preg_replace( array('/\s\s+/','/\ \&\ /','/\<[^\>]*\>/','/\"/'), array(' ',' &amp; ','','\"'), trim($node->getElementsByTagName($feed_tags['description'])->item(0)->nodeValue)),
			'link' => trim(($feed_tags['item']=='entry')?($node->getElementsByTagName($feed_tags['link'])->item(0)->getAttribute('href')):($node->getElementsByTagName($feed_tags['link'])->item(0)->nodeValue)),
		);
		if($feed_tags['item']=='item'){
			foreach($node->getElementsByTagName('encoded') as $txt_node) {
				if($txt_node->parentNode===$node){
					$item['encoded'] = $txt_node->ownerDocument->saveHTML($txt_node);
					$item['encoded']=mb_convert_encoding(html_entity_decode($item['encoded'], ENT_NOQUOTES, "UTF-8"),"HTML-ENTITIES","UTF-8");
				}
			}
			foreach($node->getElementsByTagName('description') as $txt_node) {
				if($txt_node->parentNode===$node){
					$item['description'] = $txt_node->ownerDocument->saveHTML($txt_node);
					$item['description']=mb_convert_encoding(html_entity_decode($item['description'], ENT_NOQUOTES, "UTF-8"),"HTML-ENTITIES","UTF-8");
				}
			}
			if (isset($item['desc'])){
				if( mb_strlen($item['desc'], "UTF-8")>900)$desc_count++;
				else $desc_nocount++;
			}
			if (isset($item['encoded'])){
				if( mb_strlen($item['encoded'], "UTF-8")>900)$enc_count++;
				else $enc_nocount++;
			}
		}
		if($feed_tags['item']=='entry'){
			foreach($node->getElementsByTagName('content') as $txt_node) {
				if($txt_node->parentNode===$node){
					$item['content'] = $txt_node->ownerDocument->saveHTML($txt_node);
					$item['content']=mb_convert_encoding(html_entity_decode($item['content'], ENT_NOQUOTES, "UTF-8"),"HTML-ENTITIES","UTF-8");
				}
			}
			if (isset($item['content'])){
				if( mb_strlen($item['content'], "UTF-8")>900)$desc_count++;
				else $desc_nocount++;
			}
		}
		if($item['title']!="" && $item['link']!="")array_push($rss_data, $item);
	}
		if($desc_count > $desc_nocount){
			$source=($feed_tags['item']=='entry')?('content'):('description');
			$rss_data['feed_text']=$source;
				foreach ($rss_data as $i=>$val){
					$rss_data[$i]['text']=$rss_data[$i][$source];
				}
		}
		else{
			if($enc_count > $enc_nocount){
				$rss_data['feed_text']='encoded';
				foreach ($rss_data as $i=>$val){
					$rss_data[$i]['text']=$rss_data[$i]['encoded'];
				}
			}
		}
		for ($i=0;$i<count($rss_data);$i++){
//		unset($rss_data[$i]['encoded']);unset($rss_data[$i]['description']);unset($rss_data[$i]['content']);
		}
		$rss_data['feed_title']=$rss->getElementsByTagName('title')->item(0)->nodeValue;
		($feed_tags['item']=='entry')?($rss->getElementsByTagName('feed')->item(0)->getAttribute('lang')):($rss->getElementsByTagName('language')->item(0)->nodeValue);
	return $rss_data;
}

// -------------------------------------------------------------

function get_links_from_rss($NfSourceURI,$NfArticleSection){
	$rss = new DOMDocument('1.0', 'utf-8');
	if(!$rss->load($NfSourceURI,LIBXML_NOCDATA | ENT_NOQUOTES))return false;
	$rss_data = array();
	if($rss->getElementsByTagName('rss')->length !== 0){$feed_tags=array('item' => 'item','title' => 'title','description' => 'description','link' => 'link','pubDate' => 'pubDate','enclosure' => 'enclosure','url' => 'url');}
	elseif($rss->getElementsByTagName('feed')->length !== 0){$feed_tags=array('item' => 'entry','title' => 'title','description' => 'summary','link' => 'link','pubDate' => 'published','enclosure' => 'link','url' => 'href');}
	else return false;
	foreach ($rss->getElementsByTagName($feed_tags['item']) as $node) {
		$item = array (
			'title' => preg_replace( array('/\s\s+/','/\ \&\ /'), array(' ',' &amp; '), trim($node->getElementsByTagName($feed_tags['title'])->item(0)->nodeValue)),
			'desc' => isset($node->getElementsByTagName($feed_tags['description'])->item(0)->nodeValue)?preg_replace( array('/\ \&\ /','/<br(\s+)?\/?>/i','/<br [^>]*?>/i','/\<[^\>]*\>/','/(\n)[\s^\n]*\n[\s]*/'), array(' &amp; ',"\n","\n",'','$1$1'), trim($node->getElementsByTagName($feed_tags['description'])->item(0)->nodeValue)):'',
			'link' => trim(($feed_tags['item']=='entry')?($node->getElementsByTagName($feed_tags['link'])->item(0)->getAttribute('href')):($node->getElementsByTagName($feed_tags['link'])->item(0)->nodeValue)),
			'date' => isset($node->getElementsByTagName($feed_tags['pubDate'])->item(0)->nodeValue)?trim($node->getElementsByTagName($feed_tags['pubDate'])->item(0)->nodeValue):NULL,
		);
		$pubDate = date_parse_from_format('D, d M Y H:i:s T',$item['date']);
		if($pubDate['error_count']>0){
			$item['date'] = date("Y-m-d H:i:s",time()-count($rss_data));
		}
		else{
			$item['date'] = date("Y-m-d H:i:s", mktime($pubDate['hour'], $pubDate['minute'], $pubDate['second'], $pubDate['month'], $pubDate['day'], $pubDate['year']));
		}
		if(strlen ($item['desc'])>1000)$item['desc']=mb_substr($item['desc'],0,995, "utf-8") . '...';
		if ($NfArticleSection){
			foreach ($node->getElementsByTagName($NfArticleSection) as $txt_node) {
				if($txt_node->parentNode===$node){
					$item['text'] = $txt_node->ownerDocument->saveHTML($txt_node);
					$item['text']=mb_convert_encoding(html_entity_decode($item['text'], ENT_NOQUOTES, "UTF-8"),"HTML-ENTITIES","UTF-8");
					//$item['text']=str_replace ('"','\"',$item['text']);///////////////
				}
			}
		}
		$item['audio'] = "";
		foreach($node->getElementsByTagName($feed_tags['enclosure']) as $enc){
			$type=$enc->getAttribute('type');
			if($type=="audio/mpeg")$item['audio']=$enc->getAttribute($feed_tags['url']);
		}
		if($item['title']!="" && ($item['link']!="" || ($NfArticleSection!="" && !empty($item['text']))))array_push($rss_data, $item);
	}
	return $rss_data;
}

// -------------------------------------------------------------

function get_text_from_rsslink($feed_data,$NfArticleSection,$NfFilterTags,$NfCharset=NULL){
	global $tbpref;
	foreach ($feed_data as $key =>$val){
		if(strncmp($NfArticleSection, 'redirect:', 9)==0){	
			$dom = new DOMDocument;
			$HTMLString = file_get_contents(trim($feed_data[$key]['link']));
			$dom->loadHTML($HTMLString);
			$xPath = new DOMXPath($dom);
			$redirect = explode(" | ", $NfArticleSection,2);
			$NfArticleSection=$redirect[1];
			$redirect = substr ($redirect[0],9);
			$feed_host = parse_url(trim($feed_data[$key]['link']));
			foreach($xPath->query($redirect) as $node){
				$len=$node->attributes->length;
				for($i=0;$i<$len;$i++){
					if($node->attributes->item($i)->name=='href'){
						$feed_data[$key]['link'] = $node->attributes->item($i)->value;
						if(strncmp($feed_data[$key]['link'], '..', 2)==0){
							$feed_data[$key]['link'] = 'http://'.$feed_host['host'] . substr ($feed_data[$key]['link'],2);
						}
					}
				}	
			}
			unset($dom);
			unset($HTMLString);
			unset($xPath);
		}
		$data[$key]['TxTitle'] = $feed_data[$key]['title'];
		$data[$key]['TxAudioURI'] = isset($feed_data[$key]['audio'])?$feed_data[$key]['audio']:(NULL);
		$data[$key]['TxText'] = "";
		if(isset($feed_data[$key]['text'])){
			if($feed_data[$key]['text']==""){
				unset($feed_data[$key]['text']);
			}
		}
		if(isset($feed_data[$key]['text'])){
			$link = trim($feed_data[$key]['link']);
			if(substr($link,0,1)=='#'){
				runsql('UPDATE ' . $tbpref . 'feedlinks SET FlLink=' . convert_string_to_sqlsyntax($link) . ' where FlID = ' .substr($link,1) , "");
			}
			$data[$key]['TxSourceURI'] = $link;
			$HTMLString=str_replace (array('>','<'),array('> ',' <'),$feed_data[$key]['text']);//$HTMLString=str_replace (array('>','<'),array('> ',' <'),$HTMLString);
		}
		else{
			$data[$key]['TxSourceURI'] = $feed_data[$key]['link'];
			$HTMLString = file_get_contents(trim($data[$key]['TxSourceURI']));
			if(!empty($HTMLString)){
				$encod  = '';
				if(empty($NfCharset)){
					
					$header=get_headers(trim($data[$key]['TxSourceURI']), 1);
					foreach($header as $k=>$v){
						if(strtolower($k)=='content-type'){
							if(is_array($v)){
								$encod=$v[count($v)-1];
							}
							else{
								$encod=$v;
							}
							$pos = strpos($encod, 'charset=');
							if(($pos!==FALSE) && (strpos($encod, 'text/html;')!==FALSE)){
								$encod=substr($encod,$pos+8);	
								break;
							}
							else $encod='';
						}
						
					}
				}
				else{
					if($NfCharset!='meta')$encod  = $NfCharset;
				}
				
				if(empty($encod)){
					$doc = new DomDocument;
					$previous_value = libxml_use_internal_errors(TRUE);
					$doc->loadHTML($HTMLString);
					/*
					if (!$doc->loadHTML($HTMLString)) {
					 foreach (libxml_get_errors() as $error) {
					 // handle errors here
					}*/
					libxml_clear_errors();
					libxml_use_internal_errors($previous_value);
					$nodes=$doc->getElementsByTagName('meta');
					foreach($nodes as $node){
						$len=$node->attributes->length;
						for($i=0;$i<$len;$i++){
							if($node->attributes->item($i)->name=='content'){
								$pos = strpos($node->attributes->item($i)->value, 'charset=');
								if($pos){
									$encod=substr($node->attributes->item($i)->value,$pos+8);
									unset($doc);
									unset($nodes);
									break 2;	
								}
							}
						}	
					}
				if(empty($encod)){
					foreach($nodes as $node){
						$len=$node->attributes->length;
						if($len=='1'){
							if($node->attributes->item(0)->name=='charset'){

									$encod=$node->attributes->item(0)->value;
									break;	
								}
							}
						}	
					}
				}
				unset($doc);
				unset($nodes);
				if(empty($encod)){
					mb_detect_order("ASCII,UTF-8,ISO-8859-1,windows-1252,iso-8859-15");
					$encod  = mb_detect_encoding($HTMLString);
				}
				$chset=$encod;
				switch($encod){
					case 'windows-1253':
						$chset='el_GR.utf8';
						break;
					case 'windows-1254':
						$chset='tr_TR.utf8';
						break;
					case 'windows-1255':
						$chset='he.utf8';
						break;
					case 'windows-1256':
						$chset='ar_AE.utf8';
						break;
					case 'windows-1258':
						$chset='vi_VI.utf8';
						break;
					case 'windows-874':
						$chset='th_TH.utf8';
						break;
				}
				$HTMLString = '<meta http-equiv="Content-Type" content="text/html; charset='. $chset .'">' .$HTMLString;
				if($encod!=$chset)$HTMLString = iconv($encod, 'utf-8', $HTMLString);
				else $HTMLString=mb_convert_encoding($HTMLString, 'HTML-ENTITIES', $encod);
			}
		}
$HTMLString=str_replace(array('<br />','<br>','</br>','</h','</p'),array("\n","\n","","\n</h","\n</p"),$HTMLString);
		$dom = new DOMDocument();
		$previous_value = libxml_use_internal_errors(TRUE);

		$dom->loadHTML('<?xml encoding="UTF-8">' . $HTMLString);
		foreach ($dom->childNodes as $item){/////////////////////////////////
			if ($item->nodeType == XML_PI_NODE){
				$dom->removeChild($item); // remove hack
			}
		}
		$dom->encoding = 'UTF-8'; // insert proper	//////////////////////////////

		/*
		if (!$dom->loadHTML($HTMLString)) {
		 foreach (libxml_get_errors() as $error) {
		 // handle errors here
		}*/
		libxml_clear_errors();
		libxml_use_internal_errors($previous_value);
		$filter_tags = explode("!?!", rtrim("//img | //script | //meta | //noscript | //link | //iframe!?!".$NfFilterTags,"!?!"));
		foreach (explode("!?!", $NfArticleSection) as $article_tag) {
			if($article_tag=='new'){
				foreach ($filter_tags as $filter_tag){
					$nodes=$dom->getElementsByTagName($filter_tag);
					$domElemsToRemove = array();
					foreach ( $nodes as $domElement ) {
						$domElemsToRemove[] = $domElement;
					}
					foreach ($domElemsToRemove as $node) {
					   $node->parentNode->removeChild($node);
					}
				}
				$nodes=$dom->getElementsByTagName('*');
				foreach ( $nodes as $node ) {
					$node->removeAttribute('onclick');
				}
				$str=$dom->saveHTML($dom);
			//$str=mb_convert_encoding(html_entity_decode($str, ENT_NOQUOTES, "UTF-8"),"HTML-ENTITIES","UTF-8");
				return preg_replace(array('/\<html[^\>]*\>/','/\<body\>/'),array('',''),$str);
			}
		}
		$selector = new DOMXPath($dom);
		foreach ($filter_tags as $filter_tag){
			foreach ($selector->query($filter_tag) as $node) {
				$node->parentNode->removeChild($node);
			}
		}
		if(isset($feed_data[$key]['text'])){
			foreach ($selector->query($NfArticleSection) as $text_temp) {
				if(isset($text_temp->nodeValue)){
					$data[$key]['TxText'] .= mb_convert_encoding($text_temp->nodeValue,"HTML-ENTITIES","UTF-8");
				}
			}
			$data[$key]['TxText'] = html_entity_decode($data[$key]['TxText'], ENT_NOQUOTES, "UTF-8");
		}		
		else{
			$article_tags = explode("!?!", $NfArticleSection);if(strncmp($NfArticleSection, 'redirect:', 9)==0)unset($article_tags[0]);
			foreach ($article_tags as $article_tag) {
				foreach ($selector->query($article_tag) as $text_temp) {
					if(isset($text_temp->nodeValue)){
						$data[$key]['TxText'].= $text_temp->nodeValue;
					}
				}
			}
		}		
				
		if($data[$key]['TxText']==""){
			unset($data[$key]);
			$data['error']['message'].= '"<a href=' . $feed_data[$key]['link'] .' onclick="window.open(this.href, \'child\'); return false">'  . $feed_data[$key]['title'] . '</a>" has no text section!<br />';
			$data['error']['link'][]=$feed_data[$key]['link'];
		}
		else{$data[$key]['TxText']=trim(preg_replace(array('/[\r\t]+/','/(\n)[\s^\n]*\n[\s]*/','/\ \ +/'), array(' ','$1$1',' '), $data[$key]['TxText']));
			//$data[$key]['TxText']=trim(preg_replace(array('/[\s^\n]+/','/(\n)[\s^\n]*\n[\s]*/','/\ +/','/[ ]*(\n)/'), array(' ','$1$1',' ','$1'), $data[$key]['TxText']));
		}
	}
	return $data;
}


// -------------------------------------------------------------

function get_version() {
	global $debug;
	return '1.6.22 (November 11 2015)'  . 
	($debug ? ' <span class="red">DEBUG</span>' : '');
}

// -------------------------------------------------------------

function get_version_number() {
	$r = 'v';
	$v = get_version();
	$pos = strpos($v,' ',0);
	if ($pos === false) my_die ('Wrong version: '. $v);
	$vn = preg_split ("/[.]/", substr($v,0,$pos));
	if (count($vn) < 3) my_die ('Wrong version: '. $v);
	for ($i=0; $i<3; $i++) $r .= substr('000' . $vn[$i],-3);
	return $r;  // 'vxxxyyyzzz' wenn version = x.y.z
}

// -------------------------------------------------------------

function my_die($text) {
	echo '</select></p></div><div style="padding: 1em; color:red; font-size:120%; background-color:#CEECF5;">' .
		'<p><b>Fatal Error:</b> ' . 
		tohtml($text) . 
		"</p></div><hr /><pre>Backtrace:\n\n";
	debug_print_backtrace ();
	echo '</pre><hr />';
	die('</body></html>');
}

// -------------------------------------------------------------

function stripTheSlashesIfNeeded($s) {
	if(get_magic_quotes_gpc())
		return stripslashes($s);
	else
		return $s;
}

// -------------------------------------------------------------

function getPreviousAndNextTextLinks($textid,$url,$onlyann,$add) {
	global $tbpref;
	$currentlang = validateLang(processDBParam("filterlang",'currentlanguage','',0));
	$wh_lang = ($currentlang != '') ? (' and TxLgID=' . $currentlang) : '';

	$currentquery = processSessParam("query","currenttextquery",'',0);
	$currentquerymode = processSessParam("query_mode","currenttextquerymode",'title,text',0);
	$currentregexmode = getSettingWithDefault("set-regex-mode");
	$wh_query = $currentregexmode . 'like ' .  convert_string_to_sqlsyntax(($currentregexmode == '') ? (str_replace("*","%",mb_strtolower($currentquery, 'UTF-8'))) : ($currentquery));
	switch($currentquerymode){
		case 'title,text':
			$wh_query=' and (TxTitle ' . $wh_query . ' or TxText ' . $wh_query . ')';
			break;
		case 'title':
			$wh_query=' and (TxTitle ' . $wh_query . ')';
			break;
		case 'text':
			$wh_query=' and (TxText ' . $wh_query . ')';
			break;
	}
	if($currentquery=='') $wh_query = '';

	$currenttag1 = validateTextTag(processSessParam("tag1","currenttexttag1",'',0),$currentlang);
	$currenttag2 = validateTextTag(processSessParam("tag2","currenttexttag2",'',0),$currentlang);
	$currenttag12 = processSessParam("tag12","currenttexttag12",'',0);
	if ($currenttag1 == '' && $currenttag2 == '')
		$wh_tag = '';
	else {
		if ($currenttag1 != '') {
			if ($currenttag1 == -1)
				$wh_tag1 = "group_concat(TtT2ID) IS NULL";
			else
				$wh_tag1 = "concat('/',group_concat(TtT2ID separator '/'),'/') like '%/" . $currenttag1 . "/%'";
		}
		if ($currenttag2 != '') {
			if ($currenttag2 == -1)
				$wh_tag2 = "group_concat(TtT2ID) IS NULL";
			else
				$wh_tag2 = "concat('/',group_concat(TtT2ID separator '/'),'/') like '%/" . $currenttag2 . "/%'";
		}
		if ($currenttag1 != '' && $currenttag2 == '')	
			$wh_tag = " having (" . $wh_tag1 . ') ';
		elseif ($currenttag2 != '' && $currenttag1 == '')	
			$wh_tag = " having (" . $wh_tag2 . ') ';
		else
			$wh_tag = " having ((" . $wh_tag1 . ($currenttag12 ? ') AND (' : ') OR (') . $wh_tag2 . ')) ';
	}

	$currentsort = processDBParam("sort",'currenttextsort','1',1);
	$sorts = array('TxTitle','TxID desc','TxID asc');
	$lsorts = count($sorts);
	if ($currentsort < 1) $currentsort = 1;
	if ($currentsort > $lsorts) $currentsort = $lsorts;

	if ($onlyann) 
		$sql = 'select TxID from ((' . $tbpref . 'texts left JOIN ' . $tbpref . 'texttags ON TxID = TtTxID) left join ' . $tbpref . 'tags2 on T2ID = TtT2ID), ' . $tbpref . 'languages where LgID = TxLgID AND LENGTH(TxAnnotatedText) > 0 ' . $wh_lang . $wh_query . ' group by TxID ' . $wh_tag . ' order by ' . $sorts[$currentsort-1];
	else
		$sql = 'select TxID from ((' . $tbpref . 'texts left JOIN ' . $tbpref . 'texttags ON TxID = TtTxID) left join ' . $tbpref . 'tags2 on T2ID = TtT2ID), ' . $tbpref . 'languages where LgID = TxLgID ' . $wh_lang . $wh_query . ' group by TxID ' . $wh_tag . ' order by ' . $sorts[$currentsort-1];

	$list = array(0);
	$res = do_mysql_query($sql);		
	while ($record = mysqli_fetch_assoc($res)) {
		array_push($list, ($record['TxID']+0));
	}
	mysqli_free_result($res);
	array_push($list, 0);
	$listlen = count($list);
	for ($i=1; $i < $listlen-1; $i++) {
		if($list[$i] == $textid) {
			if ($list[$i-1] !== 0) {
				$title = tohtml(getTextTitle($list[$i-1]));
				$prev = '<a href="' . $url . $list[$i-1] . '" target="_top"><img src="icn/navigation-180-button.png" title="Previous Text: ' . $title . '" alt="Previous Text: ' . $title . '" /></a>';
			}
			else
				$prev = '<img src="icn/navigation-180-button-light.png" title="No Previous Text" alt="No Previous Text" />';
			if ($list[$i+1] !== 0) {
				$title = tohtml(getTextTitle($list[$i+1]));
				$next = '<a href="' . $url . $list[$i+1] . '" target="_top"><img src="icn/navigation-000-button.png" title="Next Text: ' . $title . '" alt="Next Text: ' . $title . '" /></a>';
			}
			else
				$next = '<img src="icn/navigation-000-button-light.png" title="No Next Text" alt="No Next Text" />';
			return $add . $prev . ' ' . $next;
		}
	}
	return $add . '<img src="icn/navigation-180-button-light.png" title="No Previous Text" alt="No Previous Text" /> <img src="icn/navigation-000-button-light.png" title="No Next Text" alt="No Next Text" />';
}

// -------------------------------------------------------------

function get_tags($refresh = 0) {
	global $tbpref;
	if (isset($_SESSION['TAGS'])) {
		if (is_array($_SESSION['TAGS'])) {
			if (isset($_SESSION['TBPREF_TAGS'])) {
				if($_SESSION['TBPREF_TAGS'] == $tbpref . url_base()) {
					if ($refresh == 0) return $_SESSION['TAGS'];
				}
			}
		}
	}
	$tags = array();
	$sql = 'select TgText from ' . $tbpref . 'tags order by TgText';
	$res = do_mysql_query($sql);		
	while ($record = mysqli_fetch_assoc($res)) {
		$tags[] = $record["TgText"];
	}
	mysqli_free_result($res);
	$_SESSION['TAGS'] = $tags;
	$_SESSION['TBPREF_TAGS'] = $tbpref . url_base();
	return $_SESSION['TAGS'];
}

// -------------------------------------------------------------

function get_texttags($refresh = 0) {
	global $tbpref;
	if (isset($_SESSION['TEXTTAGS'])) {
		if (is_array($_SESSION['TEXTTAGS'])) {
			if (isset($_SESSION['TBPREF_TEXTTAGS'])) {
				if($_SESSION['TBPREF_TEXTTAGS'] == $tbpref . url_base()) {
					if ($refresh == 0) return $_SESSION['TEXTTAGS'];
				}
			}
		}
	}
	$tags = array();
	$sql = 'select T2Text from ' . $tbpref . 'tags2 order by T2Text';
	$res = do_mysql_query($sql);		
	while ($record = mysqli_fetch_assoc($res)) {
		$tags[] = $record["T2Text"];
	}
	mysqli_free_result($res);
	$_SESSION['TEXTTAGS'] = $tags;
	$_SESSION['TBPREF_TEXTTAGS'] = $tbpref . url_base();
	return $_SESSION['TEXTTAGS'];
}

// -------------------------------------------------------------

function getTextTitle ($textid) {
	global $tbpref;
	$text = get_first_value("select TxTitle as value from " . $tbpref . "texts where TxID=" . $textid);
	if (! isset($text)) $text = "?";
	return $text;
}

// -------------------------------------------------------------

function get_tag_selectoptions($v,$l) {
	global $tbpref;
	if ( ! isset($v) ) $v = '';
	$r = "<option value=\"\"" . get_selected($v,'');
	$r .= ">[Filter off]</option>";
	if ($l == '')
		$sql = "select TgID, TgText from " . $tbpref . "words, " . $tbpref . "tags, " . $tbpref . "wordtags where TgID = WtTgID and WtWoID = WoID group by TgID order by UPPER(TgText)";
	else
		$sql = "select TgID, TgText from " . $tbpref . "words, " . $tbpref . "tags, " . $tbpref . "wordtags where TgID = WtTgID and WtWoID = WoID and WoLgID = " . $l . " group by TgID order by UPPER(TgText)";
	$res = do_mysql_query($sql);		
	$cnt = 0;
	while ($record = mysqli_fetch_assoc($res)) {
		$d = $record["TgText"];
		$cnt++;
		$r .= "<option value=\"" . $record["TgID"] . "\"" . get_selected($v,$record["TgID"]) . ">" . tohtml($d) . "</option>";
	}
	mysqli_free_result($res);
	if ($cnt > 0) {
		$r .= "<option disabled=\"disabled\">--------</option>";
		$r .= "<option value=\"-1\"" . get_selected($v,-1) . ">UNTAGGED</option>";
	}
	return $r;
}

// -------------------------------------------------------------

function get_texttag_selectoptions($v,$l) {
	global $tbpref;
	if ( ! isset($v) ) $v = '';
	$r = "<option value=\"\"" . get_selected($v,'');
	$r .= ">[Filter off]</option>";
	if ($l == '')
		$sql = "select T2ID, T2Text from " . $tbpref . "texts, " . $tbpref . "tags2, " . $tbpref . "texttags where T2ID = TtT2ID and TtTxID = TxID group by T2ID order by UPPER(T2Text)";
	else
		$sql = "select T2ID, T2Text from " . $tbpref . "texts, " . $tbpref . "tags2, " . $tbpref . "texttags where T2ID = TtT2ID and TtTxID = TxID and TxLgID = " . $l . " group by T2ID order by UPPER(T2Text)";
	$res = do_mysql_query($sql);		
	$cnt = 0;
	while ($record = mysqli_fetch_assoc($res)) {
		$d = $record["T2Text"];
		$cnt++;
		$r .= "<option value=\"" . $record["T2ID"] . "\"" . get_selected($v,$record["T2ID"]) . ">" . tohtml($d) . "</option>";
	}
	mysqli_free_result($res);
	if ($cnt > 0) {
		$r .= "<option disabled=\"disabled\">--------</option>";
		$r .= "<option value=\"-1\"" . get_selected($v,-1) . ">UNTAGGED</option>";
	}
	return $r;
}

// -------------------------------------------------------------

function get_txtag_selectoptions($l,$v){
	global $tbpref;
	$text_tags=array();
	if ( ! isset($v) ) $v = '';
	$u ='';
	$r = "<option value=\"&amp;texttag\"" . get_selected($v,'');
	$r .= ">[Filter off]</option>";
	$sql = 'SELECT IFNULL(T2Text, 1) AS TagName, TtT2ID AS TagID, GROUP_CONCAT(TxID ORDER BY TxID) AS TextID FROM ' . $tbpref . 'texts';
	$sql .= ' LEFT JOIN ' . $tbpref . 'texttags ON TxID = TtTxID';
	$sql .= ' LEFT JOIN ' . $tbpref . 'tags2 ON TtT2ID = T2ID';
	if($l)$sql .= ' WHERE TxLgID='.$l;
	$sql .= ' GROUP BY UPPER(TagName)';
	$res = do_mysql_query($sql);
	while ($record = mysqli_fetch_assoc($res)) {
		if($record['TagName']==1){
			$u ="<option disabled=\"disabled\">--------</option><option value=\"" . $record['TextID'] . "&amp;texttag=-1\"" . get_selected($v,"-1") . ">UNTAGGED</option>";
		}
		else {
			$r .= "<option value=\"" .$record['TextID']."&amp;texttag=". $record['TagID'] . "\"" . get_selected($v,$record['TagID']) . ">" . $record['TagName'] . "</option>";
		}
	}
	mysqli_free_result($res);
	return $r.$u;
}

// -------------------------------------------------------------

function get_archivedtexttag_selectoptions($v,$l) {
	global $tbpref;
	if ( ! isset($v) ) $v = '';
	$r = "<option value=\"\"" . get_selected($v,'');
	$r .= ">[Filter off]</option>";
	if ($l == '')
		$sql = "select T2ID, T2Text from " . $tbpref . "archivedtexts, " . $tbpref . "tags2, " . $tbpref . "archtexttags where T2ID = AgT2ID and AgAtID = AtID group by T2ID order by UPPER(T2Text)";
	else
		$sql = "select T2ID, T2Text from " . $tbpref . "archivedtexts, " . $tbpref . "tags2, " . $tbpref . "archtexttags where T2ID = AgT2ID and AgAtID = AtID and AtLgID = " . $l . " group by T2ID order by UPPER(T2Text)";
	$res = do_mysql_query($sql);		
	$cnt = 0;
	while ($record = mysqli_fetch_assoc($res)) {
		$d = $record["T2Text"];
		$cnt++;
		$r .= "<option value=\"" . $record["T2ID"] . "\"" . get_selected($v,$record["T2ID"]) . ">" . tohtml($d) . "</option>";
	}
	mysqli_free_result($res);
	if ($cnt > 0) {
		$r .= "<option disabled=\"disabled\">--------</option>";
		$r .= "<option value=\"-1\"" . get_selected($v,-1) . ">UNTAGGED</option>";
	}
	return $r;
}

// -------------------------------------------------------------

function saveWordTags($wid) {
	global $tbpref;
	runsql("DELETE from " . $tbpref . "wordtags WHERE WtWoID =" . $wid,'');
	if (isset($_REQUEST['TermTags'])) {
		if (is_array($_REQUEST['TermTags'])) {
			if (isset($_REQUEST['TermTags']['TagList'])) {
				if (is_array($_REQUEST['TermTags']['TagList'])) {
					$cnt = count($_REQUEST['TermTags']['TagList']);
					if ($cnt > 0 ) {
						for ($i=0; $i<$cnt; $i++) {
							$tag = $_REQUEST['TermTags']['TagList'][$i];
							if(! in_array($tag, $_SESSION['TAGS'])) {
								runsql('insert into ' . $tbpref . 'tags (TgText) values(' . 
								convert_string_to_sqlsyntax($tag) . ')', "");
							}
							runsql('insert into ' . $tbpref . 'wordtags (WtWoID, WtTgID) select ' . $wid . ', TgID from ' . $tbpref . 'tags where TgText = ' . convert_string_to_sqlsyntax($tag), "");
						}
						get_tags(1);  // refresh tags cache
					}
				}
			}
		}
	}
}

// -------------------------------------------------------------

function saveTextTags($tid) {
	global $tbpref;
	runsql("DELETE from " . $tbpref . "texttags WHERE TtTxID =" . $tid,'');
	if (isset($_REQUEST['TextTags'])) {
		if (is_array($_REQUEST['TextTags'])) {
			if (isset($_REQUEST['TextTags']['TagList'])) {
				if (is_array($_REQUEST['TextTags']['TagList'])) {
					$cnt = count($_REQUEST['TextTags']['TagList']);
					if ($cnt > 0 ) {
						for ($i=0; $i<$cnt; $i++) {
							$tag = $_REQUEST['TextTags']['TagList'][$i];
							if(! in_array($tag, $_SESSION['TEXTTAGS'])) {
								runsql('insert into ' . $tbpref . 'tags2 (T2Text) values(' . 
								convert_string_to_sqlsyntax($tag) . ')', "");
							}
							runsql('insert into ' . $tbpref . 'texttags (TtTxID, TtT2ID) select ' . $tid . ', T2ID from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($tag), "");
						}
						get_texttags(1);  // refresh tags cache
					}
				}
			}
		}
	}
}

// -------------------------------------------------------------

function saveArchivedTextTags($tid) {
	global $tbpref;
	runsql("DELETE from " . $tbpref . "archtexttags WHERE AgAtID =" . $tid,'');
	if (isset($_REQUEST['TextTags'])) {
		if (is_array($_REQUEST['TextTags'])) {
			if (isset($_REQUEST['TextTags']['TagList'])) {
				if (is_array($_REQUEST['TextTags']['TagList'])) {
					$cnt = count($_REQUEST['TextTags']['TagList']);
					if ($cnt > 0 ) {
						for ($i=0; $i<$cnt; $i++) {
							$tag = $_REQUEST['TextTags']['TagList'][$i];
							if(! in_array($tag, $_SESSION['TEXTTAGS'])) {
								runsql('insert into ' . $tbpref . 'tags2 (T2Text) values(' . 
								convert_string_to_sqlsyntax($tag) . ')', "");
							}
							runsql('insert into ' . $tbpref . 'archtexttags (AgAtID, AgT2ID) select ' . $tid . ', T2ID from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($tag), "");
						}
						get_texttags(1);  // refresh tags cache
					}
				}
			}
		}
	}
}

// -------------------------------------------------------------

function getWordTags($wid) {
	global $tbpref;
	$r = '<ul id="termtags">';
	if ($wid > 0) {
		$sql = 'select TgText from ' . $tbpref . 'wordtags, ' . $tbpref . 'tags where TgID = WtTgID and WtWoID = ' . $wid . ' order by TgText';
		$res = do_mysql_query($sql);		
		while ($record = mysqli_fetch_assoc($res)) {
			$r .= '<li>' . tohtml($record["TgText"]) . '</li>';
		}
		mysqli_free_result($res);
	}
	$r .= '</ul>';
	return $r;
}

// -------------------------------------------------------------

function getTextTags($tid) {
	global $tbpref;
	$r = '<ul id="texttags">';
	if ($tid > 0) {
		$sql = 'select T2Text from ' . $tbpref . 'texttags, ' . $tbpref . 'tags2 where T2ID = TtT2ID and TtTxID = ' . $tid . ' order by T2Text';
		$res = do_mysql_query($sql);		
		while ($record = mysqli_fetch_assoc($res)) {
			$r .= '<li>' . tohtml($record["T2Text"]) . '</li>';
		}
		mysqli_free_result($res);
	}
	$r .= '</ul>';
	return $r;
}

// -------------------------------------------------------------

function getArchivedTextTags($tid) {
	global $tbpref;
	$r = '<ul id="texttags">';
	if ($tid > 0) {
		$sql = 'select T2Text from ' . $tbpref . 'archtexttags, ' . $tbpref . 'tags2 where T2ID = AgT2ID and AgAtID = ' . $tid . ' order by T2Text';
		$res = do_mysql_query($sql);
		while ($record = mysqli_fetch_assoc($res)) {
			$r .= '<li>' . tohtml($record["T2Text"]) . '</li>';
		}
		mysqli_free_result($res);
	}
	$r .= '</ul>';
	return $r;
}

// -------------------------------------------------------------

function addtaglist ($item, $list) {
	global $tbpref;
	$tagid = get_first_value('select TgID as value from ' . $tbpref . 'tags where TgText = ' . convert_string_to_sqlsyntax($item));
	if (! isset($tagid)) {
		runsql('insert into ' . $tbpref . 'tags (TgText) values(' . convert_string_to_sqlsyntax($item) . ')', "");
		$tagid = get_first_value('select TgID as value from ' . $tbpref . 'tags where TgText = ' . convert_string_to_sqlsyntax($item));
	}
	$sql = 'select WoID from ' . $tbpref . 'words LEFT JOIN ' . $tbpref . 'wordtags ON WoID = WtWoID AND WtTgID = ' . $tagid . ' WHERE WtTgID IS NULL AND WoID in ' . $list;
	$res = do_mysql_query($sql);
	$cnt = 0;
	while ($record = mysqli_fetch_assoc($res)) {
		$cnt += runsql('insert ignore into ' . $tbpref . 'wordtags (WtWoID, WtTgID) values(' . $record['WoID'] . ', ' . $tagid . ')', "");
	}
	mysqli_free_result($res);
	get_tags(1);
	return "Tag added in $cnt Terms";
}

// -------------------------------------------------------------

function addarchtexttaglist ($item, $list) {
	global $tbpref;
	$tagid = get_first_value('select T2ID as value from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($item));
	if (! isset($tagid)) {
		runsql('insert into ' . $tbpref . 'tags2 (T2Text) values(' . convert_string_to_sqlsyntax($item) . ')', "");
		$tagid = get_first_value('select T2ID as value from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($item));
	}
	$sql = 'select AtID from ' . $tbpref . 'archivedtexts LEFT JOIN ' . $tbpref . 'archtexttags ON AtID = AgAtID AND AgT2ID = ' . $tagid . ' WHERE AgT2ID IS NULL AND AtID in ' . $list;
	$res = do_mysql_query($sql);
	$cnt = 0;
	while ($record = mysqli_fetch_assoc($res)) {
		$cnt += runsql('insert ignore into ' . $tbpref . 'archtexttags (AgAtID, AgT2ID) values(' . $record['AtID'] . ', ' . $tagid . ')', "");
	}
	mysqli_free_result($res);
	get_texttags(1);
	return "Tag added in $cnt Texts";
}

// -------------------------------------------------------------

function addtexttaglist ($item, $list) {
	global $tbpref;
	$tagid = get_first_value('select T2ID as value from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($item));
	if (! isset($tagid)) {
		runsql('insert into ' . $tbpref . 'tags2 (T2Text) values(' . convert_string_to_sqlsyntax($item) . ')', "");
		$tagid = get_first_value('select T2ID as value from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($item));
	}
	$sql = 'select TxID from ' . $tbpref . 'texts  LEFT JOIN ' . $tbpref . 'texttags ON TxID = TtTxID AND TtT2ID = ' . $tagid . ' WHERE TtT2ID IS NULL AND TxID in ' . $list;
	$res = do_mysql_query($sql);
	$cnt = 0;
	while ($record = mysqli_fetch_assoc($res)) {
		$cnt += runsql('insert ignore into ' . $tbpref . 'texttags (TtTxID, TtT2ID) values(' . $record['TxID'] . ', ' . $tagid . ')', "");
	}
	mysqli_free_result($res);
	get_texttags(1);
	return "Tag added in $cnt Texts";
}

// -------------------------------------------------------------

function removetaglist ($item, $list) {
	global $tbpref;
	$tagid = get_first_value('select TgID as value from ' . $tbpref . 'tags where TgText = ' . convert_string_to_sqlsyntax($item));
	if (! isset($tagid)) return "Tag " . $item . " not found";
	$sql = 'select WoID from ' . $tbpref . 'words where WoID in ' . $list;
	$res = do_mysql_query($sql);
	$cnt = 0;
	while ($record = mysqli_fetch_assoc($res)) {
		$cnt++;
		runsql('delete from ' . $tbpref . 'wordtags where WtWoID = ' . $record['WoID'] . ' and WtTgID = ' . $tagid, "");
	}
	mysqli_free_result($res);
	return "Tag removed in $cnt Terms";
}

// -------------------------------------------------------------

function removearchtexttaglist ($item, $list) {
	global $tbpref;
	$tagid = get_first_value('select T2ID as value from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($item));
	if (! isset($tagid)) return "Tag " . $item . " not found";
	$sql = 'select AtID from ' . $tbpref . 'archivedtexts where AtID in ' . $list;
	$res = do_mysql_query($sql);
	$cnt = 0;
	while ($record = mysqli_fetch_assoc($res)) {
		$cnt++;
		runsql('delete from ' . $tbpref . 'archtexttags where AgAtID = ' . $record['AtID'] . ' and AgT2ID = ' . $tagid, "");
	}
	mysqli_free_result($res);
	return "Tag removed in $cnt Texts";
}

// -------------------------------------------------------------

function removetexttaglist ($item, $list) {
	global $tbpref;
	$tagid = get_first_value('select T2ID as value from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($item));
	if (! isset($tagid)) return "Tag " . $item . " not found";
	$sql = 'select TxID from ' . $tbpref . 'texts where TxID in ' . $list;
	$res = do_mysql_query($sql);
	$cnt = 0;
	while ($record = mysqli_fetch_assoc($res)) {
		$cnt++;
		runsql('delete from ' . $tbpref . 'texttags where TtTxID = ' . $record['TxID'] . ' and TtT2ID = ' . $tagid, "");
	}
	mysqli_free_result($res);
	return "Tag removed in $cnt Texts";
}

// -------------------------------------------------------------

function framesetheader($title) {
	@header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
	@header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
	@header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
	@header( 'Pragma: no-cache' );
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" type="text/css" href="<?php print_file_path('css/styles.css');?>" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>

<!-- ***********************************************************
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
************************************************************ -->

	<title>LWT :: <?php echo tohtml($title); ?></title>
</head>
<?php
}

// -------------------------------------------------------------

function pagestart_nobody($titletext, $addcss='') {
	global $debug;
	global $tbpref;
	@header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
	@header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
	@header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
	@header( 'Pragma: no-cache' );
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php
$currentlang = getSetting('currentlanguage');
$langname = getLanguage($currentlang);
if($langname == 'Chinese (Simplified)')
    {
		echo 'lang="zh"';
    }
else if($langname == 'Chinese (Traditional)')
    {
		echo 'lang="zh"';
    }
else if($langname == 'Chinese')
    {
		echo 'lang="zh"';
    }
else if($langname == 'Japanese')
    {
        echo 'lang="ja"';
    }
 ?>>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	
<!-- ***********************************************************
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
************************************************************ -->

	<meta name="viewport" content="width=900" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
	<link rel="apple-touch-icon" href="<?php print_file_path('img/apple-touch-icon-57x57.png');?>" />
	<link rel="apple-touch-icon" sizes="72x72" href="<?php print_file_path('img/apple-touch-icon-72x72.png');?>" />
	<link rel="apple-touch-icon" sizes="114x114" href="<?php print_file_path('img/apple-touch-icon-114x114.png');?>" />
	<link rel="apple-touch-startup-image" href="img/apple-touch-startup.png" />
	<meta name="apple-mobile-web-app-capable" content="yes" />
	
	<link rel="stylesheet" type="text/css" href="<?php print_file_path('css/jquery-ui.css');?>" />
	<link rel="stylesheet" type="text/css" href="<?php print_file_path('css/jquery.tagit.css');?>" />
	<link rel="stylesheet" type="text/css" href="<?php print_file_path('css/styles.css');?>" />
	<style type="text/css">
	<?php echo $addcss . "\n"; ?>
	</style>
	
	<script type="text/javascript" src="js/jquery.js" charset="utf-8"></script>
	<script type="text/javascript" src="js/jquery.scrollTo.min.js" charset="utf-8"></script>
	<script type="text/javascript" src="js/jquery-ui.min.js"  charset="utf-8"></script>
	<script type="text/javascript" src="js/tag-it.js" charset="utf-8"></script>
	<script type="text/javascript" src="js/jquery.jeditable.mini.js" charset="utf-8"></script>
	<script type="text/javascript" src="js/sorttable/sorttable.js" charset="utf-8"></script>
	<script type="text/javascript" src="js/countuptimer.js" charset="utf-8"></script>
	<script type="text/javascript" src="js/overlib/overlib_mini.js" charset="utf-8"></script>
	<!-- URLBASE : "<?php echo tohtml(url_base()); ?>" -->
	<!-- TBPREF  : "<?php echo tohtml($tbpref); ?>" -->
	<script type="text/javascript">
	//<![CDATA[
	<?php echo "var STATUSES = " . json_encode(get_statuses()) . ";\n"; ?>
	<?php echo "var TAGS = " . json_encode(get_tags()) . ";\n"; ?>
	<?php echo "var TEXTTAGS = " . json_encode(get_texttags()) . ";\n"; ?>
	//]]>
	</script>
	<script type="text/javascript" src="js/pgm.js" charset="utf-8"></script>
	<script type="text/javascript" src="js/jq_pgm.js" charset="utf-8"></script>
	
	<title>LWT :: <?php echo $titletext; ?></title>
</head>
<body>
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
<?php
	flush();
	if ($debug) showRequest();
} 

// -------------------------------------------------------------

function pagestart($titletext,$close) {
	global $debug;
	pagestart_nobody($titletext);
	echo '<h4>';
	if ($close) echo '<a href="index.php" target="_top">';
	echo_lwt_logo();
	echo "<span>LWT</span>";
	if ($close) {
		echo '</a><span>&nbsp; | &nbsp;';
		quickMenu();
		echo '</span>';
	}
	echo '</h4><h3>' . $titletext . ($debug ? ' <span class="red">DEBUG</span>' : '') . '</h3>';
	echo "<p>&nbsp;</p>";
} 

// -------------------------------------------------------------

function url_base() {
	$url = parse_url("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
	$r = $url["scheme"] . "://" . $url["host"];
	if(isset($url["port"])) $r .= ":" . $url["port"];
	if(isset($url["path"])) {
		$b = basename($url["path"]);
		if (substr($b,-4) == ".php" || substr($b,-4) == ".htm" || substr($b,-5) == ".html") 
			$r .= dirname($url["path"]);
		else
			$r .= $url["path"];
	}
	if(substr($r,-1) !== "/") $r .= "/";
	return $r;
}

// -------------------------------------------------------------

function pageend() {
	global $debug, $dspltime;
	if ($debug) showRequest();
	if ($dspltime) echo "\n<p class=\"smallgray2\">" . 
		round(get_execution_time(),5) . " secs</p>\n";
?></body></html><?php
} 

// -------------------------------------------------------------

function echo_lwt_logo() {
	global $tbpref;
	$pref = substr($tbpref,0,-1);
	if($pref == '') $pref = 'Default Table Set';
	echo '<img class="lwtlogo" src="';print_file_path('img/lwt_icon.png');echo '"  title="LWT - Current Table Set: ' . tohtml($pref) . '" alt="LWT - Current Table Set: ' . tohtml($pref) . '" />';
}

// -------------------------------------------------------------

function get_execution_time()
{
    static $microtime_start = null;
    if($microtime_start === null)
    {
        $microtime_start = microtime(true);
        return 0.0;
    }
    return microtime(true) - $microtime_start;
}

// -------------------------------------------------------------

function getprefixes() {
	$prefix = array();
	$res = do_mysql_query(str_replace('_',"\\_","SHOW TABLES LIKE " . convert_string_to_sqlsyntax_nonull('%_settings')));
	while ($row = mysqli_fetch_row($res))
		$prefix[] = substr($row[0], 0, -9);
	mysqli_free_result($res);
	return $prefix;
}

// -------------------------------------------------------------

function selectmediapath($f) {
	$exists = file_exists('media');
	if ($exists) {
		if (is_dir ('media')) $msg = '';
		else $msg = '<br />[Error: ".../' . basename(getcwd()) . '/media" exists, but it is not a directory.]';
	} else {
		$msg = '<br />[Directory ".../' . basename(getcwd()) . '/media" does not yet exist.]';
	}
	$r = '<br /> or choose a file in ".../' . basename(getcwd()) . '/media" (only mp3, ogg, wav files shown): ' . $msg;
	if ($msg == '') {
		$r .= '<br /><select name="Dir" onchange="{val=this.form.Dir.options[this.form.Dir.selectedIndex].value; if (val != \'\') this.form.' . $f . '.value = val; this.form.Dir.value=\'\';}">';
		$r .= '<option value="">[Choose...]</option>';
		$r .= selectmediapathoptions('media');
		$r .= '</select> ';
	}
	$r .= ' &nbsp; &nbsp; <span class="click" onclick="do_ajax_update_media_select();"><img src="icn/arrow-circle-135.png" title="Refresh Media Selection" alt="Refresh Media Selection" /> Refresh</span>';
	return $r;
}

// -------------------------------------------------------------

function selectmediapathoptions($dir) {
	$is_windows = ("WIN" == strtoupper(substr(PHP_OS, 0, 3)));
	$mediadir = scandir($dir);
	$r = '<option disabled="disabled">-- Directory: ' . tohtml($dir) . ' --</option>';
	foreach ($mediadir as $entry) {
		if ($is_windows) $entry = mb_convert_encoding ($entry,'UTF-8','ISO-8859-1');
		if (substr($entry,0,1) != '.') {
			if (! is_dir($dir . '/' . $entry)) {
				$ex = substr($entry,-4);
				if ( (strcasecmp($ex, '.mp3') == 0) ||
					(strcasecmp($ex, '.ogg') == 0) ||
					(strcasecmp($ex, '.wav') == 0))
					$r .= '<option value="' . tohtml($dir . '/' . $entry) . '">' . tohtml($dir . '/' . $entry) . '</option>';
			}
		}
	}
	foreach ($mediadir as $entry) {
		if (substr($entry,0,1) != '.') {
			if (is_dir($dir . '/' . $entry)) $r .= selectmediapathoptions($dir . '/' . $entry);
		}
	}
	return $r;
}

// -------------------------------------------------------------

function get_seconds_selectoptions($v) {
	if ( ! isset($v) ) $v = 5;
	$r = '';
	for ($i=1; $i <= 10; $i++) {
		$r .= "<option value=\"" . $i . "\"" . get_selected($v,$i);
		$r .= ">" . $i . " sec</option>";
	}
	return $r;
}

// -------------------------------------------------------------

function quickMenu() {
?><select id="quickmenu" onchange="{var qm = document.getElementById('quickmenu'); var val=qm.options[qm.selectedIndex].value; qm.selectedIndex=0; if (val != '') { if (val == 'INFO') {top.location.href='info.htm';}else if (val == 'rss_import'){top.location.href = 'do_feeds.php?check_autoupdate=1';} else {top.location.href = val + '.php';}}}">
<option value="" selected="selected">[Menu]</option>
<option value="index">Home</option>
<option value="edit_texts">Texts</option>
<option value="edit_archivedtexts">Text Archive</option>
<option value="edit_texttags">Text Tags</option>
<option value="edit_languages">Languages</option>
<option value="edit_words">Terms</option>
<option value="edit_tags">Term Tags</option>
<option value="statistics">Statistics</option>
<option value="check_text">Text Check</option>
<option value="long_text_import">Long Text Import</option>
<option value="rss_import">Newsfeed Import</option>
<option value="upload_words">Term Import</option>
<option value="backup_restore">Backup/Restore</option>
<option value="settings">Settings</option>
<option value="INFO">Help</option>
</select><?php
}

// -------------------------------------------------------------

function error_message_with_hide($msg,$noback) {
	if (trim($msg) == '') return '';
	if (substr($msg,0,5) == "Error" )
		return '<p class="red">*** ' . tohtml($msg) . ' ***' . 
			($noback ? 
			'' : 
			'<br /><input type="button" value="&lt;&lt; Go back and correct &lt;&lt;" onclick="history.back();" />' ) . 
			'</p>';
	else
		return '<p id="hide3" class="msgblue">+++ ' . tohtml($msg) . ' +++</p>';
}

// -------------------------------------------------------------

function errorbutton($msg) {
	if (substr($msg,0,5) == "Error" )
		return '<input type="button" value="&lt;&lt; Back" onclick="history.back();" />';
	else
		return '';
} 

// -------------------------------------------------------------

function optimizedb() {
	global $tbpref;
	adjust_autoincr('archivedtexts','AtID');
	adjust_autoincr('languages','LgID');
	adjust_autoincr('sentences','SeID');
	adjust_autoincr('texts','TxID');
	adjust_autoincr('words','WoID');
	adjust_autoincr('tags','TgID');
	adjust_autoincr('tags2','T2ID');
	adjust_autoincr('newsfeeds','NfID');
	adjust_autoincr('feedlinks','FlID');
	$sql='SHOW TABLE STATUS WHERE Engine in ("MyISAM","Aria") AND ((Data_free / Data_length > 0.1 AND Data_free > 102400) OR Data_free > 1048576) AND Name';
	if(empty($tbpref))$sql.= " not like '\_%'";
	else $sql.= " like " . convert_string_to_sqlsyntax(rtrim($tbpref,'_')) . "'\_%'";
	$res = do_mysql_query($sql);
	while($row = mysqli_fetch_assoc($res)) {
		runsql('OPTIMIZE TABLE ' . $row['Name'],'');
	}
	mysqli_free_result($res);
}

// -------------------------------------------------------------

function limitlength($s, $l) {
	if (mb_strlen ($s, 'UTF-8') <= $l) return $s;
	return mb_substr($s, 0, $l, 'UTF-8');
}

// -------------------------------------------------------------

function adjust_autoincr($table,$key) {
	global $tbpref;
	$val = get_first_value('select max(' . $key .')+1 as value from ' . $tbpref .  $table);
	if (! isset($val)) $val = 1;
	$sql = 'alter table ' . $tbpref . $table . ' AUTO_INCREMENT = ' . $val;
	$res = do_mysql_query($sql);
}

// -------------------------------------------------------------

function prepare_textdata($s) {
	return str_replace("\r\n","\n", stripTheSlashesIfNeeded($s));
}

// -------------------------------------------------------------

function prepare_textdata_js($s) {
	$s = convert_string_to_sqlsyntax($s);
	if($s == "NULL") return "''";
	return str_replace("''", "\\'", $s);
}

// -------------------------------------------------------------

function tohtml($s) {
	if (isset($s)) return htmlspecialchars($s, ENT_COMPAT, "UTF-8");
	else return '';
}

// -------------------------------------------------------------

function makeCounterWithTotal ($max, $num) {
	if ($max == 1) return '';
	if ($max < 10) return $num . "/" . $max;
	return substr(
		str_repeat("0", strlen($max)) . $num,
		-strlen($max))  . 
		"/" . $max;
}

// -------------------------------------------------------------

function encodeURI($url) {
	$reserved = array(
		'%2D'=>'-','%5F'=>'_','%2E'=>'.','%21'=>'!', 
		'%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')'
	);
	$unescaped = array(
		'%3B'=>';','%2C'=>',','%2F'=>'/','%3F'=>'?','%3A'=>':',
		'%40'=>'@','%26'=>'&','%3D'=>'=','%2B'=>'+','%24'=>'$'
	);
	$score = array(
		'%23'=>'#'
	);
	return strtr(rawurlencode($url), array_merge($reserved,$unescaped,$score));
}
 
// -------------------------------------------------------------

function showRequest() {
	echo "<pre>** DEBUGGING **********************************\n";
if (count($_REQUEST)) { echo '$_REQUEST...'; print_r($_REQUEST); }
	if (count($_COOKIE)) { echo '$_COOKIE...'; print_r($_COOKIE); }
	if (count($_FILES)) { echo '$_FILES...'; print_r($_FILES); }
	if (count($_SESSION)) { echo '$_SESSION...'; print_r($_SESSION); }
	echo 'get_version_number()...'; echo get_version_number() . "\n";
	echo 'get_magic_quotes_gpc()...'; echo get_magic_quotes_gpc() . "\n";
	echo "********************************** DEBUGGING **</pre>";
}

// -------------------------------------------------------------

function convert_string_to_sqlsyntax($data) {
	$result = "NULL";
	$data = trim(prepare_textdata($data));
	if($data != "") $result = "'" . mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data) . "'";
	return $result;
}

// -------------------------------------------------------------

function convert_string_to_sqlsyntax_nonull($data) {
	$data = trim(prepare_textdata($data));
	return  "'" . mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $data) . "'";
}

// -------------------------------------------------------------

function convert_string_to_sqlsyntax_notrim_nonull($data) {
	return "'" . mysqli_real_escape_string($GLOBALS["___mysqli_ston"], prepare_textdata($data)) . "'";
}

// -------------------------------------------------------------

function remove_spaces($s,$remove) {
	if ($remove) 
		return preg_replace('/\s{1,}/u', '', $s);  // '' enthält &#x200B;
	else
		return $s;
}

// -------------------------------------------------------------

function getreq($s) {
	if ( isset($_REQUEST[$s]) ) {
		return trim($_REQUEST[$s]);
	} else
		return '';
}

// -------------------------------------------------------------

function getsess($s) {
	if ( isset($_SESSION[$s]) ) {
		return trim($_SESSION[$s]);
	} else
		return '';
}

// -------------------------------------------------------------

function get_sepas() {
	static $sepa;
	if (!$sepa) {
		$sepa = preg_quote(getSettingWithDefault('set-term-translation-delimiters'),'/');
	}
	return $sepa;
}

// -------------------------------------------------------------

function get_first_sepa() {
	static $sepa;
	if (!$sepa) {
		$sepa = mb_substr(getSettingWithDefault('set-term-translation-delimiters'),
		0,1,'UTF-8');
	}
	return $sepa;
}

// -------------------------------------------------------------

function getSettingZeroOrOne($key, $dft) {
	$r = getSetting($key);
	$r = ($r == '' ? $dft : ((((int) $r) !== 0) ? 1 : 0));
	return $r;
}

// -------------------------------------------------------------

function getSetting($key) {
	global $tbpref;
	$val = get_first_value('select StValue as value from ' . $tbpref . 'settings where StKey = ' . convert_string_to_sqlsyntax($key));
	if ( isset($val) ) {
		$val = trim($val);
		if ($key == 'currentlanguage' ) $val = validateLang($val);
		if ($key == 'currenttext' ) $val = validateText($val);
		return $val;
	}
	else return '';
}

// -------------------------------------------------------------

function getSettingWithDefault($key) {
	global $tbpref;
	$dft = get_setting_data();
	$val = get_first_value('select StValue as value from ' . $tbpref . 'settings where StKey = ' . convert_string_to_sqlsyntax($key));
	if ( isset($val) && $val != '' ) return trim($val);
	else {
		if (array_key_exists($key,$dft)) return $dft[$key]['dft'];
		else return '';
	}
}

// -------------------------------------------------------------

function get_audioplayer_selectoptions($v) {
	if ( ! isset($v) ) $v = "jplayer.blue.monday.modified";
	$r  = "<option value=\"jplayer.blue.monday.modified\"" . get_selected($v,"jplayer.blue.monday.modified");
	$r .= ">Blue Monday Small</option>";
	$r .= "<option value=\"jplayer.blue.monday\"" . get_selected($v,"jplayer.blue.monday");
	$r .= ">Blue Monday</option>";
	$r .= "<option value=\"jplayer-black-and-yellow\"" . get_selected($v,"jplayer-black-and-yellow");
	$r .= ">Black &amp; Yellow</option>";
	return $r;
}

// -------------------------------------------------------------

function get_mobile_display_mode_selectoptions($v) {
	if ( ! isset($v) ) $v = "0";
	$r  = "<option value=\"0\"" . get_selected($v,"0");
	$r .= ">Auto</option>";
	$r .= "<option value=\"1\"" . get_selected($v,"1");
	$r .= ">Force Non-Mobile</option>";
	$r .= "<option value=\"2\"" . get_selected($v,"2");
	$r .= ">Force Mobile</option>";
	return $r;
}

// -------------------------------------------------------------

function get_sentence_count_selectoptions($v) {
	if ( ! isset($v) ) $v = 1;
	$r  = "<option value=\"1\"" . get_selected($v,1);
	$r .= ">Just ONE</option>";
	$r .= "<option value=\"2\"" . get_selected($v,2);
	$r .= ">TWO (+previous)</option>";
	$r .= "<option value=\"3\"" . get_selected($v,3);
	$r .= ">THREE (+previous,+next)</option>";
	return $r;
}

// -------------------------------------------------------------

function get_words_to_do_buttons_selectoptions($v) {
	if ( ! isset($v) ) $v = "1";
	$r  = "<option value=\"0\"" . get_selected($v,"0");
	$r .= ">I Know All &amp; Ignore All</option>";
	$r .= "<option value=\"1\"" . get_selected($v,"1");
	$r .= ">I Know All</option>";
	$r .= "<option value=\"2\"" . get_selected($v,"2");
	$r .= ">Ignore All</option>";
	return $r;
}

// -------------------------------------------------------------

function get_regex_selectoptions($v) {
	if ( ! isset($v) ) $v = "";
	$r  = "<option value=\"\"" . get_selected($v,"");
	$r .= ">Default</option>";
	$r .= "<option value=\"r\"" . get_selected($v,"r");
	$r .= ">RegEx</option>";
	$r .= "<option value=\"COLLATE 'utf8_bin' r\"" . get_selected($v,"COLLATE 'utf8_bin' r");
	$r .= ">RegEx CaseSensitive</option>";
	return $r;
}

// -------------------------------------------------------------

function get_tooltip_selectoptions($v) {
	if ( ! isset($v) ) $v = 1;
	$r  = "<option value=\"1\"" . get_selected($v,1);
	$r .= ">Native</option>";
	$r .= "<option value=\"2\"" . get_selected($v,2);
	$r .= ">JqueryUI</option>";
	return $r;
}

// -------------------------------------------------------------

function get_themes_selectoptions($v){
	$themes=glob ('themes/*',GLOB_ONLYDIR);
	$r= '<option value="themes/Default/">Default</option>';
	foreach($themes as $theme){
		if($theme!='themes/Default'){
			$r.= '<option value="'.$theme.'/" '. get_selected($v,$theme.'/');
			$r .= ">". str_replace(array('themes/','_'),array('',' '),$theme) ."</option>";
		}
	}
	return $r;
}

// -------------------------------------------------------------

function saveSetting($k,$v) {
	global $tbpref;
	$dft = get_setting_data();
	if (! isset($v)) $v ='';
	$v = stripTheSlashesIfNeeded($v);
	runsql('delete from ' . $tbpref . 'settings where StKey = ' . convert_string_to_sqlsyntax($k), '');
	if ($v !== '') {
		if (array_key_exists($k,$dft)) {
			if ($dft[$k]['num']) {
				$v = (int) $v;
				if ( $v < $dft[$k]['min'] ) $v = $dft[$k]['dft'];
				if ( $v > $dft[$k]['max'] ) $v = $dft[$k]['dft'];
			}
		}
		$dum = runsql('insert into ' . $tbpref . 'settings (StKey, StValue) values(' .
			convert_string_to_sqlsyntax($k) . ', ' . 
			convert_string_to_sqlsyntax($v) . ')', '');
	}
}

// -------------------------------------------------------------

function processSessParam($reqkey,$sesskey,$default,$isnum) {
	$result = '';
	if(isset($_REQUEST[$reqkey])) {
		$reqdata = stripTheSlashesIfNeeded(trim($_REQUEST[$reqkey]));
		$_SESSION[$sesskey] = $reqdata;
		$result = $reqdata;
	}
	elseif(isset($_SESSION[$sesskey])) {
		$result = $_SESSION[$sesskey];
	}
	else {
		$result = $default;
	}
	if($isnum) $result = (int)$result;
	return $result;
}

// -------------------------------------------------------------

function processDBParam($reqkey,$dbkey,$default,$isnum) {
	$result = '';
	$dbdata = getSetting($dbkey);
	if(isset($_REQUEST[$reqkey])) {
		$reqdata = stripTheSlashesIfNeeded(trim($_REQUEST[$reqkey]));
		saveSetting($dbkey,$reqdata);
		$result = $reqdata;
	}
	elseif($dbdata != '') {
		$result = $dbdata;
	}
	else {
		$result = $default;
	}
	if($isnum) $result = (int)$result;
	return $result;
}

// -------------------------------------------------------------

function validateLang($currentlang) {
	global $tbpref;
	if ($currentlang != '') {
		if (
			get_first_value(
				'select count(LgID) as value from ' . $tbpref . 'languages where LgID=' . 
				((int)$currentlang) 
			) == 0
		)  $currentlang = ''; 
	}
	return $currentlang;
}

// -------------------------------------------------------------

function validateText($currenttext) {
	global $tbpref;
	if ($currenttext != '') {
		if (
			get_first_value(
				'select count(TxID) as value from ' . $tbpref . 'texts where TxID=' . 
				((int)$currenttext) 
			) == 0
		)  $currenttext = ''; 
	}
	return $currenttext;
}

// -------------------------------------------------------------

function validateTag($currenttag,$currentlang) {
	global $tbpref;
	if ($currenttag != '' && $currenttag != -1) {
		if ($currentlang == '')
			$sql = "select (" . $currenttag . " in (select TgID from " . $tbpref . "words, " . $tbpref . "tags, " . $tbpref . "wordtags where TgID = WtTgID and WtWoID = WoID group by TgID order by TgText)) as value";
		else
			$sql = "select (" . $currenttag . " in (select TgID from " . $tbpref . "words, " . $tbpref . "tags, " . $tbpref . "wordtags where TgID = WtTgID and WtWoID = WoID and WoLgID = " . $currentlang . " group by TgID order by TgText)) as value";
		$r = get_first_value($sql);
		if ( $r == 0 ) $currenttag = ''; 
	}
	return $currenttag;
}

// -------------------------------------------------------------

function validateArchTextTag($currenttag,$currentlang) {
	global $tbpref;
	if ($currenttag != '' && $currenttag != -1) {
		if ($currentlang == '')
			$sql = "select (" . $currenttag . " in (select T2ID from " . $tbpref . "archivedtexts, " . $tbpref . "tags2, " . $tbpref . "archtexttags where T2ID = AgT2ID and AgAtID = AtID group by T2ID order by T2Text)) as value";
		else
			$sql = "select (" . $currenttag . " in (select T2ID from " . $tbpref . "archivedtexts, " . $tbpref . "tags2, " . $tbpref . "archtexttags where T2ID = AgT2ID and AgAtID = AtID and AtLgID = " . $currentlang . " group by T2ID order by T2Text)) as value";
		$r = get_first_value($sql);
		if ( $r == 0 ) $currenttag = ''; 
	}
	return $currenttag;
}

// -------------------------------------------------------------

function validateTextTag($currenttag,$currentlang) {
	global $tbpref;
	if ($currenttag != '' && $currenttag != -1) {
		if ($currentlang == '')
			$sql = "select (" . $currenttag . " in (select T2ID from " . $tbpref . "texts, " . $tbpref . "tags2, " . $tbpref . "texttags where T2ID = TtT2ID and TtTxID = TxID group by T2ID order by T2Text)) as value";
		else
			$sql = "select (" . $currenttag . " in (select T2ID from " . $tbpref . "texts, " . $tbpref . "tags2, " . $tbpref . "texttags where T2ID = TtT2ID and TtTxID = TxID and TxLgID = " . $currentlang . " group by T2ID order by T2Text)) as value";
		$r = get_first_value($sql);
		if ( $r == 0 ) $currenttag = ''; 
	}
	return $currenttag;
}

// -------------------------------------------------------------

function getWordTagList($wid, $before=' ', $brack=1, $tohtml=1) {
	global $tbpref;
	$r = get_first_value("select ifnull(" . ($brack ? "concat('['," : "") . "group_concat(distinct TgText order by TgText separator ', ')" . ($brack ? ",']')" : "") . ",'') as value from ((" . $tbpref . "words left join " . $tbpref . "wordtags on WoID = WtWoID) left join " . $tbpref . "tags on TgID = WtTgID) where WoID = " . $wid);
	if ($r != '') $r = $before . $r;
	if ($tohtml) $r = tohtml($r);
	return $r;
}

// -------------------------------------------------------------

function get_last_key() {
	return get_first_value('SELECT LAST_INSERT_ID() as value');		
}

// -------------------------------------------------------------

function get_checked($value) {
	if (! isset($value)) return '';
	if ((int)$value != 0) return ' checked="checked" ';
	return '';
}

// -------------------------------------------------------------

function get_selected($value,$selval) {
	if (! isset($value)) return '';
	if ($value == $selval) return ' selected="selected" ';
	return '';
}

// -------------------------------------------------------------

function make_status_controls_test_table($score, $status, $wordid) {
	if ( $score < 0 ) 
		$scoret = '<span class="red2">' . get_status_abbr($status) . '</span>';
	else
		$scoret = get_status_abbr($status);
		
	if ( $status <= 5 || $status == 98 ) 
		$plus = '<img src="icn/plus.png" class="click" title="+" alt="+" onclick="changeTableTestStatus(' . $wordid .',true);" />';
	else
		$plus = '<img src="'.get_file_path('icn/placeholder.png').'" title="" alt="" />';
	if ( $status >= 1 ) 
		$minus = '<img src="icn/minus.png" class="click" title="-" alt="-" onclick="changeTableTestStatus(' . $wordid .',false);" />';
	else
		$minus = '<img src="'.get_file_path('icn/placeholder.png').'" title="" alt="" />';
	return ($status == 98 ? '' : $minus . ' ') . $scoret . ($status == 99 ? '' : ' ' . $plus);
}

// -------------------------------------------------------------

function get_languages_selectoptions($v,$dt) {
	global $tbpref;
	$sql = "select LgID, LgName from " . $tbpref . "languages where LgName<>'' order by LgName";
	$res = do_mysql_query($sql);
	if ( ! isset($v) || trim($v) == '' ) {
		$r = "<option value=\"\" selected=\"selected\">" . $dt . "</option>";
	} else {
		$r = "<option value=\"\">" . $dt . "</option>";
	}
	while ($record = mysqli_fetch_assoc($res)) {
		$d = $record["LgName"];
		if ( strlen($d) > 30 ) $d = substr($d,0,30) . "...";
		$r .= "<option value=\"" . $record["LgID"] . "\" " . get_selected($v,$record["LgID"]);
		$r .= ">" . tohtml($d) . "</option>";
	}
	mysqli_free_result($res);
	return $r;
}

// -------------------------------------------------------------

function get_languagessize_selectoptions($v) {
	if ( ! isset($v) ) $v = 100;
	$r = "<option value=\"100\"" . get_selected($v,100);
	$r .= ">100 %</option>";
	$r .= "<option value=\"150\"" . get_selected($v,150);
	$r .= ">150 %</option>";
	$r .= "<option value=\"200\"" . get_selected($v,200);
	$r .= ">200 %</option>";
	$r .= "<option value=\"250\"" . get_selected($v,250);
	$r .= ">250 %</option>";
	return $r;
}

// -------------------------------------------------------------

function get_wordstatus_radiooptions($v) {
	if ( ! isset($v) ) $v = 1;
	$r = "";
	$statuses = get_statuses();
	foreach ($statuses as $n => $status) {
		$r .= '<span class="status' . $n . '" title="' . tohtml($status["name"]) . '">';
		$r .= '&nbsp;<input type="radio" name="WoStatus" value="' . $n . '"';
		if ($v == $n) $r .= ' checked="checked"';
		$r .= ' />' . tohtml($status["abbr"]) . "&nbsp;</span> ";
	}
	return $r;
}

// -------------------------------------------------------------

function get_wordstatus_selectoptions($v, $all, $not9899, $off=true) {
	if ( ! isset($v) ) {
		if ( $all ) $v = "";
		else $v = 1;
	}
	$r = "";
	if ($all && $off) {
		$r .= "<option value=\"\"" . get_selected($v,'');
		$r .= ">[Filter off]</option>";
	}
	$statuses = get_statuses();
	foreach ($statuses as $n => $status) {
		if ($not9899 && ($n == 98 || $n == 99)) continue;
		$r .= "<option value =\"" . $n . "\"" . get_selected($v,$n!=0?$n:'0');
		$r .= ">" . tohtml($status['name']) . " [" . 
		tohtml($status['abbr']) . "]</option>";
	}
	if ($all) {
		$r .= '<option disabled="disabled">--------</option>';
		$status_1_name = tohtml($statuses[1]["name"]);
		$status_1_abbr = tohtml($statuses[1]["abbr"]);
		$r .= "<option value=\"12\"" . get_selected($v,12);
		$r .= ">" . $status_1_name . " [" . $status_1_abbr . ".." . 
		tohtml($statuses[2]["abbr"]) . "]</option>";
		$r .= "<option value=\"13\"" . get_selected($v,13);
		$r .= ">" . $status_1_name . " [" . $status_1_abbr . ".." . 
		tohtml($statuses[3]["abbr"]) . "]</option>";
		$r .= "<option value=\"14\"" . get_selected($v,14);
		$r .= ">" . $status_1_name . " [" . $status_1_abbr . ".." . 
		tohtml($statuses[4]["abbr"]) . "]</option>";
		$r .= "<option value=\"15\"" . get_selected($v,15);
		$r .= ">Learning/-ed [" . $status_1_abbr . ".." . 
		tohtml($statuses[5]["abbr"]) . "]</option>";
		$r .= '<option disabled="disabled">--------</option>';
		$status_2_name = tohtml($statuses[2]["name"]);
		$status_2_abbr = tohtml($statuses[2]["abbr"]);
		$r .= "<option value=\"23\"" . get_selected($v,23);
		$r .= ">" . $status_2_name . " [" . $status_2_abbr . ".." . 
		tohtml($statuses[3]["abbr"]) . "]</option>";
		$r .= "<option value=\"24\"" . get_selected($v,24);
		$r .= ">" . $status_2_name . " [" . $status_2_abbr . ".." . 
		tohtml($statuses[4]["abbr"]) . "]</option>";
		$r .= "<option value=\"25\"" . get_selected($v,25);
		$r .= ">Learning/-ed [" . $status_2_abbr . ".." . 
		tohtml($statuses[5]["abbr"]) . "]</option>";
		$r .= '<option disabled="disabled">--------</option>';
		$status_3_name = tohtml($statuses[3]["name"]);
		$status_3_abbr = tohtml($statuses[3]["abbr"]);
		$r .= "<option value=\"34\"" . get_selected($v,34);
		$r .= ">" . $status_3_name . " [" . $status_3_abbr . ".." . 
		tohtml($statuses[4]["abbr"]) . "]</option>";
		$r .= "<option value=\"35\"" . get_selected($v,35);
		$r .= ">Learning/-ed [" . $status_3_abbr . ".." . 
		tohtml($statuses[5]["abbr"]) . "]</option>";
		$r .= '<option disabled="disabled">--------</option>';
		$r .= "<option value=\"45\"" . get_selected($v,45);
		$r .= ">Learning/-ed [" .  tohtml($statuses[4]["abbr"]) . ".." . 
		tohtml($statuses[5]["abbr"]) . "]</option>";
		$r .= '<option disabled="disabled">--------</option>';
		$r .= "<option value=\"599\"" . get_selected($v,599);
		$r .= ">All known [" . tohtml($statuses[5]["abbr"]) . "+" . 
		tohtml($statuses[99]["abbr"]) . "]</option>";
	}
	return $r;
}

// -------------------------------------------------------------

function get_annotation_position_selectoptions($v){
	if ( ! isset($v) ) $v = 1;
	$r = "<option value=\"1\"" . get_selected($v,1);
	$r .= ">Behind</option>";
	$r .= "<option value=\"3\"" . get_selected($v,3);
	$r .= ">In Front Of</option>";
	$r .= "<option value=\"2\"" . get_selected($v,2);
	$r .= ">Below</option>";
	$r .= "<option value=\"4\"" . get_selected($v,4);
	$r .= ">Above</option>";
	return $r;
}

// -------------------------------------------------------------

function get_paging_selectoptions($currentpage, $pages) {
	$r = "";
	for ($i=1; $i<=$pages; $i++) {
		$r .= "<option value=\"" . $i . "\"" . get_selected($i, $currentpage);
		$r .= ">$i</option>";
	}
	return $r;
}

// -------------------------------------------------------------

function get_wordssort_selectoptions($v) {
	if ( ! isset($v) ) $v = 1;
	$r  = "<option value=\"1\"" . get_selected($v,1);
	$r .= ">Term A-Z</option>";
	$r .= "<option value=\"2\"" . get_selected($v,2);
	$r .= ">Translation A-Z</option>";
	$r .= "<option value=\"3\"" . get_selected($v,3);
	$r .= ">Newest first</option>";
	$r .= "<option value=\"4\"" . get_selected($v,4);
	$r .= ">Oldest first</option>"; 
	$r .= "<option value=\"5\"" . get_selected($v,5);
	$r .= ">Status</option>";
	$r .= "<option value=\"6\"" . get_selected($v,6);
	$r .= ">Score Value (%)</option>";
	$r .= "<option value=\"7\"" . get_selected($v,7);
	$r .= ">Word Count Active Texts</option>";
	return $r;
}

// -------------------------------------------------------------

function get_tagsort_selectoptions($v) {
	if ( ! isset($v) ) $v = 1;
	$r  = "<option value=\"1\"" . get_selected($v,1);
	$r .= ">Tag Text A-Z</option>";
	$r .= "<option value=\"2\"" . get_selected($v,2);
	$r .= ">Tag Comment A-Z</option>";
	$r .= "<option value=\"3\"" . get_selected($v,3);
	$r .= ">Newest first</option>";
	$r .= "<option value=\"4\"" . get_selected($v,4);
	$r .= ">Oldest first</option>"; 
	return $r;
}

// -------------------------------------------------------------

function get_textssort_selectoptions($v) { 
	if ( ! isset($v) ) $v = 1;
	$r  = "<option value=\"1\"" . get_selected($v,1);
	$r .= ">Title A-Z</option>";
	$r .= "<option value=\"2\"" . get_selected($v,2);
	$r .= ">Newest first</option>"; 
	$r .= "<option value=\"3\"" . get_selected($v,3);
	$r .= ">Oldest first</option>"; 
	return $r;
}

// -------------------------------------------------------------

function get_yesno_selectoptions($v) {
	if ( ! isset($v) ) $v = 0;
	$r  = "<option value=\"0\"" . get_selected($v,0);
	$r .= ">No</option>";
	$r .= "<option value=\"1\"" . get_selected($v,1);
	$r .= ">Yes</option>";
	return $r;
}

// -------------------------------------------------------------

function get_andor_selectoptions($v) {
	if ( ! isset($v) ) $v = 0;
	$r  = "<option value=\"0\"" . get_selected($v,0);
	$r .= ">... OR ...</option>";
	$r .= "<option value=\"1\"" . get_selected($v,1);
	$r .= ">... AND ...</option>";
	return $r;
}

// -------------------------------------------------------------

function get_set_status_option($n, $suffix = "") {
	return "<option value=\"s" . $n . $suffix . "\">Set Status to " .
		tohtml(get_status_name($n)) . " [" . tohtml(get_status_abbr($n)) .
		"]</option>";
}

// -------------------------------------------------------------

function get_status_name($n) {
	$statuses = get_statuses();
	return $statuses[$n]["name"];
}

// -------------------------------------------------------------

function get_status_abbr($n) {
	$statuses = get_statuses();
	return $statuses[$n]["abbr"];
}

// -------------------------------------------------------------

function get_colored_status_msg($n) {
	return '<span class="status' . $n . '">&nbsp;' . tohtml(get_status_name($n)) . '&nbsp;[' . tohtml(get_status_abbr($n)) . ']&nbsp;</span>';
}

// -------------------------------------------------------------

function get_multiplewordsactions_selectoptions() {
	$r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"test\">Test Marked Terms</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"spl1\">Increase Status by 1 [+1]</option>";
	$r .= "<option value=\"smi1\">Reduce Status by 1 [-1]</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= get_set_status_option(1);
	$r .= get_set_status_option(5);
	$r .= get_set_status_option(99);
	$r .= get_set_status_option(98);
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"today\">Set Status Date to Today</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"lower\">Set Marked Terms to Lowercase</option>";
	$r .= "<option value=\"cap\">Capitalize Marked Terms</option>";
	$r .= "<option value=\"delsent\">Delete Sentences of Marked Terms</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"addtag\">Add Tag</option>";
	$r .= "<option value=\"deltag\">Remove Tag</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"exp\">Export Marked Terms (Anki)</option>";
	$r .= "<option value=\"exp2\">Export Marked Terms (TSV)</option>";
	$r .= "<option value=\"exp3\">Export Marked Terms (Flexible)</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"del\">Delete Marked Terms</option>";
	return $r;
}

// -------------------------------------------------------------

function get_multipletagsactions_selectoptions() {
	$r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
	$r .= "<option value=\"del\">Delete Marked Tags</option>";
	return $r;
}

// -------------------------------------------------------------

function get_allwordsactions_selectoptions() {
	$r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"testall\">Test ALL Terms</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"spl1all\">Increase Status by 1 [+1]</option>";
	$r .= "<option value=\"smi1all\">Reduce Status by 1 [-1]</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= get_set_status_option(1, "all");
	$r .= get_set_status_option(5, "all");
	$r .= get_set_status_option(99, "all");
	$r .= get_set_status_option(98, "all");
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"todayall\">Set Status Date to Today</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"lowerall\">Set ALL Terms to Lowercase</option>";
	$r .= "<option value=\"capall\">Capitalize ALL Terms</option>";
	$r .= "<option value=\"delsentall\">Delete Sentences of ALL Terms</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"addtagall\">Add Tag</option>";
	$r .= "<option value=\"deltagall\">Remove Tag</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"expall\">Export ALL Terms (Anki)</option>";
	$r .= "<option value=\"expall2\">Export ALL Terms (TSV)</option>";
	$r .= "<option value=\"expall3\">Export ALL Terms (Flexible)</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"delall\">Delete ALL Terms</option>";
	return $r;
}

// -------------------------------------------------------------

function get_alltagsactions_selectoptions() {
	$r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
	$r .= "<option value=\"delall\">Delete ALL Tags</option>";
	return $r;
}

// -------------------------------------------------------------

function get_multipletextactions_selectoptions() {
	$r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"test\">Test Marked Texts</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"addtag\">Add Tag</option>";
	$r .= "<option value=\"deltag\">Remove Tag</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"rebuild\">Reparse Texts</option>";
	$r .= "<option value=\"setsent\">Set Term Sentences</option>";
	$r .= "<option value=\"setactsent\">Set Active Term Sentences</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"arch\">Archive Marked Texts</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"del\">Delete Marked Texts</option>";
	return $r;
}

// -------------------------------------------------------------

function get_multiplearchivedtextactions_selectoptions() {
	$r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"addtag\">Add Tag</option>";
	$r .= "<option value=\"deltag\">Remove Tag</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"unarch\">Unarchive Marked Texts</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"del\">Delete Marked Texts</option>";
	return $r;
}

// -------------------------------------------------------------

function get_texts_selectoptions($lang,$v) {
	global $tbpref;
	if ( ! isset($v) ) $v = '';
	if ( ! isset($lang) ) $lang = '';	
	if ( $lang=="" ) 
		$l = "";	
	else 
		$l = "and TxLgID=" . $lang;
	$r = "<option value=\"\"" . get_selected($v,'');
	$r .= ">[Filter off]</option>";
	$sql = "select TxID, TxTitle, LgName from " . $tbpref . "languages, " . $tbpref . "texts where LgID = TxLgID " . $l . " order by LgName, TxTitle";
	$res = do_mysql_query($sql);
	while ($record = mysqli_fetch_assoc($res)) {
		$d = $record["TxTitle"];
		if ( mb_strlen($d, 'UTF-8') > 30 ) $d = mb_substr($d,0,30, 'UTF-8') . "...";
		$r .= "<option value=\"" . $record["TxID"] . "\"" . get_selected($v,$record["TxID"]) . ">" . tohtml( ($lang!="" ? "" : ($record["LgName"] . ": ")) . $d) . "</option>";
	}
	mysqli_free_result($res);
	return $r;
}

// -------------------------------------------------------------

function print_file_path($filename){
	echo get_file_path($filename);
}
// -------------------------------------------------------------

function get_file_path($filename){
	$file=getSettingWithDefault('set-theme-dir').preg_replace('/.*\//','',$filename);
	if(file_exists ($file))return $file;
	else{
		return $filename;
	}
}

// -------------------------------------------------------------

function makePager ($currentpage, $pages, $script, $formname) {
	if ($currentpage > 1) { 
?>
&nbsp; &nbsp;<a href="<?php echo $script; ?>?page=1"><img src="icn/control-stop-180.png" title="First Page" alt="First Page" /></a>&nbsp;
<a href="<?php echo $script; ?>?page=<?php echo $currentpage-1; ?>"><img  src="icn/control-180.png" title="Previous Page" alt="Previous Page" /></a>&nbsp;
<?php
	} else {
?>
&nbsp; &nbsp;<img src="<?php print_file_path('icn/placeholder.png');?>" alt="-" />&nbsp;
<img src="<?php print_file_path('icn/placeholder.png');?>" alt="-" />&nbsp;
<?php
	} 
?>
Page
<?php
	if ($pages==1) echo '1';
	else {
?>
<select name="page" onchange="{val=document.<?php echo $formname; ?>.page.options[document.<?php echo $formname; ?>.page.selectedIndex].value; location.href='<?php echo $script; ?>?page=' + val;}"><?php echo get_paging_selectoptions($currentpage, $pages); ?></select>
<?php
	}
	echo ' of ' . $pages . '&nbsp; ';
	if ($currentpage < $pages) { 
?>
<a href="<?php echo $script; ?>?page=<?php echo $currentpage+1; ?>"><img src="icn/control.png" title="Next Page" alt="Next Page" /></a>&nbsp;
<a href="<?php echo $script; ?>?page=<?php echo $pages; ?>"><img src="icn/control-stop.png" title="Last Page" alt="Last Page" /></a>&nbsp; &nbsp;
<?php 
	} else {
?>
<img src="<?php print_file_path('icn/placeholder.png');?>" alt="-" />&nbsp;
<img src="<?php print_file_path('icn/placeholder.png');?>" alt="-" />&nbsp; &nbsp; 
<?php
	}
}

// -------------------------------------------------------------

function makeStatusCondition($fieldname, $statusrange) {
	if ($statusrange >= 12 && $statusrange <= 15) {
		return '(' . $fieldname . ' between 1 and ' . ($statusrange % 10) . ')';
	} elseif ($statusrange >= 23 && $statusrange <= 25) {
		return '(' . $fieldname . ' between 2 and ' . ($statusrange % 10) . ')';
	} elseif ($statusrange >= 34 && $statusrange <= 35) {
		return '(' . $fieldname . ' between 3 and ' . ($statusrange % 10) . ')';
	} elseif ($statusrange == 45) {
		return '(' . $fieldname . ' between 4 and 5)';
	} elseif ($statusrange == 599) {
		return $fieldname . ' in (5,99)';
	} else {
		return $fieldname . ' = ' . $statusrange;
	}
}

// -------------------------------------------------------------

function checkStatusRange($currstatus, $statusrange) {
	if ($statusrange >= 12 && $statusrange <= 15) {
		return ($currstatus >= 1 && $currstatus <= ($statusrange % 10));
	} elseif ($statusrange >= 23 && $statusrange <= 25) {
		return ($currstatus >= 2 && $currstatus <= ($statusrange % 10));
	} elseif ($statusrange >= 34 && $statusrange <= 35) {
		return ($currstatus >= 3 && $currstatus <= ($statusrange % 10));
	} elseif ($statusrange == 45) {
		return ($currstatus == 4 || $currstatus == 5);
	} elseif ($statusrange == 599) {
		return ($currstatus == 5 || $currstatus == 99);
	} else {
		return ($currstatus == $statusrange);
	}
}

// -------------------------------------------------------------

function makeStatusClassFilter($status) {
	if ($status == '') return '';
	$liste = array(1,2,3,4,5,98,99);
	if ($status == 599) {
		makeStatusClassFilterHelper(5,$liste);
		makeStatusClassFilterHelper(99,$liste);
	} elseif ($status < 6 || $status > 97) { 
		makeStatusClassFilterHelper($status,$liste);
	} else {
		$from = (int) ($status / 10);
		$to = $status - ($from*10);
		for ($i = $from; $i <= $to; $i++)
			makeStatusClassFilterHelper($i,$liste);
	}
	$r = '';
	foreach ($liste as $v) {
		if($v != -1) $r .= ':not(.status' . $v . ')';
	}
	return $r;
}

// -------------------------------------------------------------

function makeStatusClassFilterHelper($status,&$array) {
	$pos = array_search($status,$array);
	if ($pos !== FALSE) $array[$pos] = -1;
}

// -------------------------------------------------------------

function createTheDictLink($u,$t) {
	// Case 1: url without any ###: append UTF-8-term
	// Case 2: url with one ###: substitute UTF-8-term
	// Case 3: url with two ###enc###: substitute enc-encoded-term
	// see http://php.net/manual/en/mbstring.supported-encodings.php for supported encodings
	$url = trim($u);
	$trm = trim($t);
	$pos = stripos ($url, '###');
	if ($pos !== false) {  // ### found
		$pos2 = strripos ($url, '###');
		if ( ($pos2-$pos-3) > 1 ) {  // 2 ### found
			$enc = trim(substr($url, $pos+3, $pos2-$pos-3));
			$r = substr($url, 0, $pos);
			$r .= urlencode(mb_convert_encoding($trm, $enc, 'UTF-8'));
			if (($pos2+3) < strlen($url)) $r .= substr($url, $pos2+3);
		} 
		elseif ( $pos == $pos2 ) {  // 1 ### found
			$r = str_replace("###", ($trm == '' ? '+' : urlencode($trm)), $url);
		}
	}
	else  // no ### found
		$r = $url . urlencode($trm);
	return $r;
}

// -------------------------------------------------------------

function createDictLinksInEditWin($lang,$word,$sentctljs,$openfirst) {
	global $tbpref;
	$sql = 'select LgDict1URI, LgDict2URI, LgGoogleTranslateURI from ' . $tbpref . 'languages where LgID = ' . $lang;
	$res = do_mysql_query($sql);
	$record = mysqli_fetch_assoc($res);
	$wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
	$wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
	$wb3 = isset($record['LgGoogleTranslateURI']) ? $record['LgGoogleTranslateURI'] : "";
	mysqli_free_result($res);
	$r ='';
	if ($openfirst) {
		$r .= '<script type="text/javascript">';
		$r .= "\n//<![CDATA[\n";
		$r .= makeOpenDictStrJS(createTheDictLink($wb1,$word));
		$r .= "//]]>\n</script>\n";
	}
	$r .= 'Lookup Term: ';
	$r .= makeOpenDictStr(createTheDictLink($wb1,$word), "Dict1"); 
	if ($wb2 != "") 
		$r .= makeOpenDictStr(createTheDictLink($wb2,$word), "Dict2"); 
	if ($wb3 != "") 
		$r .= makeOpenDictStr(createTheDictLink($wb3,$word), "GTr") . ' | Sent.: ' . makeOpenDictStrDynSent($wb3, $sentctljs, "GTr"); 
	return $r;
}

// -------------------------------------------------------------

function makeOpenDictStr($url, $txt) {
	$r = '';
	if ($url != '' && $txt != '') {
		if(substr($url,0,1) == '*') {
			$r = ' <span class="click" onclick="owin(' . prepare_textdata_js(substr($url,1)) . ');">' . tohtml($txt) . '</span> ';
		} 
		else {
			$r = ' <a href="' . $url . '" target="ru">' . tohtml($txt) . '</a> ';
		} 
	}
	return $r;
}

// -------------------------------------------------------------

function makeOpenDictStrJS($url) {
	$r = '';
	if ($url != '') {
		if(substr($url,0,1) == '*') {
			$r = "owin(" . prepare_textdata_js(substr($url,1)) . ");\n";
		} 
		else {
			$r = "top.frames['ru'].location.href=" . prepare_textdata_js($url) . ";\n";
		} 
	}
	return $r;
}

// -------------------------------------------------------------

function makeOpenDictStrDynSent($url, $sentctljs, $txt) {
	$r = '';
	if ($url != '') {
		if (substr($url,0,7) == 'ggl.php') {
			$url = str_replace ('?','?sent=1&',$url);
		}
		if(substr($url,0,1) == '*') {
			$r = '<span class="click" onclick="translateSentence2(' . prepare_textdata_js(substr($url,1)) . ',' . $sentctljs . ');">' . tohtml($txt) . '</span>';
		} 
		else {
			$r = '<span class="click" onclick="translateSentence(' . prepare_textdata_js($url) . ',' . $sentctljs . ');">' . tohtml($txt) . '</span>';
		} 
	}
	return $r;
}

// -------------------------------------------------------------

function createDictLinksInEditWin2($lang,$sentctljs,$wordctljs) {
	global $tbpref;
	$sql = 'select LgDict1URI, LgDict2URI, LgGoogleTranslateURI from ' . $tbpref . 'languages where LgID = ' . $lang;
	$res = do_mysql_query($sql);
	$record = mysqli_fetch_assoc($res);
	$wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
	if(substr($wb1,0,1) == '*') $wb1 = substr($wb1,1);
	$wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
	if(substr($wb2,0,1) == '*') $wb2 = substr($wb2,1);
	$wb3 = isset($record['LgGoogleTranslateURI']) ? $record['LgGoogleTranslateURI'] : "";
	if(substr($wb3,0,1) == '*') $wb3 = substr($wb3,1);
	mysqli_free_result($res);
	$r ='';
	$r .= 'Lookup Term: ';
	$r .= '<span class="click" onclick="translateWord2(' . prepare_textdata_js($wb1) . ',' . $wordctljs . ');">Dict1</span> ';
	if ($wb2 != "") 
		$r .= '<span class="click" onclick="translateWord2(' . prepare_textdata_js($wb2) . ',' . $wordctljs . ');">Dict2</span> ';
	if ($wb3 != "") 
		$r .= '<span class="click" onclick="translateWord2(' . prepare_textdata_js($wb3) . ',' . $wordctljs . ');">GTr</span> | Sent.: <span class="click" onclick="translateSentence2(' . prepare_textdata_js((substr($wb3,0,7) == 'ggl.php')?str_replace ('?','?sent=1&',$wb3):$wb3) . ',' . $sentctljs . ');">GTr</span>';
	return $r;
}

// -------------------------------------------------------------

function makeDictLinks($lang,$wordctljs) {
	global $tbpref;
	$sql = 'select LgDict1URI, LgDict2URI, LgGoogleTranslateURI from ' . $tbpref . 'languages where LgID = ' . $lang;
	$res = do_mysql_query($sql);
	$record = mysqli_fetch_assoc($res);
	$wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
	if(substr($wb1,0,1) == '*') $wb1 = substr($wb1,1);
	$wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
	if(substr($wb2,0,1) == '*') $wb2 = substr($wb2,1);
	$wb3 = isset($record['LgGoogleTranslateURI']) ? $record['LgGoogleTranslateURI'] : "";
	if(substr($wb3,0,1) == '*') $wb3 = substr($wb3,1);
	mysqli_free_result($res);
	$r ='<span class="smaller">';
	$r .= '<span class="click" onclick="translateWord3(' . prepare_textdata_js($wb1) . ',' . $wordctljs . ');">[1]</span> ';
	if ($wb2 != "") 
		$r .= '<span class="click" onclick="translateWord3(' . prepare_textdata_js($wb2) . ',' . $wordctljs . ');">[2]</span> ';
	if ($wb3 != "") 
		$r .= '<span class="click" onclick="translateWord3(' . prepare_textdata_js($wb3) . ',' . $wordctljs . ');">[G]</span>'; 
	$r .= '</span>';
	return $r;
}

// -------------------------------------------------------------

function createDictLinksInEditWin3($lang,$sentctljs,$wordctljs) {
	global $tbpref;
	$sql = 'select LgDict1URI, LgDict2URI, LgGoogleTranslateURI from ' . $tbpref . 'languages where LgID = ' . $lang;
	$res = do_mysql_query($sql);
	$record = mysqli_fetch_assoc($res);
	
	$wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
	if(substr($wb1,0,1) == '*') 
		$f1 = 'translateWord2(' . prepare_textdata_js(substr($wb1,1));
	else 
		$f1 = 'translateWord(' . prepare_textdata_js($wb1);
		
	$wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
	if(substr($wb2,0,1) == '*') 
		$f2 = 'translateWord2(' . prepare_textdata_js(substr($wb2,1));
	else 
		$f2 = 'translateWord(' . prepare_textdata_js($wb2);

	$wb3 = isset($record['LgGoogleTranslateURI']) ? $record['LgGoogleTranslateURI'] : "";
	if(substr($wb3,0,1) == '*') {
		$f3 = 'translateWord2(' . prepare_textdata_js(substr($wb3,1));
		$f4 = 'translateSentence2(' . prepare_textdata_js(substr($wb3,1));
	} else {
		$f3 = 'translateWord(' . prepare_textdata_js($wb3);
		$f4 = 'translateSentence(' . prepare_textdata_js((substr($wb3,0,7) == 'ggl.php')?str_replace ('?','?sent=1&',$wb3):$wb3);
	}

	mysqli_free_result($res);
	$r ='';
	$r .= 'Lookup Term: ';
	$r .= '<span class="click" onclick="' . $f1 . ',' . $wordctljs . ');">Dict1</span> ';
	if ($wb2 != "") 
		$r .= '<span class="click" onclick="' . $f2 . ',' . $wordctljs . ');">Dict2</span> ';
	if ($wb3 != "") 
		$r .= '<span class="click" onclick="' . $f3 . ',' . $wordctljs . ');">GTr</span> | Sent.: <span class="click" onclick="' . $f4 . ',' . $sentctljs . ');">GTr</span>'; 
	return $r;
}

// -------------------------------------------------------------

function checkTest($val, $name) {
	if (! isset($_REQUEST[$name])) return ' ';
	if (! is_array($_REQUEST[$name])) return ' ';
	if (in_array($val,$_REQUEST[$name])) return ' checked="checked" ';
	else return ' ';
}

// -------------------------------------------------------------

function strToHex($string)
{
  $hex='';
  for ($i=0; $i < strlen($string); $i++)
  {
  		$h = dechex(ord($string[$i]));
  		if ( strlen($h) == 1 ) 
  			$hex .= "0" . $h;
  		else
  		  $hex .= $h;
  }
  return strtoupper($hex);
}

// -------------------------------------------------------------

function strToClassName($string)
{
	// escapes everything to "¤xx" but not 0-9, a-z, A-Z, and unicode >= (hex 00A5, dec 165)
	$l = mb_strlen ($string, 'UTF-8');
	$r = '';
  for ($i=0; $i < $l; $i++)
  {
  	$c = mb_substr($string,$i,1, 'UTF-8');
  	$o = ord($c);
  	if (
  		($o < 48) || 
  		($o > 57 && $o < 65) || 
  		($o > 90 && $o < 97) || 
  		($o > 122 && $o < 165)
  		)
  		$r .= '¤' . strToHex($c);
  	else 
  		$r .= $c;
  }
  return $r;
}

// -------------------------------------------------------------

function anki_export($sql) {
	// WoID, LgRightToLeft, LgRegexpWordCharacters, LgName, WoText, WoTranslation, WoRomanization, WoSentence, taglist
	$res = do_mysql_query($sql);
	$x = '';
	while ($record = mysqli_fetch_assoc($res)) {
		$rtlScript = $record['LgRightToLeft'];
		$span1 = ($rtlScript ? '<span dir="rtl">' : '');
		$span2 = ($rtlScript ? '</span>' : '');
		$lpar = ($rtlScript ? ']' : '[');
		$rpar = ($rtlScript ? '[' : ']');
		$sent = tohtml(repl_tab_nl($record["WoSentence"]));
		$sent1 = str_replace("{", '<span style="font-weight:600; color:#0000ff;">' . $lpar, str_replace("}", $rpar . '</span>', 
			mask_term_in_sentence($sent,$record["LgRegexpWordCharacters"])
		));
		$sent2 = str_replace("{", '<span style="font-weight:600; color:#0000ff;">', str_replace("}", '</span>', $sent));
		$x .= $span1 . tohtml(repl_tab_nl($record["WoText"])) . $span2 . "\t" . 
		tohtml(repl_tab_nl($record["WoTranslation"])) . "\t" . 
		tohtml(repl_tab_nl($record["WoRomanization"])) . "\t" . 
		$span1 . $sent1 . $span2 . "\t" . 
		$span1 . $sent2 . $span2 . "\t" . 
		tohtml(repl_tab_nl($record["LgName"])) . "\t" . 
		tohtml($record["WoID"]) . "\t" . 
		tohtml($record["taglist"]) .  
		"\r\n";
	}
	mysqli_free_result($res);
	header('Content-type: text/plain; charset=utf-8');
	header("Content-disposition: attachment; filename=lwt_anki_export_" . date('Y-m-d-H-i-s') . ".txt");
	echo $x;
	exit();
}

// -------------------------------------------------------------

function tsv_export($sql) {
	// WoID, LgName, WoText, WoTranslation, WoRomanization, WoSentence, WoStatus, taglist
	$res = do_mysql_query($sql);
	$x = '';
	while ($record = mysqli_fetch_assoc($res)) {
		$x .= repl_tab_nl($record["WoText"]) . "\t" . 
		repl_tab_nl($record["WoTranslation"]) . "\t" . 
		repl_tab_nl($record["WoSentence"]) . "\t" . 
		repl_tab_nl($record["WoRomanization"]) . "\t" . 
		$record["WoStatus"] . "\t" . 
		repl_tab_nl($record["LgName"]) . "\t" . 
		$record["WoID"] . "\t" . 
		$record["taglist"] . "\r\n";
	}
	mysqli_free_result($res);
	header('Content-type: text/plain; charset=utf-8');
	header("Content-disposition: attachment; filename=lwt_tsv_export_" . date('Y-m-d-H-i-s') . ".txt");
	echo $x;
	exit();
}

// -------------------------------------------------------------

function flexible_export($sql) {
	// WoID, LgName, LgExportTemplate, LgRightToLeft, WoText, WoTextLC, WoTranslation, WoRomanization, WoSentence, WoStatus, taglist
	$res = do_mysql_query($sql);
	$x = '';
	while ($record = mysqli_fetch_assoc($res)) {
		if (isset($record['LgExportTemplate'])) {
			$woid = $record['WoID'] + 0;
			$langname = repl_tab_nl($record['LgName']);
			$rtlScript = $record['LgRightToLeft'];
			$span1 = ($rtlScript ? '<span dir="rtl">' : '');
			$span2 = ($rtlScript ? '</span>' : '');
			$term = repl_tab_nl($record['WoText']);
			$term_lc = repl_tab_nl($record['WoTextLC']);
			$transl = repl_tab_nl($record['WoTranslation']);
			$rom = repl_tab_nl($record['WoRomanization']);
			$sent_raw = repl_tab_nl($record['WoSentence']);
			$sent = str_replace('{','',str_replace('}','',$sent_raw));
			$sent_c = mask_term_in_sentence_v2($sent_raw);
			$sent_d = str_replace('{','[',str_replace('}',']',$sent_raw));
			$sent_x = str_replace('{','{{c1::',str_replace('}','}}',$sent_raw));
			$sent_y = str_replace('{','{{c1::',str_replace('}','::' . $transl . '}}',$sent_raw));
			$status = $record['WoStatus'] + 0;
			$taglist = trim($record['taglist']);
			$xx = repl_tab_nl($record['LgExportTemplate']);	
			$xx = str_replace('%w',$term,$xx);		
			$xx = str_replace('%t',$transl,$xx);		
			$xx = str_replace('%s',$sent,$xx);		
			$xx = str_replace('%c',$sent_c,$xx);		
			$xx = str_replace('%d',$sent_d,$xx);		
			$xx = str_replace('%r',$rom,$xx);		
			$xx = str_replace('%a',$status,$xx);		
			$xx = str_replace('%k',$term_lc,$xx);		
			$xx = str_replace('%z',$taglist,$xx);		
			$xx = str_replace('%l',$langname,$xx);		
			$xx = str_replace('%n',$woid,$xx);		
			$xx = str_replace('%%','%',$xx);		
			$xx = str_replace('$w',$span1 . tohtml($term) . $span2,$xx);		
			$xx = str_replace('$t',tohtml($transl),$xx);		
			$xx = str_replace('$s',$span1 . tohtml($sent) . $span2,$xx);		
			$xx = str_replace('$c',$span1 . tohtml($sent_c) . $span2,$xx);		
			$xx = str_replace('$d',$span1 . tohtml($sent_d) . $span2,$xx);		
			$xx = str_replace('$x',$span1 . tohtml($sent_x) . $span2,$xx);		
			$xx = str_replace('$y',$span1 . tohtml($sent_y) . $span2,$xx);		
			$xx = str_replace('$r',tohtml($rom),$xx);		
			$xx = str_replace('$k',$span1 . tohtml($term_lc) . $span2,$xx);		
			$xx = str_replace('$z',tohtml($taglist),$xx);		
			$xx = str_replace('$l',tohtml($langname),$xx);		
			$xx = str_replace('$$','$',$xx);		
			$xx = str_replace('\\t',"\t",$xx);		
			$xx = str_replace('\\n',"\n",$xx);		
			$xx = str_replace('\\r',"\r",$xx);		
			$xx = str_replace('\\\\','\\',$xx);		
			$x .= $xx;
		}
	}
	mysqli_free_result($res);
	header('Content-type: text/plain; charset=utf-8');
	header("Content-disposition: attachment; filename=lwt_flexible_export_" . date('Y-m-d-H-i-s') . ".txt");
	echo $x;
	exit();
}

// -------------------------------------------------------------

function mask_term_in_sentence_v2($s) {
	$l = mb_strlen($s,'utf-8');
	$r = '';
	$on = 0;
	for ($i=0; $i < $l; $i++) {
		$c = mb_substr($s, $i, 1, 'UTF-8');
		if ($c == '}') { 
			$on = 0;
			continue;
		}
		if ($c == '{') {
			$on = 1;
			$r .= '[...]';
			continue;
		}
		if ($on == 0) {
			$r .= $c;
		}
	}
	return $r;
}

// -------------------------------------------------------------

function repl_tab_nl($s) {
	$s = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $s);
	$s = preg_replace('/\s/u', ' ', $s);
	$s = preg_replace('/\s{2,}/u', ' ', $s);
	return trim($s);
}

// -------------------------------------------------------------

function mask_term_in_sentence($s,$regexword) {
	$l = mb_strlen($s,'utf-8');
	$r = '';
	$on = 0;
	for ($i=0; $i < $l; $i++) {
		$c = mb_substr($s, $i, 1, 'UTF-8');
		if ($c == '}') $on = 0;
		if ($on) {
			if (preg_match('/[' . $regexword . ']/u', $c)) {
   			$r .= '•';
			} else {
   			$r .= $c;
			}	
		}
		else {
			$r .= $c;
		}
		if ($c == '{') $on = 1;
	}
	return $r;
}

// -------------------------------------------------------------

function textwordcount($text) {
	global $tbpref;
	return get_first_value('select count(distinct lower(Ti2Text)) as value from ' . $tbpref . 'textitems2 where Ti2WordCount = 1 and Ti2TxID = ' . $text);
}

// -------------------------------------------------------------

function textexprcount($text) {
	global $tbpref;
	return get_first_value('select count(distinct lower(Ti2Text)) as value from ' . $tbpref . 'textitems2 where Ti2WordCount > 1 and Ti2TxID = ' . $text);
}

// -------------------------------------------------------------

function textworkcount($text) {
	global $tbpref;
	return get_first_value('select count(distinct lower(Ti2Text)) as value from ' . $tbpref . 'textitems2 where Ti2WordCount = 1 and Ti2TxID = ' . $text . ' and Ti2WoID != 0');
}

// -------------------------------------------------------------

function texttodocount($text) {
	global $tbpref;
	return '<span title="To Do" class="status0">&nbsp;' . 
	(get_first_value('SELECT count(DISTINCT LOWER(Ti2Text)) as value FROM ' . $tbpref . 'textitems2 WHERE Ti2WordCount=1 and Ti2WoID=0 and Ti2TxID=' . $text)) . '&nbsp;</span>';
}

// -------------------------------------------------------------

function texttodocount2($text) {
	global $tbpref;
	$c = get_first_value('SELECT count(DISTINCT LOWER(Ti2Text)) as value FROM ' . $tbpref . 'textitems2 WHERE Ti2WordCount=1 and Ti2WoID=0 and Ti2TxID=' . $text);
	if ($c > 0 ){ 
		$show_buttons=getSettingWithDefault('set-words-to-do-buttons');
		$dict = get_first_value('select LgGoogleTranslateURI as value from ' . $tbpref . 'languages, ' . $tbpref . 'texts where LgID = TxLgID and TxID = ' . $text);
		$tl=preg_replace('/.*[?&]tl=([a-zA-Z\-]*)(&.*)*$/','$1',$dict);
		$sl=preg_replace('/.*[?&]sl=([a-zA-Z\-]*)(&.*)*$/','$1',$dict);
		$res = '<span title="To Do" class="status0">&nbsp;' . $c . '&nbsp;</span>&nbsp;';
		if($sl!=$dict and $tl!=$dict)$res .='<img src="icn/script-import.png" onclick="{top.frames[\'ro\'].location.href=\'bulk_translate_words.php?tid=' . $text . '&offset=0&sl=' . $sl . '&tl=' . $tl . '\';}" style="cursor: pointer;vertical-align:middle" title="Lookup New Words" alt="Lookup New Words" />&nbsp;&nbsp;&nbsp;';
		if($show_buttons!=2)$res .='<input type="button" onclick="iknowall(' . $text . ');" value=" I KNOW ALL " />';
		if($show_buttons!=1)$res.='<input type="button" onclick="ignoreall(' . $text . ');" value=" IGNORE ALL " />';
		return $res	;}
	else
		return '<span title="To Do" class="status0">&nbsp;' . $c . '&nbsp;</span>';
}

// -------------------------------------------------------------

function getSentence($seid, $wordlc,$mode) {
	global $tbpref;
	$res = do_mysql_query('select concat(\'​\',group_concat(Ti2Text order by Ti2Order asc SEPARATOR \'​\'),\'​\') as SeText, Ti2TxID as SeTxID, LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar from ' . $tbpref . 'textitems2, ' . $tbpref . 'languages where Ti2LgID = LgID and Ti2WordCount<2 and Ti2SeID= ' . $seid);
	$record = mysqli_fetch_assoc($res);
	$removeSpaces = $record["LgRemoveSpaces"];
	$splitEachChar = $record['LgSplitEachChar'];
	$txtid = $record["SeTxID"];
	if($removeSpaces==1 && $splitEachChar==0){
		$text = $record["SeText"];
		$wordlc = '[​]*' . preg_replace('/(.)/u', "$1[​]*", $wordlc);
		$pattern = '/(?<=[​])(' . $wordlc . ')(?=[​])/ui';
	}
	else{
		$text = str_replace(array('​​','​','ÿ'),array('ÿ','','​'),$record["SeText"]);
		if($splitEachChar==0){
			$pattern = '/(?<![' . $record["LgRegexpWordCharacters"] . '])(' . remove_spaces($wordlc, $removeSpaces) . ')(?![' . $record["LgRegexpWordCharacters"] . '])/ui';
		}
		else $pattern ='/(' .  $wordlc . ')/ui';
	}
	$se = str_replace('​','',preg_replace ($pattern,'<b>$0</b>',$text));
	$sejs = str_replace('​','',preg_replace ($pattern,'{$0}',$text));
	if ($mode > 1) {
		if($removeSpaces==1 && $splitEachChar==0){
			$prevseSent = get_first_value('select concat(\'​\',group_concat(Ti2Text order by Ti2Order asc SEPARATOR \'​\'),\'​\') as value from ' . $tbpref . 'sentences, ' . $tbpref . 'textitems2 where Ti2SeID = SeID and SeID < ' . $seid . ' and SeTxID = ' . $txtid . " and trim(SeText) not in ('¶','') group by SeID order by SeID desc");
		}
		else{
			$prevseSent = get_first_value('select SeText as value from ' . $tbpref . 'sentences where SeID < ' . $seid . ' and SeTxID = ' . $txtid . " and trim(SeText) not in ('¶','') order by SeID desc");
		}
		if (isset($prevseSent)){
			$se = preg_replace ($pattern,'<b>$0</b>',$prevseSent) . $se;
			$sejs = preg_replace ($pattern,'{$0}',$prevseSent) . $sejs;
		}
		if ($mode > 2) {
			if($removeSpaces==1 && $splitEachChar==0){
				$nextSent = get_first_value('select concat(\'​\',group_concat(Ti2Text order by Ti2Order asc SEPARATOR \'​\'),\'​\') as  value from ' . $tbpref . 'sentences, ' . $tbpref . 'textitems2 where Ti2SeID = SeID and SeID > ' . $seid . ' and SeTxID = ' . $txtid . " and trim(SeText) not in ('¶','') group by SeID order by SeID asc");
			}
			else{
				$nextSent = get_first_value('select SeText as value from ' . $tbpref . 'sentences where SeID > ' . $seid . ' and SeTxID = ' . $txtid . " and trim(SeText) not in ('¶','') order by SeID asc");
			}
			if (isset($nextSent)){
				$se .= preg_replace ($pattern,'<b>$0</b>',$nextSent);
				$sejs .= preg_replace ($pattern,'{$0}',$nextSent);
			}
		}
	}
	mysqli_free_result($res);
	if($removeSpaces==1){
		$se = str_replace('​', '', $se);
		$sejs = str_replace('​', '', $sejs);
	}
	return array($se,$sejs); // [0]=html, word in bold
	                         // [1]=text, word in {} 
}

// -------------------------------------------------------------

function get20Sentences($lang, $wordlc, $wid, $jsctlname, $mode) {
	global $tbpref;
	$r = '<p><b>Sentences in active texts with <i>' . tohtml($wordlc) . '</i></b></p><p>(Click on <img src="icn/tick-button.png" title="Choose" alt="Choose" /> to copy sentence into above term)</p>';
	if(empty($wid)){
		$sql = 'SELECT DISTINCT SeID, SeText FROM ' . $tbpref . 'sentences, ' . $tbpref . 'textitems2 WHERE lower(Ti2Text) = ' . convert_string_to_sqlsyntax($wordlc) . ' AND Ti2WoID = 0 AND SeID = Ti2SeID AND SeLgID = ' . $lang . ' order by CHAR_LENGTH(SeText), SeText limit 0,20';
	}
	else if($wid==-1){
		$res = do_mysql_query('select LgRegexpWordCharacters,LgRemoveSpaces from ' . $tbpref . 'languages where LgID = ' . $lang);
		$record = mysqli_fetch_assoc($res);
		mysqli_free_result($res);
		$removeSpaces = $record["LgRemoveSpaces"];
		if(!($removeSpaces==1)){
			$pattern = convert_string_to_sqlsyntax('(^|[^' . $record["LgRegexpWordCharacters"] . '])' . remove_spaces($wordlc, $removeSpaces) . '([^' . $record["LgRegexpWordCharacters"] . ']|$)');
		}
		else{
			$pattern = convert_string_to_sqlsyntax($wordlc);
		}
		$sql = 'SELECT DISTINCT SeID, SeText FROM ' . $tbpref . 'sentences WHERE SeText rlike ' . $pattern . ' AND SeLgID = ' . $lang . ' order by CHAR_LENGTH(SeText), SeText limit 0,20';
	}
	else{
		$sql = 'SELECT DISTINCT SeID, SeText FROM ' . $tbpref . 'sentences, ' . $tbpref . 'textitems2 WHERE Ti2WoID = ' . $wid . ' AND SeID = Ti2SeID AND SeLgID = ' . $lang . ' order by CHAR_LENGTH(SeText), SeText limit 0,20';
	}
	$res = do_mysql_query($sql);
	$r .= '<p>';
	$last = '';
	while ($record = mysqli_fetch_assoc($res)) {
		if ($last != $record['SeText']) {
			$sent = getSentence($record['SeID'], $wordlc,$mode);
			if(mb_strstr ($sent[1],'}', 'UTF-8')){
				$r .= '<span class="click" onclick="{' . $jsctlname . '.value=' . prepare_textdata_js($sent[1]) . '; makeDirty();}"><img src="icn/tick-button.png" title="Choose" alt="Choose" /></span> &nbsp;' . $sent[0] . '<br />';
			}
		}
		$last = $record['SeText'];
	}
	mysqli_free_result($res);
	$r .= '</p>';
	return $r;
}

// -------------------------------------------------------------

function getsqlscoreformula ($method) {
	// $method = 2 (today)
	// $method = 3 (tomorrow)
	// Formula: {{{2.4^{Status}+Status-Days-1} over Status -2.4} over 0.14325248}
		
	if ($method == 3) return 'GREATEST(-125, CASE WHEN WoStatus > 5 THEN 100 WHEN WoStatus = 1 THEN ROUND(-7 -7 * DATEDIFF(NOW(),WoStatusChanged)) WHEN WoStatus = 2 THEN ROUND(3.4 - 3.5 * DATEDIFF(NOW(),WoStatusChanged)) WHEN WoStatus = 3 THEN ROUND(17.7 - 2.3 * DATEDIFF(NOW(),WoStatusChanged)) WHEN WoStatus = 4 THEN ROUND(44.65 - 1.75 * DATEDIFF(NOW(),WoStatusChanged)) WHEN WoStatus = 5 THEN ROUND(98.6 - 1.4 * DATEDIFF(NOW(),WoStatusChanged)) END)';

	elseif ($method == 2) return 'GREATEST(-125, CASE WHEN WoStatus > 5 THEN 100 WHEN WoStatus = 1 THEN ROUND(-7 * DATEDIFF(NOW(),WoStatusChanged)) WHEN WoStatus = 2 THEN ROUND(6.9 - 3.5 * DATEDIFF(NOW(),WoStatusChanged)) WHEN WoStatus = 3 THEN ROUND(20 - 2.3 * DATEDIFF(NOW(),WoStatusChanged)) WHEN WoStatus = 4 THEN ROUND(46.4 - 1.75 * DATEDIFF(NOW(),WoStatusChanged)) WHEN WoStatus = 5 THEN ROUND(100 - 1.4 * DATEDIFF(NOW(),WoStatusChanged)) END)';
	
	else return '0';
	
}

// -------------------------------------------------------------

function AreUnknownWordsInSentence ($sentno) {
	global $tbpref;
	$x = get_first_value("SELECT distinct Ti2Text as value FROM " . $tbpref . "textitems2 where Ti2SeID = " . $sentno . " AND Ti2WordCount = 1 and Ti2WoID = 0 limit 1");
//	$x = get_first_value("SELECT distinct ifnull(WoTextLC,'') as value FROM (" . $tbpref . "textitems left join " . $tbpref . "words on (TiTextLC = WoTextLC) and (TiLgID = WoLgID)) where TiSeID = " . $sentno . " AND TiWordCount = 1 AND TiIsNotWord = 0 order by WoTextLC asc limit 1");
	// echo $sentno . '/' . isset($x) . '/' . $x . '/';
	if ( isset($x) ) {
		if ( $x == '' ) return true;
	}
	return false;
}

// -------------------------------------------------------------

function get_statuses() {
	static $statuses;
	if (!$statuses) {
		$statuses = array(
				 1 => array("abbr" =>   "1", "name" => "Learning"),
				 2 => array("abbr" =>   "2", "name" => "Learning"),
				 3 => array("abbr" =>   "3", "name" => "Learning"),
				 4 => array("abbr" =>   "4", "name" => "Learning"),
				 5 => array("abbr" =>   "5", "name" => "Learned"),
				99 => array("abbr" => "WKn", "name" => "Well Known"),
				98 => array("abbr" => "Ign", "name" => "Ignored"),
		);
	}
	return $statuses;
}

// -------------------------------------------------------------

function get_languages() {
	global $tbpref;
	$langs = array();
	$sql = "select LgID, LgName from " . $tbpref . "languages where LgName<>''";
	$res = do_mysql_query($sql);
	while ($record = mysqli_fetch_assoc($res)) {
		$langs[$record['LgName']] = $record['LgID'];
	}
	mysqli_free_result($res);
	return $langs;
}

// -------------------------------------------------------------

function get_setting_data() {
	static $setting_data;
	if (! $setting_data) {
		$setting_data = array(
		'set-text-h-frameheight-no-audio' => 
		array("dft" => '140', "num" => 1, "min" => 10, "max" => 999),
		'set-text-h-frameheight-with-audio' => 
		array("dft" => '200', "num" => 1, "min" => 10, "max" => 999),
		'set-text-l-framewidth-percent' => 
		array("dft" => '50', "num" => 1, "min" => 5, "max" => 95),
		'set-text-r-frameheight-percent' => 
		array("dft" => '50', "num" => 1, "min" => 5, "max" => 95),
		'set-test-h-frameheight' => 
		array("dft" => '140', "num" => 1, "min" => 10, "max" => 999),
		'set-test-l-framewidth-percent' => 
		array("dft" => '50', "num" => 1, "min" => 5, "max" => 95),
		'set-test-r-frameheight-percent' => 
		array("dft" => '50', "num" => 1, "min" => 5, "max" => 95),
		'set-words-to-do-buttons' => 
		array("dft" => '1', "num" => 0),
		'set-tooltip-mode' => 
		array("dft" => '2', "num" => 0),
		'set-display-text-frame-term-translation' => 
		array("dft" => '', "num" => 0),
		'set-text-frame-annotation-position' => 
		array("dft" => '2', "num" => 0),
		'set-test-main-frame-waiting-time' => 
		array("dft" => '0', "num" => 1, "min" => 0, "max" => 9999),
		'set-test-edit-frame-waiting-time' => 
		array("dft" => '500', "num" => 1, "min" => 0, "max" => 99999999),
		'set-test-sentence-count' => 
		array("dft" => '1', "num" => 0),
		'set-tts' => 
		array("dft" => '1', "num" => 0),
		'set-term-sentence-count' => 
		array("dft" => '1', "num" => 0),
		'set-archivedtexts-per-page' => 
		array("dft" => '100', "num" => 1, "min" => 1, "max" => 9999),
		'set-texts-per-page' => 
		array("dft" => '10', "num" => 1, "min" => 1, "max" => 9999),
		'set-terms-per-page' => 
		array("dft" => '100', "num" => 1, "min" => 1, "max" => 9999),
		'set-tags-per-page' => 
		array("dft" => '100', "num" => 1, "min" => 1, "max" => 9999),
		'set-articles-per-page' => 
		array("dft" => '10', "num" => 1, "min" => 1, "max" => 9999),
		'set-feeds-per-page' => 
		array("dft" => '50', "num" => 1, "min" => 1, "max" => 9999),
		'set-max-articles-with-text' => 
		array("dft" => '100', "num" => 1, "min" => 1, "max" => 9999),
		'set-max-articles-without-text' => 
		array("dft" => '250', "num" => 1, "min" => 1, "max" => 9999),
		'set-max-texts-per-feed' => 
		array("dft" => '20', "num" => 1, "min" => 1, "max" => 9999),
		'set-ggl-translation-per-page' => 
		array("dft" => '100', "num" => 1, "min" => 1, "max" => 9999),
		'set-regex-mode' => 
		array("dft" => '', "num" => 0),
		'set-theme_dir' => 
		array("dft" => 'themes/default/', "num" => 0),
		'set-show-text-word-counts' => 
		array("dft" => '1', "num" => 0),
		'set-text-visit-statuses-via-key' => 
		array("dft" => '', "num" => 0),
		'set-term-translation-delimiters' => 
		array("dft" => '/;|', "num" => 0),
		'set-mobile-display-mode' => 
		array("dft" => '0', "num" => 0),
		'set-similar-terms-count' => 
		array("dft" => '0', "num" => 1, "min" => 0, "max" => 9)
		);
	}
	return $setting_data;
}

// -------------------------------------------------------------

function reparse_all_texts() {
	global $tbpref;
	runsql('TRUNCATE ' . $tbpref . 'sentences','');
	runsql('TRUNCATE ' . $tbpref . 'textitems2','');
	adjust_autoincr('sentences','SeID');
	set_word_count ();
	$sql = "select TxID, TxLgID from " . $tbpref . "texts";
	$res = do_mysql_query($sql);
	while ($record = mysqli_fetch_assoc($res)) {
		$id = $record['TxID'];
		splitCheckText(
			get_first_value('select TxText as value from ' . $tbpref . 'texts where TxID = ' . $id), $record['TxLgID'], $id );
	}
	mysqli_free_result($res);
}

// -------------------------------------------------------------

function getLanguage($lid) {
	global $tbpref;
	if ( ! isset($lid) ) return '';
	if ( trim($lid) == '' ) return '';
	if ( ! is_numeric($lid) ) return '';
	$r = get_first_value("select LgName as value from " . $tbpref . "languages where LgID='" . $lid . "'");
	if ( isset($r) ) return $r;
	return '';
}

// -------------------------------------------------------------

function getScriptDirectionTag($lid) {
	global $tbpref;
	if ( ! isset($lid) ) return '';
	if ( trim($lid) == '' ) return '';
	if ( ! is_numeric($lid) ) return '';
	$r = get_first_value("select LgRightToLeft as value from " . $tbpref . "languages where LgID='" . $lid . "'");
	if ( isset($r) ) {
		if ($r) return ' dir="rtl" '; 
	}
	return '';
}

// -------------------------------------------------------------

function echodebug($var,$text) {
	global $debug;
	if (! $debug ) return;
	echo "<pre> **DEBUGGING** " . tohtml($text) . ' = [[[';
	print_r($var);
	echo "]]]\n--------------</pre>";
}

// -------------------------------------------------------------

function splitCheckText($text, $lid, $id) {//todo
	// $id = -1     => Check, return protocol
	// $id = -2     => Only return sentence array
	// $id = TextID => Split: insert sentences/textitems entries in DB
	global $tbpref;
	$r = '';
	$sql = "select * from " . $tbpref . "languages where LgID=" . $lid;
	$res = do_mysql_query($sql);
	$record = mysqli_fetch_assoc($res);
	if ($record == FALSE) my_die("Language data not found: $sql");
	$removeSpaces = $record['LgRemoveSpaces'];
	$splitEachChar = $record['LgSplitEachChar'];
	$splitSentence = $record['LgRegexpSplitSentences'];
	$noSentenceEnd = $record['LgExceptionsSplitSentences'];
	$termchar = $record['LgRegexpWordCharacters'];
	$replace = explode("|",$record['LgCharacterSubstitutions']);
	$rtlScript = $record['LgRightToLeft'];
	mysqli_free_result($res);
	$s = prepare_textdata($text);
	$s = str_replace("\n", " ¶ ", $s);
	$s = str_replace("\t", " ", $s);
	$s = trim($s);
	if ($splitEachChar) {
		$s = preg_replace('/([^\s])/u', "$1 ", $s);
	}
	$s = preg_replace('/\s{2,}/u', ' ', $s);
	if ($id == -1) $r .= "<div style=\"margin-right:50px;\"><h4>Text</h4><p " .  ($rtlScript ? 'dir="rtl"' : '') . ">" . str_replace("¶", "<br /><br />", tohtml($s)). "</p>";

	$s = str_replace('{', '[', $s);	// because of sent. spc. char
	$s = str_replace('}', ']', $s);	
	foreach ($replace as $value) {
		$fromto = explode("=",trim($value));
		if(count($fromto) >= 2) {
  		$s = str_replace(trim($fromto[0]), trim($fromto[1]), $s);
		}
	}
	$s = trim($s);
	
	$s = preg_replace_callback("/(\S+)\s*((\.+)|([$splitSentence])(['`\"”)\]‘’‹›“„«»』」]*))(?=(\s+)(\S+))/u", function ($matches) use ($noSentenceEnd) {
		if(strpos("\n",$matches[6])!==false)return $matches[0];
		if(is_numeric ($matches[1])){
			 if( strlen ($matches[1])<3)return $matches[0];
		}
		else if($matches[3] && (preg_match('/^[B-DF-HJ-NP-TV-XZb-df-hj-np-tv-xz][b-df-hj-np-tv-xzñ]*$/u',$matches[1]) || preg_match('/^[AEIOUY]$/',$matches[1])))return $matches[0];
		if(preg_match('/[.:]/',$matches[2])){
			if(preg_match('/^[a-z]/', $matches[7]))return $matches[0];
		}
		if($noSentenceEnd != '' && preg_match('/^(' . $noSentenceEnd . ')$/',$matches[0]))return $matches[0];
		return $matches[0]."\n";
	},$s);
	$s = str_replace(" ¶", "\n¶",str_replace("¶", "¶\n",$s));
	
	if ($s=='') {
		$textLines = array($s);
	} else {
		$s = explode("\n",$s);
		$l = count($s);
		for ($i=0; $i<$l; $i++) {
  		$s[$i] = trim($s[$i]);
			if ($s[$i] != '') {
				$pos = strpos($splitSentence, $s[$i]);
				while ($pos !== false && $i > 0) {
					$s[$i-1] .= " " . $s[$i];
					for ($j=$i+1; $j<$l; $j++) $s[$j-1] = $s[$j];
					array_pop($s);
					$l = count($s);
					$pos = strpos($splitSentence, $s[$i]);
				}
			}
		}
		$l = count($s);
		$textLines = array();
		for ($i=0; $i<$l; $i++) {
			$zz = trim($s[$i]);
			if ($zz != '' ) $textLines[] = $zz;
		}
	}
	
	if ($id == -2) {
	
		////////////////////////////////////
		// Only return sentence array
		
		return $textLines;
		
	}

	$lineWords = array();
		
	if ($id == -1) {
	
		////////////////////////////////////
		// Check, return protocol
		
		$wordList = array();
		$wordSeps = array();
		$r .= "<h4>Sentences</h4><ol>";
		$sentNumber = 0;
		foreach ($textLines as $value) { 
			$r .= "<li " .  ($rtlScript ? 'dir="rtl"' : '') . ">" . tohtml(remove_spaces($value, $removeSpaces)) . "</li>";
			$lineWords[$sentNumber] = preg_split('/([^' . $termchar . ']{1,})/u', $value, -1, PREG_SPLIT_DELIM_CAPTURE );
			$l = count($lineWords[$sentNumber]);
			for ($i=0; $i<$l; $i++) {
				$term = mb_strtolower($lineWords[$sentNumber][$i], 'UTF-8');
				if ($term != '') {
					if ($i % 2 == 0) {
						if(array_key_exists($term,$wordList)) {
							$wordList[$term][0]++;
							$wordList[$term][1][] = $sentNumber;
						}
						else {
							$wordList[$term] = array(1, array($sentNumber));
						}
					} else {
						$ww = remove_spaces($term, $removeSpaces);
						if(array_key_exists($ww,$wordSeps))
							$wordSeps[$ww]++;
						else	
							$wordSeps[$ww]=1;
					}
				}
			}
			$sentNumber += 1;
		} 
		$r .= "</ol><h4>Word List <span class=\"red2\">(red = already saved)</span></h4><ul>";
		ksort($wordList); 
		$anz = 0;
		foreach ($wordList as $key => $value) {
			$trans = get_first_value("select WoTranslation as value from " . $tbpref . "words where WoLgID = " . $lid . " and WoTextLC = " . convert_string_to_sqlsyntax($key));
			if (! isset($trans)) $trans="";
			if ($trans == "*") $trans="";
			if ($trans != "") 
				$r .= "<li " .  ($rtlScript ? 'dir="rtl"' : '') . "><span class=\"red2\">[" . tohtml($key) . "] — " . $value[0] . " - " . tohtml(repl_tab_nl($trans)) . "</span></li>";
			else
				$r .= "<li " .  ($rtlScript ? 'dir="rtl"' : '') . ">[" . tohtml($key) . "] — " . $value[0] . "</li>";	
			$anz++;
		} 
		$r .= "</ul><p>TOTAL: " . $anz . "</p><h4>Non-Word List</h4><ul>";
		if(array_key_exists('',$wordSeps)) unset($wordSeps['']);
		ksort($wordSeps); 
		$anz = 0;
		foreach ($wordSeps as $key => $value) { 
			$r .= "<li>[" . str_replace(" ", "<span class=\"backgray\">&nbsp;</span>", tohtml($key)) . "] — " . $value . "</li>";
			$anz++;
		} 
		$r .=  "</ul><p>TOTAL: " . $anz . "</p></div>"; 
		return $r;
	}
	
	////////////////////////////////////
	// Split: insert sentences/textitems entries in DB
	
	$sentNumber = 0;
	$lfdnr =0;
	$mwlen[2]=1;
	$sqlarr=array();
	//set_word_count ();
	$max=get_first_value("SELECT max(WoWordCount) as value FROM " . $tbpref . "words where WoLgID = " . $lid);
	$res = do_mysql_query("SELECT WoWordCount as len, count(WoWordCount) as cnt FROM " . $tbpref . "words where WoLgID = " . $lid . " group by WoWordCount", ' ');
	while($record = mysqli_fetch_assoc($res)){
		//if($record['cnt']>10){
		$mwlen[$record['len'] * 2] = 1;
		//}
	}
	mysqli_free_result($res);
	do_mysql_query ('delete from '.$tbpref.'textitems2 where Ti2TxID='.$id);

	foreach ($textLines as $value) { 
		
		$dummy = runsql('INSERT INTO ' . $tbpref . 'sentences (SeLgID, SeTxID, SeOrder, SeFirstPos, SeText) VALUES (' . $lid . ',' .  $id . ',' .  ($sentNumber+1) . ',' . ($lfdnr + 1) . ',' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($value . ' ', $removeSpaces)) . ')', ' ');
		$sentid = get_last_key();
		$lineWords[$sentNumber] = preg_split('/([^' . $termchar . ']+)/u', $value . ' ', null, PREG_SPLIT_DELIM_CAPTURE );
		$l = count($lineWords[$sentNumber]);
		for ($i=0; $i<$l; $i++) {
			$mw=array();
			$mw_lower=array();
			if($l>$max*2+$i)$le=$max*2+1+$i;
			else $le=$l;		
			
			if ($lineWords[$sentNumber][$i] != '') {
				if ($i % 2 == 0) {
					$isnotwort=0;
					$rest = '';
					$cnt = 2;
					for ($j=$i; $j<$le; $j++) {
						if ($lineWords[$sentNumber][$j] != '') {
							$rest .= $lineWords[$sentNumber][$j];
							if(isset($mwlen[$cnt])) {
								if(strlen ($rest) >250) break;
								$mw_lower[$cnt] = mb_strtolower($rest, 'UTF-8');
								if($cnt==2 || !($mw_lower[$cnt] == $rest))$mw[$cnt]=$rest;
								else $mw[$cnt]='';
							}
							$cnt++;
						}
					}
				} else {
					$isnotwort=1;
					//if(!$splitEachChar || remove_spaces($lineWords[$sentNumber][$i], $removeSpaces))
						$sqlarr[] = '(' . $lid . ',' .  $id . ',' . $sentid . ',' . ($lfdnr+1) . ', 0, ' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($lineWords[$sentNumber][$i], $removeSpaces)) . ', "")';
				}
				
				$lfdnr++;
				if ($isnotwort==0) {
					foreach ($mw_lower as $k=>$v) {
						
						$sqlarr[] = '(' . $lid . ',' .  $id . ',' . $sentid . ',' . $lfdnr . ', ' . $k/2 . ', ' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($mw[$k], $removeSpaces)) . ', ' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($v, $removeSpaces)) . ')';

					}
				}
			}
		}
		$sentNumber += 1;
		if ($sentNumber % 50 == 0) {
			$sqltext = 'INSERT INTO ' . $tbpref . 'temptextitems (TiLgID, TiTxID, TiSeID, TiOrder, TiWordCount, TiText, TiTextLC) VALUES ';
			$sqltext .= rtrim(implode(',', $sqlarr),',');
			$sqlarr=array();
			do_mysql_query ($sqltext);
do_mysql_query('INSERT INTO ' . $tbpref . 'textitems2 (Ti2WoID,Ti2LgID,Ti2TxID,Ti2SeID,Ti2Order,Ti2WordCount,Ti2Text) select WoID, TiLgID,TiTxID, TiSeID, TiOrder, TiWordCount, TiText from ' . $tbpref . 'temptextitems left join ' . $tbpref . 'words on TiTextLC=WoTextLC and TiLgID=WoLgID where TiWordCount<2 or WoID IS NOT NULL');
		do_mysql_query ('truncate ' . $tbpref . 'temptextitems');
		}
	}
	$sqltext = 'INSERT INTO ' . $tbpref . 'temptextitems (TiLgID, TiTxID, TiSeID, TiOrder, TiWordCount, TiText, TiTextLC) VALUES ';
	$sqltext .= rtrim(implode(',', $sqlarr),',');
	unset($sqlarr);
	do_mysql_query ($sqltext);
	do_mysql_query('INSERT INTO ' . $tbpref . 'textitems2 (Ti2WoID,Ti2LgID,Ti2TxID,Ti2SeID,Ti2Order,Ti2WordCount,Ti2Text) select WoID, TiLgID,TiTxID, TiSeID, TiOrder, TiWordCount, TiText from ' . $tbpref . 'temptextitems left join ' . $tbpref . 'words on TiTextLC=WoTextLC and TiLgID=WoLgID where TiWordCount<2 or WoID IS NOT NULL');
	do_mysql_query ('truncate ' . $tbpref . 'temptextitems');
}

// -------------------------------------------------------------

function restore_file($handle, $title) {
	global $tbpref;
	$message = "";
	$lines = 0;
	$ok = 0;
	$errors = 0;
	$drops = 0;
	$inserts = 0;
	$creates = 0;
	$start = 1;
	while (! gzeof($handle)) {
		$sql_line = trim(
			str_replace("\r","",
			str_replace("\n","",
			gzgets($handle, 99999))));
		if ($sql_line != "") {
			if($start) {
				if (strpos($sql_line,"-- lwt-backup-") === false and strpos($sql_line,"-- lwt-exp_version-backup-") === false) {
					$message = "Error: Invalid " . $title . " Restore file (possibly not created by LWT backup)";
					$errors = 1;
					break;
				}
				$start = 0;
				continue;
			}
			if ( substr($sql_line,0,3) !== '-- ' ) {
				$res = do_mysql_query(insert_prefix_in_sql($sql_line));
				$lines++;
				if ($res == FALSE) $errors++;
				else {
					$ok++;
					if (substr($sql_line,0,11) == "INSERT INTO") $inserts++;
					elseif (substr($sql_line,0,10) == "DROP TABLE") $drops++;
					elseif (substr($sql_line,0,12) == "CREATE TABLE") $creates++;
				}
				// echo $ok . " / " . tohtml(insert_prefix_in_sql($sql_line)) . "<br />";
			}
		}
	} // while (! feof($handle))
	gzclose ($handle);
	if ($errors == 0) {
		runsql('DROP TABLE IF EXISTS ' . $tbpref . 'textitems','');
		check_update_db();
		reparse_all_texts();
		optimizedb();
		get_tags($refresh = 1);
		get_texttags($refresh = 1);
		$message = "Success: " . $title . " restored - " .
		$lines . " queries - " . $ok . " successful (" . $drops . "/" . $creates . " tables dropped/created, " . $inserts . " records added), " . $errors . " failed.";
	} else {
		if ($message == "") {
			$message = "Error: " . $title . " NOT restored - " .
			$lines . " queries - " . $ok . " successful (" . $drops . "/" . $creates . " tables dropped/created, " . $inserts . " records added), " . $errors . " failed.";
		}
	}
	return $message;
}

// -------------------------------------------------------------

function set_word_count () {
	global $tbpref;
	$sqlarr = array();
	$i=0;
	$min=0;
	$max=0;
	$sql= "select WoID, WoTextLC, LgRegexpWordCharacters, LgSplitEachChar from " . $tbpref . "words, " . $tbpref . "languages where WoWordCount=0 and WoLgID = LgID order by WoID";
	$result = do_mysql_query($sql);
	while($rec = mysqli_fetch_assoc($result)){
		if ($rec['LgSplitEachChar']) {
			$textlc = preg_replace('/([^\s])/u', "$1 ", $rec['WoTextLC']);
		}
		else{
			$textlc = $rec['WoTextLC'];
		}
		$sqlarr[]= ' WHEN ' . $rec['WoID'] . ' THEN ' . preg_match_all('/([' . $rec['LgRegexpWordCharacters'] . ']+)/u',$textlc,$ma);
		if(++$i % 1000 == 0){
			if(!empty($sqlarr)){
				$max=$rec['WoID'];
				$sqltext = "UPDATE  " . $tbpref . "words SET WoWordCount  = CASE WoID";
				$sqltext .= implode(' ', $sqlarr) . ' END where WoWordCount=0 and WoID between ' . $min . ' and ' . $max;
				do_mysql_query ($sqltext);
				$min=$max;
			}
			$sqlarr = array();
		}
	}
	mysqli_free_result($result);
	if(!empty($sqlarr)){
		$sqltext = "UPDATE  " . $tbpref . "words SET WoWordCount  = CASE WoID";
		$sqltext .= implode(' ', $sqlarr) . ' END where WoWordCount=0';
		do_mysql_query ($sqltext);
	}
}

// -------------------------------------------------------------

function recreate_save_ann($textid, $oldann) {
	global $tbpref;
	$newann = create_ann($textid);
	// Get the translations from $oldann:
	$oldtrans = array();
	$olditems = preg_split('/[\n]/u', $oldann);
	foreach ($olditems as $olditem) {
		$oldvals = preg_split('/[\t]/u', $olditem);
		if ($oldvals[0] > -1) {
			$trans = '';
			if (count($oldvals) > 3) $trans = $oldvals[3];
			$oldtrans[$oldvals[0] . "\t" . $oldvals[1]] = $trans;
		}
	}
	// Reset the translations from $oldann in $newann and rebuild in $ann:
	$newitems = preg_split('/[\n]/u', $newann);
	$ann = '';
	foreach ($newitems as $newitem) {
		$newvals = preg_split('/[\t]/u', $newitem);
		if ($newvals[0] > -1) {
			$key = $newvals[0] . "\t";
			if (isset($newvals[1])) $key .= $newvals[1];
			if (array_key_exists($key, $oldtrans)) {
				$newvals[3] = $oldtrans[$key];
			}
			$item = implode("\t", $newvals);
		} else {
			$item = $newitem;
		}
		$ann .= $item . "\n";
	}
	$dummy = runsql('update ' . $tbpref . 'texts set ' .
		'TxAnnotatedText = ' . convert_string_to_sqlsyntax($ann) . ' where TxID = ' . $textid, "");
	return get_first_value("select TxAnnotatedText as value from " . $tbpref . "texts where TxID = " . $textid);
}

// -------------------------------------------------------------

function create_ann($textid) {
	global $tbpref;
	$ann = '';
	$sql = 'select CASE WHEN Ti2WordCount>0 THEN Ti2WordCount ELSE 1 END as Code, CASE WHEN CHAR_LENGTH(Ti2Text)>0 THEN Ti2Text ELSE WoText END as TiText, Ti2Order, CASE WHEN Ti2WordCount > 0 THEN 0 ELSE 1 END as TiIsNotWord, WoID, WoTranslation from (' . $tbpref . 'textitems2 left join ' . $tbpref . 'words on (Ti2WoID = WoID) and (Ti2LgID = WoLgID)) where Ti2TxID = ' . $textid . ' order by Ti2Order asc, Ti2WordCount desc';
	$savenonterm = '';
	$saveterm = '';
	$savetrans = '';
	$savewordid = '';
	$until = 0;
	$res = do_mysql_query($sql);
	while ($record = mysqli_fetch_assoc($res)) {
		$actcode = $record['Code'] + 0;
		$order = $record['Ti2Order'] + 0;
		if ( $order <= $until ) {
			continue;
		}
		if ( $order > $until ) {
			$ann = $ann . process_term($savenonterm, $saveterm, $savetrans, $savewordid, $order);
			$savenonterm = '';
			$saveterm = '';
			$savetrans = '';
			$savewordid = '';
			$until = $order;
		}
		if ($record['TiIsNotWord'] != 0) {
			$savenonterm = $savenonterm . $record['TiText'];
		}
		else {
			$until = $order + 2 * ($actcode-1);
			$saveterm = $record['TiText'];
			$savetrans = '';
			if(isset($record['WoID'])) {
				$savetrans = $record['WoTranslation'];
				$savewordid = $record['WoID'];
			}
		}
	} // while
	mysqli_free_result($res);
	$ann .= process_term($savenonterm, $saveterm, $savetrans, $savewordid, $order);
	return $ann;
}

// -------------------------------------------------------------
function str_replace_first ($needle, $replace, $haystack) {
	if ($needle === '')
		return $haystack;
	$pos = strpos($haystack,$needle);
	if ($pos !== false) {
    return substr_replace($haystack,$replace,$pos,strlen($needle));
	}
	return $haystack;
}

// -------------------------------------------------------------

function annotation_to_json ($ann) {
	if ($ann == '') return "{}";
	$arr = array();
	$items = preg_split('/[\n]/u', $ann);
	foreach ($items as $item) {
		$vals = preg_split('/[\t]/u', $item);
		if (count($vals) > 3 && $vals[0] >= 0 && $vals[2] > 0) {
			$arr[$vals[0]-1] = array($vals[1],$vals[2],$vals[3]);
		}
	}
	return json_encode($arr);
}

// -------------------------------------------------------------

function LWTTableCheck () {
	if (mysqli_num_rows(do_mysql_query("SHOW TABLES LIKE '\\_lwtgeneral'")) == 0) {
		runsql("CREATE TABLE IF NOT EXISTS _lwtgeneral ( LWTKey varchar(40) NOT NULL, LWTValue varchar(40) DEFAULT NULL, PRIMARY KEY (LWTKey) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
		if (mysqli_num_rows(do_mysql_query("SHOW TABLES LIKE '\\_lwtgeneral'")) == 0) my_die("Unable to create table '_lwtgeneral'!");
	}
}

// -------------------------------------------------------------

function LWTTableSet ($key, $val) {
	LWTTableCheck ();
	runsql("INSERT INTO _lwtgeneral (LWTKey, LWTValue) VALUES (" . convert_string_to_sqlsyntax($key) . ", " . convert_string_to_sqlsyntax($val) . ") ON DUPLICATE KEY UPDATE LWTValue = " . convert_string_to_sqlsyntax($val),'');
}

// -------------------------------------------------------------

function LWTTableGet ($key) {
	LWTTableCheck ();
	return get_first_value("SELECT LWTValue as value FROM _lwtgeneral WHERE LWTKey = " . convert_string_to_sqlsyntax($key));
}

// -------------------------------------------------------------

function insert_prefix_in_sql ($sql_line) {
	global $tbpref;
	//                                 123456789012345678901
	if     (substr($sql_line,0,12) == "INSERT INTO ")
		return substr($sql_line,0,12) . $tbpref . substr($sql_line,12);
	elseif (substr($sql_line,0,21) == "DROP TABLE IF EXISTS ")
		return substr($sql_line,0,21) . $tbpref . substr($sql_line,21);
	elseif (substr($sql_line,0,14) == "CREATE TABLE `")
		return substr($sql_line,0,14) . $tbpref . substr($sql_line,14);
	elseif (substr($sql_line,0,13) == "CREATE TABLE ")
		return substr($sql_line,0,13) . $tbpref . substr($sql_line,13);
	else
		return $sql_line;
}

// -------------------------------------------------------------

function create_save_ann($textid) {
	global $tbpref;
	$ann = create_ann($textid);
	$dummy = runsql('update ' . $tbpref . 'texts set ' .
		'TxAnnotatedText = ' . convert_string_to_sqlsyntax($ann) . ' where TxID = ' . $textid, "");
	return get_first_value("select TxAnnotatedText as value from " . $tbpref . "texts where TxID = " . $textid);
}

// -------------------------------------------------------------

function process_term($nonterm, $term, $trans, $wordid, $line) {
	$r = '';
	if ($nonterm != '') $r = $r . "-1\t" . $nonterm . "\n";
	if ($term != '') $r = $r . $line . "\t" . $term . "\t" . trim($wordid) . "\t" . get_first_translation($trans) . "\n";
	return $r;
}

// -------------------------------------------------------------

function get_first_translation($trans) {
	$arr = preg_split('/[' . get_sepas()  . ']/u', $trans);
	if (count($arr) < 1) return '';
	$r = trim($arr[0]);
	if ($r == '*') $r ="";
	return $r;
}

// -------------------------------------------------------------

function get_annotation_link($textid) {
	global $tbpref;
	if ( get_first_value('select length(TxAnnotatedText) as value from ' . $tbpref . 'texts where TxID=' . $textid) > 0) 
	return ' &nbsp;<a href="print_impr_text.php?text=' . $textid . '" target="_top"><img src="icn/tick.png" title="Annotated Text" alt="Annotated Text" /></a>';
	else 
		return '';
}

// -------------------------------------------------------------

function trim_value(&$value) 
{ 
	$value = trim($value); 
}

// -------------------------------------------------------------

function makeAudioPlayer($audio,$offset=0) {
	if ($audio != '') {
		$repeatMode = getSettingZeroOrOne('currentplayerrepeatmode',0);
?>
<link type="text/css" href="<?php print_file_path('css/jplayer.css');?>" rel="stylesheet" />
<script type="text/javascript" src="js/jquery.jplayer.min.js"></script>
<table class="width99pc" cellspacing="0" cellpadding="3">
<tr>
<td class="width45pc">&nbsp;</td>
<td class="center borderleft" style="padding-left:10px;">
<span id="do-single" class="click<?php echo ($repeatMode ? '' : ' hide'); ?>" style="color:#09F;font-weight: bold;" title="Toggle Repeat (Now ON)">↻</span><span id="do-repeat" class="click<?php echo ($repeatMode ? ' hide' : ''); ?>" style="color:grey;font-weight: bold;" title="Toggle Repeat (Now OFF)">↻</span><div id="playbackrateContainer" style="font-size: 80%;position:relative;-webkit-touch-callout: none;-webkit-user-select: none;-khtml-user-select: none;-moz-user-select: none;-ms-user-select: none;user-select: none;"></div>
</td>
<td class="center bordermiddle">&nbsp;</td>
<td class="bordermiddle">
<div id="jquery_jplayer_1" class="jp-jplayer">
</div>
<div class="jp-audio-container">
	<div id="jp_container_1" class="jp-audio">
		<div class="jp-type-single">
			<div id="jp_interface_1" class="jp-interface">
				<ul class="jp-controls">
					<li><a href="#" class="jp-play" tabindex="1">play</a></li>
					<li><a href="#" class="jp-pause" tabindex="1">pause</a></li>
					<li><a href="#" class="jp-stop" tabindex="1">stop</a></li>
					<li><a href="#" class="jp-mute" tabindex="1">mute</a></li>
					<li><a href="#" class="jp-unmute" tabindex="1">unmute</a></li>
				</ul>
				<div class="jp-progress-container">
					<div class="jp-progress">
						<div class="jp-seek-bar">
							<div class="jp-play-bar">
							</div>
						</div>
					</div>
				</div>
				<div class="jp-volume-bar-container">
					<div class="jp-volume-bar">
						<div class="jp-volume-bar-value">
						</div>
					</div>
				</div>
				<div class="jp-current-time">
				</div>
				<div class="jp-duration">
				</div>

			</div>
			<div id="jp_playlist_1" class="jp-playlist">
			</div>
		</div>
	</div>
</div>
</td>
<td class="center bordermiddle">&nbsp;</td>
<td class="center borderright" style="padding-right:10px;">
<?php
$currentplayerseconds = getSetting('currentplayerseconds');
if($currentplayerseconds == '') $currentplayerseconds = 5;
?>
<select id="backtime" name="backtime" onchange="{do_ajax_save_setting('currentplayerseconds',document.getElementById('backtime').options[document.getElementById('backtime').selectedIndex].value);}"><?php echo get_seconds_selectoptions($currentplayerseconds); ?></select><br />
<span id="backbutt" class="click" title="Rewind n seconds">⇤</span>&nbsp;&nbsp;<span id="forwbutt" class="click" title="Forward n seconds">⇥</span>
<span id="playTime" class="hide"></span>
</td>
<td class="width45pc">&nbsp;</td>
</tr>
<script type="text/javascript">
//<![CDATA[

function new_pos(p) {
	$("#jquery_jplayer_1").jPlayer("playHead", p);
}

function click_single() {
	$("#jquery_jplayer_1").unbind($.jPlayer.event.ended + ".jp-repeat");
	$("#do-single").addClass('hide');
	$("#do-repeat").removeClass('hide');
	do_ajax_save_setting('currentplayerrepeatmode','0');
	return false;
}

function click_repeat() {
	$("#jquery_jplayer_1").bind($.jPlayer.event.ended + ".jp-repeat", function(event) { 
		$(this).jPlayer("play"); 
	});
	$("#do-repeat").addClass('hide');
	$("#do-single").removeClass('hide');
	do_ajax_save_setting('currentplayerrepeatmode','1');
	return false;
}

function click_back() {
	var t = parseInt($("#playTime").text(),10);
	var b = parseInt($("#backtime").val(),10);
	var nt = t - b;
	var st = 'pause';
	if (nt < 0) nt = 0;
	if(!$('#jquery_jplayer_1').data().jPlayer.status.paused)st = 'play';
	$("#jquery_jplayer_1").jPlayer(st, nt);
}

function click_forw() {
	var t = parseInt($("#playTime").text(),10);
	var b = parseInt($("#backtime").val(),10);
	var nt = t + b;
	var st = 'pause';
	if(!$('#jquery_jplayer_1').data().jPlayer.status.paused)st = 'play';
	$("#jquery_jplayer_1").jPlayer(st, nt);
}

function click_slower() {
	val=parseFloat($("#pbvalue").text()) - 0.1;
	if(val>=0.5){
		$("#pbvalue").text(val.toFixed(1)).css({'color': '#BBB'}).animate({color: '#888'},150,function() {});
		$("#jquery_jplayer_1").jPlayer("playbackRate",val);
	}
}

function click_faster() {
	val=parseFloat($("#pbvalue").text()) + 0.1;
	if(val<=4.0){
		$("#pbvalue").text(val.toFixed(1)).css({'color': '#BBB'}).animate({color: '#888'},150,function() {});
		$("#jquery_jplayer_1").jPlayer("playbackRate",val);
	}
}

$(document).ready(function(){
  $("#jquery_jplayer_1").jPlayer({
    ready: function () {
      $(this).jPlayer("setMedia", { 
<?php 
	$audio = trim($audio);
	if (strcasecmp(substr($audio,-4), '.mp3') == 0) { 
  	echo 'mp3: ' . prepare_textdata_js(encodeURI($audio)); 
  } elseif (strcasecmp(substr($audio,-4), '.ogg') == 0) { 
  	echo 'oga: ' . prepare_textdata_js(encodeURI($audio))  . ", " . 
  			 'mp3: ' . prepare_textdata_js(encodeURI($audio)); 
  } elseif (strcasecmp(substr($audio,-4), '.wav') == 0) {
  	echo 'wav: ' . prepare_textdata_js(encodeURI($audio))  . ", " . 
  			 'mp3: ' . prepare_textdata_js(encodeURI($audio)); 
  } else {
  	echo 'mp3: ' . prepare_textdata_js(encodeURI($audio)); 
  }
?> }).jPlayer("pause",<?php echo $offset; ?>);
      if($('#jquery_jplayer_1').data().jPlayer.status.playbackRateEnabled)$("#playbackrateContainer").css("margin-top",".2em").html('<span id="pbSlower" style="position:absolute;top: 0; left: 0; bottom: 0; right: 50%;" title="Slower" onclick="click_slower();">&nbsp;</span><span id="pbFaster" style="position:absolute;top: 0; left: 50%; bottom: 0; right: 0;" title="Faster" onclick="click_faster();">&nbsp;</span><span class="ui-widget ui-state-default ui-corner-all" style="padding-left: 0.2em;padding-right: 0.2em;color:grey"><span id="playbackSlower" style="padding-right: 0.15em;">≪</span><span id="pbvalue">1.0</span><span id="playbackFaster" style="padding-left: 0.15em;">≫</span></span>').css("cursor","pointer");
    },
    swfPath: "js",
  });

  $("#jquery_jplayer_1").bind($.jPlayer.event.timeupdate, function(event) { 
  	$("#playTime").text(Math.floor(event.jPlayer.status.currentTime));
	});

  $("#backbutt").click(click_back).button();
  $("#forwbutt").click(click_forw).button();
  $("#do-single").click(click_single).button().css('transform','rotate(270deg)');
  $("#do-repeat").click(click_repeat).button().css('transform','rotate(270deg)');
  $(".ui-button-text").css('padding','.2em .4em');

  <?php echo ($repeatMode ? "click_repeat();\n" : ''); ?>
});
//]]>
</script>
<?php
	} // if (isset($audio))
}

// -------------------------------------------------------------

function make_score_random_insert_update($type) {  // $type='iv'/'id'/'u'
	if ($type == 'iv') {
		return ' WoTodayScore, WoTomorrowScore, WoRandom ';
	} elseif ($type == 'id') {
		return ' ' . getsqlscoreformula(2) . ', ' . getsqlscoreformula(3) . ', RAND() ';
	} elseif ($type == 'u') {
		return ' WoTodayScore = ' . getsqlscoreformula(2) . ', WoTomorrowScore = ' . getsqlscoreformula(3) . ', WoRandom = RAND() ';
	} else {
		return '';
	}
}

// -------------------------------------------------------------

function check_update_db() {
	global $debug, $tbpref;
	$tables = array();
	
	$res = do_mysql_query(str_replace('_',"\\_","SHOW TABLES LIKE " . convert_string_to_sqlsyntax_nonull($tbpref . '%')));
  while ($row = mysqli_fetch_row($res))
  	$tables[] = $row[0];
	mysqli_free_result($res);
	
	$count = 0;  // counter for cache rebuild
	
	// Rebuild Tables if missing (current versions!)
	
	if (in_array($tbpref . 'archivedtexts', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding archivedtexts</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "archivedtexts ( AtID smallint(5) unsigned NOT NULL AUTO_INCREMENT, AtLgID tinyint(3) unsigned NOT NULL, AtTitle varchar(200) NOT NULL, AtText text NOT NULL, AtAnnotatedText longtext NOT NULL, AtAudioURI varchar(200) DEFAULT NULL, AtSourceURI varchar(1000) DEFAULT NULL, PRIMARY KEY (AtID), KEY AtLgID (AtLgID), KEY AtLgIDSourceURI (AtSourceURI(20),AtLgID) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'languages', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding languages</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "languages ( LgID tinyint(3) unsigned NOT NULL AUTO_INCREMENT, LgName varchar(40) NOT NULL, LgDict1URI varchar(200) NOT NULL, LgDict2URI varchar(200) DEFAULT NULL, LgGoogleTranslateURI varchar(200) DEFAULT NULL, LgExportTemplate varchar(1000) DEFAULT NULL, LgTextSize smallint(5) unsigned NOT NULL DEFAULT '100', LgCharacterSubstitutions varchar(500) NOT NULL, LgRegexpSplitSentences varchar(500) NOT NULL, LgExceptionsSplitSentences varchar(500) NOT NULL, LgRegexpWordCharacters varchar(500) NOT NULL, LgRemoveSpaces tinyint(1) unsigned NOT NULL DEFAULT '0', LgSplitEachChar tinyint(1) unsigned NOT NULL DEFAULT '0', LgRightToLeft tinyint(1) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (LgID), UNIQUE KEY LgName (LgName) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'sentences', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding sentences</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "sentences ( SeID mediumint(8) unsigned NOT NULL AUTO_INCREMENT, SeLgID tinyint(3) unsigned NOT NULL, SeTxID smallint(5) unsigned NOT NULL, SeOrder smallint(5) unsigned NOT NULL, SeText text, SeFirstPos smallint(5) unsigned NOT NULL, PRIMARY KEY (SeID), KEY SeLgID (SeLgID), KEY SeTxID (SeTxID), KEY SeOrder (SeOrder) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
		$count++;
	}
	
	if (in_array($tbpref . 'settings', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding settings</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "settings ( StKey varchar(40) NOT NULL, StValue varchar(40) DEFAULT NULL, PRIMARY KEY (StKey) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'textitems2', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding textitems2</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "textitems2 ( Ti2WoID mediumint(8) unsigned NOT NULL, Ti2LgID tinyint(3) unsigned NOT NULL, Ti2TxID smallint(5) unsigned NOT NULL, Ti2SeID mediumint(8) unsigned NOT NULL, Ti2Order smallint(5) unsigned NOT NULL, Ti2WordCount tinyint(3) unsigned NOT NULL, Ti2Text varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, PRIMARY KEY (Ti2TxID,Ti2Order,Ti2WordCount), KEY Ti2WoID (Ti2WoID)) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
		if (in_array($tbpref . 'textitems', $tables) != FALSE) {
			runsql('INSERT INTO ' . $tbpref . 'textitems2 (Ti2WoID,Ti2LgID,Ti2TxID,Ti2SeID,Ti2Order,Ti2WordCount,Ti2Text) select IFNULL(WoID,0), TiLgID,TiTxID, TiSeID, TiOrder, CASE WHEN TiIsNotWord = 1 THEN 0 ELSE TiWordCount END as WordCount, CASE WHEN STRCMP( TiText COLLATE utf8_bin ,TiTextLC)!=0 OR TiWordCount = 1 THEN TiText ELSE "" END as Text from ' . $tbpref . 'textitems left join ' . $tbpref . 'words on TiTextLC=WoTextLC and TiLgID=WoLgID where TiWordCount<2 or WoID IS NOT NULL','');
			runsql ('TRUNCATE ' . $tbpref . 'textitems','');
		}
		else $count++;
	}


	if (in_array($tbpref . 'temptextitems', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding temptextitems</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "temptextitems ( TiLgID tinyint(3) unsigned NOT NULL, TiTxID smallint(5) unsigned NOT NULL, TiSeID mediumint(8) unsigned NOT NULL, TiOrder smallint(5) unsigned NOT NULL, TiWordCount tinyint(3) unsigned NOT NULL, TiText varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, TiTextLC varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL) ENGINE=MEMORY DEFAULT CHARSET=utf8",'');
	}

	if (in_array($tbpref . 'tempwords', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding tempwords</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "tempwords (WoText varchar(250) DEFAULT NULL, WoTextLC varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, WoTranslation varchar(500) NOT NULL DEFAULT '*', WoRomanization varchar(100) DEFAULT NULL, WoSentence varchar(1000) DEFAULT NULL, WoTaglist varchar(255) DEFAULT NULL, PRIMARY KEY(WoTextLC) ) ENGINE=MEMORY DEFAULT CHARSET=utf8",'');
	}

	if (in_array($tbpref . 'texts', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding texts</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "texts ( TxID smallint(5) unsigned NOT NULL AUTO_INCREMENT, TxLgID tinyint(3) unsigned NOT NULL, TxTitle varchar(200) NOT NULL, TxText text NOT NULL, TxAnnotatedText longtext NOT NULL, TxAudioURI varchar(200) DEFAULT NULL, TxSourceURI varchar(1000) DEFAULT NULL, TxPosition smallint(5) DEFAULT 0, TxAudioPosition float DEFAULT 0, PRIMARY KEY (TxID), KEY TxLgID (TxLgID), KEY TxLgIDSourceURI (TxSourceURI(20),TxLgID) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'words', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding words</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "words ( WoID mediumint(8) unsigned NOT NULL AUTO_INCREMENT, WoLgID tinyint(3) unsigned NOT NULL, WoText varchar(250) NOT NULL, WoTextLC varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, WoStatus tinyint(4) NOT NULL, WoTranslation varchar(500) NOT NULL DEFAULT '*', WoRomanization varchar(100) DEFAULT NULL, WoSentence varchar(1000) DEFAULT NULL, WoWordCount tinyint(3) unsigned NOT NULL DEFAULT 0, WoCreated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, WoStatusChanged timestamp NOT NULL DEFAULT '0000-00-00 00:00:00', WoTodayScore double NOT NULL DEFAULT '0', WoTomorrowScore double NOT NULL DEFAULT '0', WoRandom double NOT NULL DEFAULT '0', PRIMARY KEY (WoID), UNIQUE KEY WoTextLCLgID (WoTextLC,WoLgID), KEY WoLgID (WoLgID), KEY WoStatus (WoStatus), KEY WoTranslation (WoTranslation(20)), KEY WoCreated (WoCreated), KEY WoStatusChanged (WoStatusChanged), KEY WoWordCount(WoWordCount), KEY WoTodayScore (WoTodayScore), KEY WoTomorrowScore (WoTomorrowScore), KEY WoRandom (WoRandom) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'tags', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding tags</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "tags ( TgID smallint(5) unsigned NOT NULL AUTO_INCREMENT, TgText varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, TgComment varchar(200) NOT NULL DEFAULT '', PRIMARY KEY (TgID), UNIQUE KEY TgText (TgText) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'wordtags', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding wordtags</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "wordtags ( WtWoID mediumint(8) unsigned NOT NULL, WtTgID smallint(5) unsigned NOT NULL, PRIMARY KEY (WtWoID,WtTgID), KEY WtTgID (WtTgID), KEY WtWoID (WtWoID) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'tags2', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding tags2</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "tags2 ( T2ID smallint(5) unsigned NOT NULL AUTO_INCREMENT, T2Text varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, T2Comment varchar(200) NOT NULL DEFAULT '', PRIMARY KEY (T2ID), UNIQUE KEY T2Text (T2Text) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'texttags', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding texttags</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "texttags ( TtTxID smallint(5) unsigned NOT NULL, TtT2ID smallint(5) unsigned NOT NULL, PRIMARY KEY (TtTxID,TtT2ID), KEY TtTxID (TtTxID), KEY TtT2ID (TtT2ID) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'newsfeeds', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding newsfeeds</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "newsfeeds (NfID tinyint(3) unsigned NOT NULL AUTO_INCREMENT,NfLgID tinyint(3) unsigned NOT NULL,NfName varchar(40) NOT NULL,NfSourceURI varchar(200) NOT NULL,NfArticleSectionTags text NOT NULL,NfFilterTags text NOT NULL,NfUpdate int(12) unsigned NOT NULL,NfOptions varchar(200) NOT NULL,PRIMARY KEY (NfID), KEY NfLgID (NfLgID), KEY NfUpdate (NfUpdate)) ENGINE=MyISAM  DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'feedlinks', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding feedlinks</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "feedlinks (FlID mediumint(8) unsigned NOT NULL AUTO_INCREMENT,FlTitle varchar(200) NOT NULL,FlLink varchar(400) NOT NULL,FlDescription text NOT NULL,FlDate datetime NOT NULL,FlAudio varchar(200) NOT NULL,FlText longtext NOT NULL,FlNfID tinyint(3) unsigned NOT NULL,PRIMARY KEY (FlID), KEY FlLink (FlLink), KEY FlDate (FlDate), UNIQUE KEY FlTitle (FlNfID,FlTitle)) ENGINE=MyISAM  DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'archtexttags', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding archtexttags</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "archtexttags ( AgAtID smallint(5) unsigned NOT NULL, AgT2ID smallint(5) unsigned NOT NULL, PRIMARY KEY (AgAtID,AgT2ID), KEY AgAtID (AgAtID), KEY AgT2ID (AgT2ID) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'images', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding images</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "images ( ImID smallint(5) NOT NULL AUTO_INCREMENT, ImWoID mediumint(8) NOT NULL, PRIMARY KEY (ImID),  UNIQUE KEY ImWoID (ImWoID) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if ($count > 0) {		
		// Rebuild Text Cache if cache tables new
		if ($debug) echo '<p>DEBUG: rebuilding cache tables</p>';
		reparse_all_texts();
	}
	
	// DB Version
	
	$currversion = get_version_number();
	
	$res = do_mysql_query ("select StValue as value from " . $tbpref . "settings where StKey = 'dbversion'");
	if (((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_errno($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false)) != 0) my_die('There is something wrong with your database ' . $dbname . '. Please reinstall.');
	$record = mysqli_fetch_assoc($res);
	if ($record) {
		$dbversion = $record["value"];
	} else {
		$dbversion = 'v001000000';
	}
	mysqli_free_result($res);
	
	// Do DB Updates if tables seem to be old versions
	
	if ( $dbversion < $currversion ) {
		if ($debug) echo "<p>DEBUG: do DB updates: $dbversion --&gt; $currversion</p>";
		runsql("ALTER TABLE " . $tbpref . "words ADD WoTodayScore DOUBLE NOT NULL DEFAULT 0, ADD WoTomorrowScore DOUBLE NOT NULL DEFAULT 0, ADD WoRandom DOUBLE NOT NULL DEFAULT 0", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "words ADD WoWordCount tinyint(3) unsigned NOT NULL DEFAULT 0 AFTER WoSentence", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "words ADD INDEX WoTodayScore (WoTodayScore), ADD INDEX WoTomorrowScore (WoTomorrowScore), ADD INDEX WoRandom (WoRandom)", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "languages ADD LgRightToLeft tinyint(1) UNSIGNED NOT NULL DEFAULT  0", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "texts ADD TxAnnotatedText LONGTEXT NOT NULL AFTER TxText", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "archivedtexts ADD AtAnnotatedText LONGTEXT NOT NULL AFTER AtText", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "tags CHANGE TgComment TgComment VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "tags2 CHANGE T2Comment T2Comment VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "languages CHANGE LgGoogleTTSURI LgExportTemplate VARCHAR(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "texts ADD TxSourceURI VARCHAR(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "archivedtexts ADD AtSourceURI VARCHAR(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "texts ADD TxPosition smallint(5) NOT NULL DEFAULT  0", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "texts ADD TxAudioPosition float NOT NULL DEFAULT  0", '', $sqlerrdie = FALSE);
		
			runsql('ALTER TABLE `' . $tbpref . 'archivedtexts` MODIFY COLUMN `AtLgID` tinyint(3) unsigned NOT NULL, MODIFY COLUMN `AtID` smallint(5) unsigned NOT NULL, ADD INDEX AtLgIDSourceURI (AtSourceURI(20),AtLgID)','', $sqlerrdie = FALSE);
			runsql('ALTER TABLE `' . $tbpref . 'languages` MODIFY COLUMN `LgID` tinyint(3) unsigned NOT NULL AUTO_INCREMENT, MODIFY COLUMN `LgRemoveSpaces` tinyint(1) unsigned NOT NULL, MODIFY COLUMN `LgSplitEachChar` tinyint(1) unsigned NOT NULL, MODIFY COLUMN `LgRightToLeft` tinyint(1) unsigned NOT NULL','', $sqlerrdie = FALSE);
			runsql('ALTER TABLE `' . $tbpref . 'sentences`  ADD SeFirstPos smallint(5) NOT NULL, MODIFY COLUMN `SeID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT, MODIFY COLUMN `SeLgID` tinyint(3) unsigned NOT NULL, MODIFY COLUMN `SeTxID` smallint(5) unsigned NOT NULL, MODIFY COLUMN `SeOrder` smallint(5) unsigned NOT NULL','', $sqlerrdie = FALSE);
			runsql('ALTER TABLE `' . $tbpref . 'texts` MODIFY COLUMN `TxID` smallint(5) unsigned NOT NULL AUTO_INCREMENT, MODIFY COLUMN `TxLgID` tinyint(3) unsigned NOT NULL, ADD INDEX TxLgIDSourceURI (TxSourceURI(20),TxLgID)','', $sqlerrdie = FALSE);
			runsql('ALTER TABLE `' . $tbpref . 'words` MODIFY COLUMN `WoID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT, MODIFY COLUMN `WoLgID` tinyint(3) unsigned NOT NULL, MODIFY COLUMN `WoStatus` tinyint(4) NOT NULL','', $sqlerrdie = FALSE);		
			runsql('ALTER TABLE `' . $tbpref . 'words` DROP INDEX WoTextLC','', $sqlerrdie = FALSE);
			runsql('ALTER TABLE `' . $tbpref . 'words` DROP INDEX WoLgIDTextLC, ADD UNIQUE INDEX WoTextLCLgID (WoTextLC,WoLgID)','', $sqlerrdie = FALSE);
			runsql('ALTER TABLE `' . $tbpref . 'words` ADD INDEX WoWordCount (WoWordCount)','', $sqlerrdie = FALSE);
			runsql('ALTER TABLE `' . $tbpref . 'archtexttags` MODIFY COLUMN `AgAtID` smallint(5) unsigned NOT NULL, MODIFY COLUMN `AgT2ID` smallint(5) unsigned NOT NULL','', $sqlerrdie = FALSE);
			runsql('ALTER TABLE `' . $tbpref . 'tags` MODIFY COLUMN `TgID` smallint(5) unsigned NOT NULL AUTO_INCREMENT','', $sqlerrdie = FALSE);
			runsql('ALTER TABLE `' . $tbpref . 'tags2` MODIFY COLUMN `T2ID` smallint(5) unsigned NOT NULL AUTO_INCREMENT','', $sqlerrdie = FALSE);
			runsql('ALTER TABLE `' . $tbpref . 'wordtags` MODIFY COLUMN `WtTgID` smallint(5) unsigned NOT NULL AUTO_INCREMENT','', $sqlerrdie = FALSE);
			runsql('ALTER TABLE `' . $tbpref . 'texttags` MODIFY COLUMN `TtTxID` smallint(5) unsigned NOT NULL, MODIFY COLUMN `TtT2ID` smallint(5) unsigned NOT NULL','', $sqlerrdie = FALSE);
		if ($debug) echo '<p>DEBUG: rebuilding tts</p>';
		runsql("CREATE TABLE IF NOT EXISTS tts ( TtsID mediumint(8) unsigned NOT NULL AUTO_INCREMENT, TtsTxt varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, TtsLc varchar(8) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, PRIMARY KEY (TtsID), UNIQUE KEY TtsTxtLC (TtsTxt,TtsLc) ) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=1",'');
		
		// set to current.
		saveSetting('dbversion',$currversion);
		saveSetting('lastscorecalc','');  // do next section, too
	}

	// Do Scoring once per day, clean Word/Texttags, and optimize db
	$lastscorecalc = getSetting('lastscorecalc');
	$today = date('Y-m-d');
	if ($lastscorecalc != $today) {
		if ($debug) echo '<p>DEBUG: Doing score recalc. Today: ' . $today . ' / Last: ' . $lastscorecalc . '</p>';
		runsql("UPDATE " . $tbpref . "words SET " . make_score_random_insert_update('u') ." where WoTodayScore>=-100 and WoStatus<98",'');
		runsql("DELETE " . $tbpref . "wordtags FROM (" . $tbpref . "wordtags LEFT JOIN " . $tbpref . "tags on WtTgID = TgID) WHERE TgID IS NULL",'');
		runsql("DELETE " . $tbpref . "wordtags FROM (" . $tbpref . "wordtags LEFT JOIN " . $tbpref . "words on WtWoID = WoID) WHERE WoID IS NULL",'');
		runsql("DELETE " . $tbpref . "texttags FROM (" . $tbpref . "texttags LEFT JOIN " . $tbpref . "tags2 on TtT2ID = T2ID) WHERE T2ID IS NULL",'');
		runsql("DELETE " . $tbpref . "texttags FROM (" . $tbpref . "texttags LEFT JOIN " . $tbpref . "texts on TtTxID = TxID) WHERE TxID IS NULL",'');
		runsql("DELETE " . $tbpref . "archtexttags FROM (" . $tbpref . "archtexttags LEFT JOIN " . $tbpref . "tags2 on AgT2ID = T2ID) WHERE T2ID IS NULL",'');
		runsql("DELETE " . $tbpref . "archtexttags FROM (" . $tbpref . "archtexttags LEFT JOIN " . $tbpref . "archivedtexts on AgAtID = AtID) WHERE AtID IS NULL",'');
		optimizedb();
		saveSetting('lastscorecalc',$today);
	}
}

// -------------------------------------------------------------

//////////////////  S T A R T  /////////////////////////////////

// Start Timer

if (!empty($dspltime)) get_execution_time();

// Connection, @ suppresses messages from function

$err = @($GLOBALS["___mysqli_ston"] = mysqli_connect($server, $userid, $passwd));
if ($err == FALSE) my_die('DB connect error (MySQL not running or connection parameters are wrong; start MySQL and/or correct file "connect.inc.php"). Please read the documentation: http://lwt.sf.net');

@mysqli_query($GLOBALS["___mysqli_ston"], "SET NAMES 'utf8'");

// @mysqli_query($GLOBALS["___mysqli_ston"], "SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
@mysqli_query($GLOBALS["___mysqli_ston"], "SET SESSION sql_mode = ''");

$err = @mysqli_select_db ($GLOBALS["___mysqli_ston"], $dbname);
if ($err == FALSE && (mysqli_errno($GLOBALS["___mysqli_ston"]) == 1049)){
	runsql("CREATE DATABASE `" . $dbname . "` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci",'');
	$err = @mysqli_select_db ($GLOBALS["___mysqli_ston"], $dbname);
	if ($err == FALSE) my_die('DB select error (Cannot find database: "'. $dbname . '" or connection parameter $dbname is wrong; please correct file: "connect.inc.php"). Please read the documentation: http://lwt.sf.net');
}

// *** GLOBAL VARIABLES ***
// $tbpref = Current Table Prefix
// $fixed_tbpref = Table Prefix is fixed, no changes possible
// *** GLOBAL VARIABLES ***

// Is $tbpref set in connect.inc.php? Take it and $fixed_tbpref=1.
// If not: $fixed_tbpref=0. Is it set in table "_lwtgeneral"? Take it.
// If not: Use $tbpref = '' (no prefix, old/standard behaviour).

if (! isset($tbpref)) {
	$fixed_tbpref = 0;
	$p = LWTTableGet("current_table_prefix");
	if (isset($p)) 
		$tbpref = $p;
	else {
		$tbpref = '';
	}
} 
else
	$fixed_tbpref = 1;

$len_tbpref = strlen($tbpref); 
if ($len_tbpref > 0) {
	if ($len_tbpref > 20) my_die('Table prefix/set "' . $tbpref . '" longer than 20 digits or characters. Please fix in "connect.inc.php".');
	for ($i=0; $i < $len_tbpref; $i++) 
		if (strpos("_0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", substr($tbpref,$i,1)) === FALSE) my_die('Table prefix/set "' . $tbpref . '" contains characters or digits other than 0-9, a-z, A-Z or _. Please fix in "connect.inc.php".'); 
}

if (! $fixed_tbpref) 
	LWTTableSet ("current_table_prefix", $tbpref);

// *******************************************************************
// IF PREFIX IS NOT '', THEN ADD A '_', TO ENSURE NO IDENTICAL NAMES
if ( $tbpref !== '') $tbpref .= "_";
// *******************************************************************

// check/update db
check_update_db();

// -------------------------------------------------------------

?>
