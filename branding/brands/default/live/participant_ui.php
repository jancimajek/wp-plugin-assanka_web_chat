<?php global $assanka_webchat; ?>

<div class="participantbrowserwarning">
	<p><strong>Unsupported browser warning</strong></p><p>The browser you are using is not fully supported.</p>
	<p>Some participant features are not available in Internet Explorer; please use Mozilla Firefox or Google Chrome instead.</p>
</div>

<div class="participantui">
	<form accept-charset="utf-8">
		<div class='options'>
			<div class='session-options'>
				<div id="start-session-container" class="link-container" <?php echo ($assanka_webchat->currentPostIsComingsoon() ? '' : 'style="display:none;"'); ?> >
					<a href="#" id="link-start-session">Start session now</a>
				</div>
				<div id="end-session-container" class="link-container" <?php echo ($assanka_webchat->currentPostIsComingsoon() ? 'style="display:none;"' : ''); ?> >
					<a href="#" id="link-end-session">End session now</a>
				</div>
			</div>
			<label><input type="checkbox" class='check opt-send-on-enter' /> Send on [enter]</label><br />
			<label><input type="checkbox" class='check opt-quote' /> Send as quote</label><br />
			<label class="key-label"><input type="checkbox" class='check key-event' /> Key event</label><br />
			<input type="submit" value="Send" class='button' />
		</div>
		<textarea class='new-msg'></textarea>
		<input type="text" class='key-text' value='<?php echo $assanka_webchat->current_webchat_brand->insertkeytext; ?>'></input>
	</form>

	<div class="wiki-key">
		<span class="highlight">*</span>bold<span class="highlight">*</span><br/>
		<span class="highlight">/</span>italics<span class="highlight">/</span><br/>
		<span class="highlight">&gt;</span>block quote
		<span class="highlight">[</span>http://url.com Link<span class="highlight">]</span><br/>
	</div>

	<?php
	$emoticons = $assanka_webchat->current_webchat_brand->getEmoticons();
	if (!empty($emoticons)):
	?>
	<div class="emoticons">
		<ul>

			<?php
			foreach ($emoticons as $emoticon) {
				$shortcut = $emoticon->getShortCut();
				if (empty($shortcut)) {
					?>
					<li><img alt="Emoticon" src="<?php echo $emoticon->getURL();?>" /></li>
					<?php
				}
			}
			?>

		</ul>
	</div>
	<?php endif; ?>

</div>
