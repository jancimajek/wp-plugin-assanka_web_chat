<div class='hentry <?php echo join(' ', $classes);?>' id='webchat-msg-<?php echo $messageid;?>' <?php echo $strdataatributes;?> >
	<a name='<?php echo md5('message'.$messageid); ?>' id='<?php echo md5('message'.$messageid); ?>'></a>
	<div class='messageheader'>
	<?php if ($keytext) echo '<h3>'.$keytext.'</h3>'; ?>
		<?php if ($show_message_authornames == 1 && $show_message_headshots == 1 && !empty($headshot)): ?><div class="headshot-image " style="background: url('<?php echo $headshot; ?>') center center no-repeat;"></div><?php endif; ?>
		<span class="pubdate entry-meta entry-date"><a href="#<?php echo md5('message'.$messageid); ?>"><?php echo $pubdate->format('g:ia'); ?><a "></a></span>
		<?php if ($show_message_authornames == 1 && !empty($authordisplayname)): ?><span class="webchat-byline color-<?php echo $authorcolour; ?>" title="<?php echo $authordisplayname; ?>"><?php echo $authordisplayname; ?></span><?php endif; ?>
	</div>
	<div class="messagebody">
		<?php echo $messagebody; ?>
	</div>
</div>
