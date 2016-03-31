<?php global $assanka_webchat; ?>

<div class="participantui">
	<form accept-charset="utf-8">
		<div class='options'>
			<div class='session-options'>
				<a href="#" id="link-end-session">End session now</a>
			</div>
			<label><input type="checkbox" class='check opt-send-on-enter' />Send on [enter]</label><br />
			<label><input type="checkbox" class='check opt-quote' />Send as quote</label><br />
			<input type="submit" value="Send" class='button' /><br />
		</div>
		<textarea class='new-msg'></textarea>
	</form>
	<div class="wiki-key">
		<span class="highlight">*</span>bold<span class="highlight">*</span><br/>
		<span class="highlight">/</span>italics<span class="highlight">/</span><br/>
		<span class="highlight">&gt;</span>block quote
		<span class="highlight">[</span>http://url.com Link<span class="highlight">]</span><br/>
	</div>
	<div class="emoticons">

		<?php
		$emoticons = $assanka_webchat->current_webchat_brand->getEmoticons();
		if (!empty($emoticons)) {
			?>
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
			<?php
		}
		?>

	</div>
</div>
