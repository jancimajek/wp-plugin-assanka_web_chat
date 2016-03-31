<?php

/**
 *
 * Need to prepare segments separately before outputting because there cannot be any line breaks in the content
 * when it is output.  This is to beat the Wordpress auto-paragrapher.
 */


$initials = "";
if (in_array($messagetype, array('normal', 'blockquote')) and !empty($authorinitials)) {
	$pubdate->setTimezone(new DateTimeZone('Europe/London'));
	$initials = "<span class='messageheader par color-".$authorcolour."' title='".$pubdate->format('H:i')."'>".$authorinitials."</span>";
}

$message = "<div class='messagebody'>".$messagebody."</div>";

?><div class='<?php echo join(' ', $classes);?>' data-timestamp='<?php echo $pubdate->format('U');?>' data-mid='<?php echo $messageid;?>' data-rawmessage='<?php echo $htmlencodedrawmessage;?>' id='webchat-msg-<?php echo $messageid;?>'><?php echo $initials.$message;?></div>

