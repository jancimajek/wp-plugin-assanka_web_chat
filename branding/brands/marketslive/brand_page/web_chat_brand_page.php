<?php /* Template Name: Webchat Brand Page */ ?>

<?php /* Webchat Brand pages require a special template which shows a list of closed webchats. */ ?>

<div class="primary-container">
	<?php get_header(); ?>
	<div class="page-columns-container clearfix">
		<div class="webchat-container primary-content" role="main">
			<div class="inner">
				<?php if (have_posts()) : the_post(); global $post; ?>
				<div id="post-<?php the_ID(); ?>" <?php post_class('clearfix'); ?>>
					<div class="webchat-content">
						<?php get_template_part('content-post'); ?>
					</div><!-- /webchat-content -->
				</div><!-- /clearfix -->
				<?php endif; ?>
			</div>
		</div><!-- primary-content -->
	</div><!--  page-columns-container -->
</div><!-- primary-container -->
