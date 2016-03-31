<?php
/**
 * Template Name: Webchat.
 */
?>

<?php
	get_header();
	global $assanka_webchat;
?>

<div class="primary-content webchat-container" role="main">

	<?php if (have_posts()) : the_post(); global $post; ?>
	<div id="webchat-session" <?php post_class(); ?>>

		<div class="loader"></div>

		<div class="chat-panel">

			<div class="webchat-introduction">

				<div class="webchat-header">
					<h2 class="entry-title">
						<?php assanka_show_post_byline(); // Displays an author headshot or category/country flag. ?>
						<?php echo (class_exists('Assanka_WebChat') ? Assanka_WebChat::getInstance()->getLozenge() : ''); ?>
						<?php the_title(); ?>
					</h2>
				</div>

				<div class="entry-meta">
					<strong>
						<?php 
						if ( function_exists( 'coauthors_posts_links' ) ) {
						    coauthors_posts_links();
						} else {
						    the_author_posts_link();
						};
						?>
					</strong>
					<div class="inline-block">
						<!-- author alerts -->
						<?php  
					 	if (class_exists('Assanka_UID')){
							$blog_post_uid = Assanka_UID::get_the_post_uid();
						}
						?>
						<div class="o-author-alerts o-author-alerts--theme" data-o-component="o-author-alerts" data-o-version="0.1.0" data-o-author-alerts-article-id="<?php echo $blog_post_uid; ?>"></div>
						<span class="meta-divider"> | </span>
						<span class="entry-date"><?php echo assanka_get_the_time(); ?></span>
						<span class="meta-divider"> | </span>
						<?php echo falcon_get_share_widget_html(); ?>
					</div>
				</div><!-- .entry-meta -->

				<div class="webchat-post-excerpt clearfix">
					<?php
					the_post_thumbnail(
						array(168,250),
						array('class' => 'alignleft')
					);
					?>

					<p>
						<?php echo $post->post_excerpt; // Intentionally not using the_excerpt() to avoid filters ?>
					</p>
				</div>
			</div>

			<?php do_action("social_buttons_and_counters"); ?>


			<div class='wrapper entry-content'>
				<div class='chat'></div>
			</div>

			<?php echo $assanka_webchat->current_webchat_brand->renderParticipantUI(); ?>

			<div class="entry-utility entry-meta"><?php falcon_posted_in(); ?></div><!-- .entry-utility -->

			</div>
		<div class='footer'></div>
	</div>

	<?php endif; //have_posts ?>
</div><!-- primary-content -->

<?php get_footer(); ?>
