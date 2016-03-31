<?php /* Template Name: Webchat */ ?>

<div class="primary-container">
	<?php get_header(); ?>
	<div class="page-columns-container clearfix">
		<div class="webchat-container primary-content" role="main">

			<?php
			if (have_posts()) :

				the_post();

				global $post, $assanka_webchat;

				$participants = $assanka_webchat->getParticipants();
				$emoticons = $assanka_webchat->current_webchat_brand->getEmoticons();
			?>

			<div id="webchat-session" <?php post_class(); ?>>
				<div class="loader"></div>

				<div class="comments-panel widget <?php do_action('marketslive_comments_widget_class'); ?>">
					<?php do_action('marketslive_comments_widget'); ?>
				</div>
				<div class="chat-panel">
					<?php do_action ('marketslive_comments_widget_scroll_sync_checkbox'); ?>
					<div class="webchat-header">
						<h2><?php echo $post->post_excerpt; // Intentionally not using the_excerpt() to avoid filters ?></h2>
					</div>
					<div class='wrapper'>
						<div class='chat'></div>
					</div>

					<?php echo $assanka_webchat->current_webchat_brand->renderParticipantUI(); ?>

				</div>
				<div class='footer'>
					<div class="webchat-participants"></div>
				</div>
			</div>
			<script>var InfernoConfig = InfernoConfig || {}; jQuery.extend(InfernoConfig, {overrides:{sort:'dateasc'}});</script>

			<?php endif; ?>
		</div>
	</div><!-- primary-content -->
</div><!-- primary-container -->
