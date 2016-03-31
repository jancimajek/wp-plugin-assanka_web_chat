<div class="secondary-content widget-area railSection" role="complementary">
	<div class="inner">

		<?php
		// Display the MPU (advertising) widget
		if (class_exists(Assanka_MpuWidget)) echo Assanka_MpuWidget::widget($args=null, $instance=null);

		// Get the list of closed webchats for this page
		$webchat_brand = get_post_meta(get_the_ID(), 'webchat_brand', true);
		$closed_webchats = new WP_Query( array('post_type' => $webchat_brand) );
		?>

		<?php if($closed_webchats->have_posts()): ?>

		<div id="webchat-archive" class="widget_webchat widget">
			<h2 class="widgettitle">Webchat archive</h2>
			<div class="widgetcontent">
				<div class="webchat webchat-archive">

					<?php while ( $closed_webchats->have_posts() ) : $closed_webchats->the_post(); ?>
					<div class="webchat-item">
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</div>
					<?php endwhile; wp_reset_postdata(); ?>

				</div>
			</div>
		</div>
		<?php endif; ?>

	</div>
</div>
