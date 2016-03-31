<?php /* Template Name: Webchat */ ?>

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
				<?php comments_template( '', true ); ?>
			</div>
		</div><!-- primary-content -->
		<?php get_footer(); ?>
	</div>
</div><!-- primary-container -->
