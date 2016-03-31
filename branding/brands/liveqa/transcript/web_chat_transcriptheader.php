<?php

// Prepare certain parts of the template beforehand to avoid wordpress auto-paragraph generator
$markedupexcerpt = "";
if (!empty($excerpt)) {
	$markedupexcerpt .= '<div class=\'webchat-post-excerpt\'>'.wpautop($excerpt).'</div>';
}

?><div class="webchat-introduction clearfix"><div class="webchat-date pubdate entry-meta entry-date"><?php echo $abstract;?></div><?php the_post_thumbnail(array(168,250), array('class' => 'alignleft')); ?><?php echo $markedupexcerpt;?><?php echo $keypoints; ?></div>