<?php
/**
 * Register the configuration settings for the "Markets Live" brand.
 * Only add methods here to overload those in the parent class,
 * or where functionality is specifically required for this brand only.
 */
class Assanka_WebchatBrand_Marketslive extends Assanka_WebchatBrand {
	public $singular_name              = 'Markets Live';
	public $plural_name                = 'Markets Live';
	public $post_type                  = 'webchat-markets-live';
	public $slug                       = 'marketslive';
	public $default_excerpt            = 'Live markets commentary from FT.com';
	public $validWordpressThemes       = array('wrapper-alphaville');
	public $initial_polling_wait_time  = 30;
	public $poll_interval              = 3;
	public $requireparticipantinitials = true;
	public $allowparticipantheadshots  = false;
	public $allowMessageBylines        = false;
	public $taxonomies                 = array('post_tag');
	public $connection_notification    = "Real time stream connected. New messages will appear here the moment they are published.";

	/**
	 * For marketslive-brand posts, do not apply the 'hentry' class to pending or live,
	 * but do allow it for closed sessions.
	 */
	function hook_post_class($classes, $class, $post_id){
		$classes = parent::hook_post_class($classes, $class, $post_id);

		if (is_single() && get_post_type() === "webchat-markets-live" && !Assanka_WebChat::postIsClosed($post_id)) {
			$classes = array_diff($classes, array('hentry'));
		}
		return $classes;
	}

	public function shouldShowRightRail($pagetype) {
		return $pagetype == 'live' ? false : true;
	}

	public function setCustomSidebar($pagetype, $placeholders) {
		if ($pagetype == 'brand_page') {

			$sidebar_template = plugin_dir_path(__FILE__).'brand_page/web_chat_brand_sidebar.php';
			if (file_exists($sidebar_template)) {

				// See the assanka_ftwrappers plugin for more info
				global $assanka_ftwrapper;
				if (isset($placeholders['rail'])) {
					$placeholders['rail']['replacement'] = array(
						'object'=>$assanka_ftwrapper,
						'function'=>'load_wordpress_function',
						'parameters'=>array('name'=>'load_template', 'arguments'=>$sidebar_template),
					);
				}
			}
		}
		return $placeholders;
	}

	/**
	 * Decorate the excerpt with a notice (coming soon/in progress/closed).
	 *
	 * @param Assanka_Webchat $assanka_webchat
	 * @param string $excerpt
	 * @return string
	 */
	public function decorateExcerpt(Assanka_Webchat $assanka_webchat, $excerpt) {

		// If this is an in-session webchat, the notice nor the read-more is not neccessary.
		if($assanka_webchat->getWebchatPagetype() == 'live') {
			return preg_replace('/\<a ([^>]*?)more-link(.*?)\>(.*?)\<\/a\>/', '', $excerpt);
		}

		// Add a notice to excerpts (on, e.g. the front page)
		$notice = '';
		if ($assanka_webchat->currentPostIsClosed()) {
			// Only show the transcript banner if enabled in WP Admin > Settings > Webchats:
			if ( get_option( 'webchats_show_transcript_banner', true) ) {
				// The notice banner will be prepended to the excerpt
				$notice  = '<div class="webchat-notice entry-meta-box is-closed">This ' . $this->singular_name .  ' session is closed. ';
				$notice .= '<a class="webchat-notice-more-link" href="'. get_permalink() .'">Read more</a></div>';

				// Remove Read More link in this case as it is already part of the notice
				$excerpt = preg_replace('/\<a (.*?)more-link(.*?)\>(.*?)\<\/a\>/', null, $excerpt);
			}
		} else {
			$chatStatus = ( $assanka_webchat->currentPostIsComingSoon() ? 'coming soon': 'currently in progress');
			// The notice banner will be prepended to the excerpt
			$notice  = '<div class="webchat-notice entry-meta-box is-live">This ' . $this->singular_name;
			$notice  .=  ' session is ' .$chatStatus. '. ';
			$notice .= '<a class="webchat-notice-more-link" href="'. get_permalink() .'">Join the discussion</a></div>';

			// Remove Read More link in this case as it is already part of the notice
			$excerpt = preg_replace('/\<a ([^>]*?)more-link(.*?)\>(.*?)\<\/a\>/', null, $excerpt);
		}

		return $notice . $excerpt;
	}

	/**
	 * No "ComingSoon" meta box for Markets Live.
	 */
	public function setupComingSoonMetabox() {
		return null;
	}

	/**
	 * Overrides the default hook that creates custom WebChats sidebar in WP Admin > Appearance > Widgets
	 *
	 * There is no sidebar for MarketsLive, as it is entirely handled in code for sessions In Progress,
	 * and displays the default theme's "sidebar-1" on transcripts
	 *
	 * @hookedTo wp_loaded
	 */
	public function registerWebchatsSidebar() {
		// No custom sidebar on Markets live
	}

}
$brand = new Assanka_WebchatBrand_Marketslive(dirname(__FILE__));
Assanka_WebchatBrand::registerBrand($brand);
