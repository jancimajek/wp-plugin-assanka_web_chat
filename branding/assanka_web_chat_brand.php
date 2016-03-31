<?php
/**
 * Parent class for Webchat brands.
 * ("Markets Live", "Live Blogs", "Live Q&A")
 *
 * Brands share common functionality in this class, and
 * contain methods only to overload those in this class — or
 * where functionality is specifically required for that brand.
 */

// @TODO:ADAM:20141119:Refactor appropriate parts of this code (e.g. formatMessage) into the parent class.

use FTLabs\Memcache;
use FTLabs\TemplateManager\TemplateManagerV5;

abstract class Assanka_WebchatBrand {
	static private $brands = array();

	static public function registerBrand($brand) {
		self::$brands[] = $brand;
		$brand->register_post_type();
	}

	static public function getForPost($post) {
		if (empty($post->post_type)) { return null; }

		return self::getForPostType($post->post_type);
	}

	static public function getForPostID($id) {
		$thepost = get_post($id);
		return self::getForPost($thepost);
	}

	/**
	 * Factory method returns correct class based on the post type, or null if none is found
	 *
	 * @param string $post_type
	 * @return Assanka_WebchatBrand
	 */
	static public function getForPostType($post_type) {
		foreach (self::$brands as $brand) {
			if ($brand->post_type == $post_type) {
				return $brand;
			}
		}

		return null;
	}

	static public function getAll() {
		return self::$brands;
	}

	public $author_name_style                  = 'initials';
	public $fixed_height                       = true;
	public $content_order                      = 'ascending';
	public $alloweditanddeletepreviousmessages = false;
	public $closecommentingonendofchat         = false;
	public $requireparticipantinitials         = true;
	public $allowparticipantheadshots          = true;
	public $allowMessageBylines                = true;

	const IMAGE_URL_REGEX    = "/^https?\:\/\/\S+\.(jpe?g|gif|png|img)$/i";
	const TWITTER_URL_REGEX  = "/^(?:https?\:\/\/)?(www.)?twitter\.com\/\S+/i";

	const FTVDEO_PIXEL_WIDTH = 480;

	private $plugindir, $pathtofiles;
	private $requiredproperties = array(
		'singular_name',
		'plural_name',
		'post_type',
		'slug',
		'default_excerpt',
		'author_name_style',
		'fixed_height',
		'content_order',
		'alloweditanddeletepreviousmessages',
		'closecommentingonendofchat',
		'validWordpressThemes',
		'initial_polling_wait_time',
		'poll_interval',
		'requireparticipantinitials',
	);

	const READER_COMMENTS_WIDGET_NAME = 'livechatcomments';
	const MPU_WIDGET_NAME = 'mpu';
	const MOST_POPULAR_WIDGET_NAME = 'mostpopular';

	protected $emoticons = array();

	public function __construct($pathtofiles) {

		$notfoundproperties = $this->checkAllRequiredPropertiesAreDefined();
		if (!empty($notfoundproperties)) {
			throw new Exception("Required properties not defined: {".join(", ", $notfoundproperties)."}", 0, null);
		}

		if (!file_exists($pathtofiles)) {
			throw new Exception("Location '".$pathtofiles."' not found", 0, null);
		}

		$this->plugindir = realpath(dirname(__FILE__)."/..");
		$this->pathtofiles = preg_replace("/^".preg_quote($this->plugindir, "/")."/", "", $pathtofiles);

		$this->emoticons = array(
			Assanka_WebchatEmoticon::create('tounge_smile', array(
				'shortcut' => ':-P',
			)),
			Assanka_WebchatEmoticon::create('whatchutalkingabout_smile', array(
				'shortcut' => ':-|',
			)),
			Assanka_WebchatEmoticon::create('wink_smile', array(
				'shortcut' => ';-)',
			)),
			Assanka_WebchatEmoticon::create('omg_smile', array(
				'shortcut' => '8-O',
			)),
			Assanka_WebchatEmoticon::create('regular_smile', array(
				'shortcut' => ':-)',
			)),
			Assanka_WebchatEmoticon::create('sad_smile', array(
				'shortcut' => ':-(',
			)),
			Assanka_WebchatEmoticon::create('shades_smile', array(
				'shortcut' => '8-)',
			)),
			Assanka_WebchatEmoticon::create('thumbs_down'),
			Assanka_WebchatEmoticon::create('thumbs_up'),
			Assanka_WebchatEmoticon::create('teeth_smile'),
			Assanka_WebchatEmoticon::create('cry_smile'),
			Assanka_WebchatEmoticon::create('omg_smile'),
			Assanka_WebchatEmoticon::create('embarassed_smile'),
			Assanka_WebchatEmoticon::create('censored'),
			Assanka_WebchatEmoticon::create('angry_smile'),
			Assanka_WebchatEmoticon::create('devil_smile'),
			Assanka_WebchatEmoticon::create('wink_smile'),
			Assanka_WebchatEmoticon::create('lightbulb'),
			Assanka_WebchatEmoticon::create('bandit1'),
			Assanka_WebchatEmoticon::create('bandit2'),
			Assanka_WebchatEmoticon::create('bandit3'),
			Assanka_WebchatEmoticon::create('bandit4'),
			Assanka_WebchatEmoticon::create('bandit5'),
			Assanka_WebchatEmoticon::create('bandit6'),
			Assanka_WebchatEmoticon::create('bandit7'),
			Assanka_WebchatEmoticon::create('bandit8'),
			Assanka_WebchatEmoticon::create('bandit9'),
			Assanka_WebchatEmoticon::create('bandit10'),
			Assanka_WebchatEmoticon::create('bear'),
			Assanka_WebchatEmoticon::create('bull'),
			Assanka_WebchatEmoticon::create('buy'),
			Assanka_WebchatEmoticon::create('sell'),
			Assanka_WebchatEmoticon::create('cash'),
			Assanka_WebchatEmoticon::create('danger'),
			Assanka_WebchatEmoticon::create('deadcat'),
			Assanka_WebchatEmoticon::create('feltcollaredsource'),
			Assanka_WebchatEmoticon::create('financier'),
			Assanka_WebchatEmoticon::create('rocket'),
			Assanka_WebchatEmoticon::create('scorchedfingers'),
			Assanka_WebchatEmoticon::create('swag'),
			Assanka_WebchatEmoticon::create('tinhat'),
			Assanka_WebchatEmoticon::create('separator'),
			Assanka_WebchatEmoticon::create('breaking_news', array(
				'cssclass' => 'breaking-news'
			)),
		);

		/**
		 * Register filters to add "Status" column to the list of Webchats in Wp Admin
		 */
		add_filter('manage_' . $this->post_type . '_posts_columns', 	  array($this, 'hookAddWebchatColumnsHead'));
		add_filter('manage_' . $this->post_type . '_posts_custom_column', array($this, 'hookAddWebchatColumnsContent'), 10, 2);


		/**
		 * Register filters to override default Livefyre scripts
		 */
		add_filter('livefyre_script',      array($this, 'hookLivefyreScript'));
		add_filter('livefyre_init_script', array($this, 'hookLivefyreInitScript'));

		// Add ComingSoon meta box
		add_action('load-post.php',     array($this, 'setupComingSoonMetabox'));
		add_action('load-post-new.php', array($this, 'setupComingSoonMetabox'));

		add_filter('post_class',        array($this, 'hook_post_class'), null, 3);

		// Add custom sidebar for WebChats; hooked to "wp_loaded" instead of "register_sidebar"
		// so that it is registered after the theme's default sidebar
		add_action('wp_loaded',  array($this, 'registerWebchatsSidebar'));
	}

	/**
	 * Include appropriate classes for the web-chat post.
	 */
	function hook_post_class($classes, $class, $post_id){
		if(!in_array('post', $classes)) {
			$classes[] = 'post';
		}
		if(!empty($this->webchat_pagetype)) {
			$classes[] = 'webchat-' . $this->webchat_pagetype . '-session';
		}

		/**
		 * Guest Participants (i.e. Participants who aren't WP Editors or Admins)
		 * shouldn't be able to open and close webchat sessions.
		 * If the current user has edit_published_posts permissions, add a class;
		 * so then the UI can be revealed via CSS when appropriate.
		 */
		if(empty($GLOBALS['wp']->query_vars['participant_token'])) {
			$classes[] = 'participant-is-editor';
		}

		// return apply_filters('post_class', $classes, $class, $post->ID);
		return $classes;
	}

	/**
	 * Method sets up hooks for the ComingSoon meta box
	 */
	public function setupComingSoonMetabox() {
		add_action('add_meta_boxes', array($this, 'addComingSoonMetaBoxHook'));
		add_action('save_post',      array($this, 'saveComingSoonMetaBoxHook'), 10, 2);
	}

	/**
	 * Method adds Status meta box
	 * @see http://codex.wordpress.org/Function_Reference/add_meta_box
	 */
	public function addComingSoonMetaBoxHook() {
		add_meta_box(
			'webchat_comingsoon',                   // ID
			esc_html__($this->singular_name . ' Status'),       // Title
			array($this, 'renderComingSoonMetaBox'),// Callback
			$this->post_type,                    // Post type
			'normal',                            // Context
			'default'                            // Priority
		);
	}

	/**
	 * Method renders the ComingSoon meta box HTML form.
	 * Note that the form will be disabled if the session has ended.
	 *
	 * @param stdClass $post WP_Post object
	 * @see http://codex.wordpress.org/Class_Reference/WP_Post
	 * @param array $callbackArgs arguments passed via the callback_args parameter of the add_meta_box (none expected in this case)
	 */
	public function renderComingSoonMetaBox($post, $callbackArgs) {

		$useComingSoon = get_post_meta($post->ID, 'webchat_use_comingsoon');

		// If we get an empty array, it means the option was not yet set for this post, so we retrieve the blog's  default setting
		if (empty($useComingSoon)) {
			// This is for the unlikely instances where the webchat is in progress already but doesn't have
			// the option set (e.g. webchats running while this code is deployed)
			if ($post->post_status == 'publish') {
				$setComingSoonStatus = false;
			} else {
				$setComingSoonStatus = get_option( 'webchats_use_comingsoon_default', false);
			}
		} else {
			$setComingSoonStatus = ($useComingSoon[0] == '1');
		}
		// Unset ComingSoon status when a post is closed
		if (Assanka_Webchat::getInstance()->postIsClosed($post->ID)){
			$setComingSoonStatus = false;
		}

		$checkedAttr = ($setComingSoonStatus ? 'checked="checked" ' : '');

		// Output security nonce
		echo wp_nonce_field(basename(__FILE__), 'webchat_comingsoon_nonce');

		// If the webchat is published, the coming soon status cannot be changed,
		// so we want to disable the option
		$disabledAttr = $disabledStyle = '';
		if ($post->post_status == 'publish') {
			$disabledAttr = 'disabled="disabled"';
			$disabledStyle = 'style="color:#777;"';
		}

		echo '<p>';
		_e('Please choose if the ' . $this->singular_name . ' should start in "Coming soon" or "In progress" state.<br />');
		_e('<em>Note that once the ' . $this->singular_name . ' is published, this status can no longer be changed from WP Admin. You can still switch the session from "Coming soon" to "In progress" using the Webchat toolbox on the ' . $this->singular_name . ' itself. There is no way to switch session "In progress" back to "Coming Soon".</em>');
		echo '</p>';
		echo '<p ' . $disabledStyle . '><label for="webchat_use_comingsoon">';
		echo '<input type="checkbox" name="webchat_use_comingsoon" id="webchat_use_comingsoon" value="1" ' . $disabledAttr . $checkedAttr. '/> ';
		_e('Set "Coming Soon" status for this ' . $this->singular_name . ' post.');
		echo '</label></p>';
	}

	/**
	 * Method handles form submission, checks the nonce validity, user permissions,
	 * whether current post is closed (comingsoon status be cannot changed for
	 * closed webchats), and that the submitted value is a valid one; then stores the
	 * value as post meta data
	 * @param int $postId
	 * @param stdClass $post WP_Post object
	 */
	public function saveComingSoonMetaBoxHook($postId, $post) {
		// Verify the nonce before proceeding
		if (!isset($_POST['webchat_comingsoon_nonce']) || !wp_verify_nonce($_POST['webchat_comingsoon_nonce'], basename( __FILE__ ))) {
			return;
		}

		// Check if the current user has permission to edit the post
		$postType = get_post_type_object($post->post_type);
		if (!current_user_can($postType->cap->edit_post, $postId)) {
			return;
		}

		// Check if the session is closed in which case the ComingSoon status is unset
		if (Assanka_Webchat::getInstance()->postIsClosed($postId)) {
			update_post_meta($postId, 'webchat_use_comingsoon', false);
			return;
		}

		// If webchat has been already published, the value cannot be changed from WP Admin (just
		// via front-end webchat toolbox), and in fact should not even be set
		if (!isset($_POST['original_post_status']) || $_POST['original_post_status'] == 'publish') {
			return;
		}

		// Save the new value
		update_post_meta($postId, 'webchat_use_comingsoon', (bool)$_POST['webchat_use_comingsoon']);
	}

	private function checkAllRequiredPropertiesAreDefined() {
		$notfound = array();
		foreach ($this->requiredproperties as $requiredproperty) {
			if (!property_exists($this, $requiredproperty) or !isset($this->$requiredproperty)) {
				$notfound[] = $requiredproperty;
			}
		}
		return $notfound;
	}

	public function findThemeFile($directory, $filename) {
		static $cache = array();

		if (empty($cache[$directory])) {
			$cache[$directory] = array();
		}

		if (empty($cache[$directory][$filename])) {

			$filepath = $directory."/".$filename;

			$files = array(
				$this->pathtofiles."/".$filepath,
				"/branding/brands/default/".$filepath,
			);

			foreach ($files as $file) {
				if (file_exists($this->plugindir.$file)) {
					$cache[$directory][$filename] = substr($file, 1);
					break;
				}
			}
		}

		if (isset($cache[$directory][$filename])) {
			return $cache[$directory][$filename];
		}

		return null;
	}


	public final function register_post_type() {
		$labels = array(
			'name'                => $this->plural_name,
			'singular_name'       => $this->singular_name,
			'add_new'             => 'Add New',
			'all_items'           => 'All Sessions',
			'add_new_item'        => 'Add a new ' . $this->singular_name . ' session',
			'edit_item'           => 'Edit '      . $this->singular_name . ' session',
			'new_item'            => 'New '       . $this->singular_name . ' session',
			'view_item'           => 'View '      . $this->singular_name . ' session',
			'search_items'        => 'Search '    . $this->singular_name . ' sessions',
			'not_found'           => 'No '        . $this->singular_name . ' sessions found',
			'not_found_in_trash'  => 'No '        . $this->singular_name . ' sessions found in trash',
			'parent_item_colon'   => 'Parent '    . $this->singular_name . ' sessions:',
			'menu_name'           => $this->plural_name
		);
		$post_type_args = array(
			'labels'              => $labels,
			'description'         => 'Creates pages for ' . $this->singular_name . ' sessions.',
			'public'              => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_nav_menus'   => false,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 70,
			'menu_icon'           => null,
			'capability_type'     => 'page',
			'hierarchical'        => false,
			'supports'            => array('title', 'thumbnail', 'excerpt', 'comments', 'author'),
			'has_archive'         => false,
			'rewrite'             => array('slug' => $this->slug),
			'query_var'           => true,
			'can_export'          => true,
			'taxonomies'          => $this->taxonomies
		);

		// Allow webchat posts to be edited if they're closed
		if (!empty($_GET['post']) and get_post_meta($_GET['post'], 'is_closed', $single = true)) {
			$post_type_args['supports']   = array_merge($post_type_args['supports'], array('editor', 'revisions'));
		}

		register_post_type($this->post_type, $post_type_args);
	}

	public function getEmoticons() {
		return $this->emoticons;
	}

	/**
	 * Render plaintext message into html
	 * @param  string $messageText Input from textarea
	 * @param  string $msgtype     Normal or Blockquote
	 * @return string              HTML version of the message
	 */
	public function renderMessageText($messageText, $msgtype='normal') {

		// Ignore message types other than normal & blockquote
		// (Possible types: 'normal','blockquote','price','sysmsg','separator')
		if (!in_array($msgtype, array('normal', 'blockquote'))) return $messageText;

		$messageHTML = esc_textarea($messageText);
		$messageHTML = $this->convertInlineWikiFormatting($messageHTML);
		$messageHTML = $this->doAutoEmbeds($messageHTML);
		$messageHTML = $this->convertURLsAndEmailAddressesToLinks($messageHTML);
		$messageHTML = $this->replaceEmoticons($messageHTML);
		if ($msgtype == 'blockquote') {
			$messageHTML = '<blockquote>' . $messageHTML . '</blockquote>';
		}
		$messageHTML = wpautop($messageHTML);
		return $messageHTML;
	}

	/**
	 * Format message array into HTML
	 * @param  array  $message An individual webchat message
	 * @return string $html    The message in HTML format
	 */
	public function formatMessage($message) {
		global $wpdb, $assanka_webchat;

		// TODO:ADAM:20141118 Clean up this data model to have consistent variable names
		$templateData = array(
			"messagebody"              => $this->renderMessageText($message["msgtext"],$message["msgtype"]),
			"messageid"                => $message["id"],
			"messagetype"              => $message["msgtype"],
			"pubdate"                  => $message["pubdate"],
			"datemodified"             => $message["datemodified"],
			"classes"                  => $this->getMessageClasses($message),
			"show_participant_options" => ($assanka_webchat->shouldShowParticipantOptions() and empty($message["forpermanentarchive"])),
		);
		$templateData['pubdate']->setTimezone(new DateTimeZone('Europe/London'));

		// Populate the template data with Participant details
		$messageParticipant = Assanka_Webchat::getInstance()->getMessageParticipant($message["user_id"]);

		// Modulo the author colour because there are only 12 colours assigned in css
		$templateData["authorcolour"]         = $messageParticipant->colour % 12;
		$templateData["authorinitials"]       = $messageParticipant->initials;
		$templateData["authordisplayname"]    = $messageParticipant->display_name;
		$templateData["headshot"]             = $messageParticipant->headshot;

		/**
		 * TODO:ADAM201119:Figure out and explain what's happening here
		 */
		if (empty($message["forpermanentarchive"]) and current_user_can(Assanka_Webchat::PARTICIPANT_CAPABILITY)) {
			$templateData["rawmessage"]            = $message["msgtext"];
			$templateData["htmlencodedrawmessage"] = str_replace(array("\n", "\r"), array("&#10;", "&#13;"), htmlspecialchars($message["msgtext"], ENT_QUOTES));
		} else {
			unset($templateData["datemodified"]);
		}

		// strip tags from key event text
		$keytext = $message["keytext"] ? $message["keytext"] : $message["keyevent"];
		$templateData["keytext"] = wp_strip_all_tags($keytext);

		// Prepare data attributes (discard empty-string attributes and build string for template)
		// This is to avoid excessive numbers of data attributes ending up in the closed webchat.
		$dataAttributes = array(
			'timestamp' => $templateData['pubdate']->format('U'),
			'mid' => $templateData['messageid'],
			'rawmessage' => $templateData['htmlencodedrawmessage'],
			'keytext' => $templateData['keytext'],
			'isblockquote' => (($templateData['messagetype'] == 'blockquote')?'1':'0'),
			'datemodified' => (empty($templateData['datemodified'])?'':($templateData['datemodified']->format('U'))),
		);
		$strDataAtributes = '';
		foreach ($dataAttributes as $key => $value) {
			if ($value !== '') {
				$strDataAtributes .= ' data-'.$key.'=\''.$value.'\'';
			}
		}
		
		$templateData['strdataatributes'] = substr($strDataAtributes, 1);

		$templateData['show_message_authornames'] = get_post_meta(get_the_ID(), 'webchat_show_message_authornames', true);
		$templateData['show_message_headshots'] = get_post_meta(get_the_ID(), 'webchat_show_message_headshots', true);

		$html = $this->applyMessageTemplate($templateData);
		return $html;
	}

	public function getAbstract() {
		$template = $this->findThemeFile("transcript", "web_chat_abstract.php");
		if (empty($template)) {
			throw new Exception("No abstract template found", 0, null);
		}

		// Generate a cosmetic list of Participants,
		// with appropriate commars and 'and' before the last one.
		$participants = Assanka_Webchat::getInstance()->getParticipants();
		foreach ($participants as $participant) {
			$participant_strings[] = '<span class="par color-'.$participant->colour.'">'.htmlspecialchars($participant->display_name).'</span>';
		}
		$strparticipants = '';
		$last_participant = array_pop($participant_strings);
		if (count($participant_strings) > 0) {
			$strparticipants .= implode(", ", $participant_strings) . " and " . $last_participant;
		} else {
			$strparticipants = $last_participant;
		}

		return $this->renderTemplate($template, array(
			"dategenerated" => new DateTime('now', new DateTimeZone('Europe/London')),
			"strparticipants" => $strparticipants,
			"brandname" => $this->singular_name,
		));
	}

	public function getHeader($vars) {
		$template = $this->findThemeFile("transcript", "web_chat_transcriptheader.php");

		if (empty($template)) {
			throw new Exception("No transcript header template found", 0, null);
		}

		return $this->renderTemplate($template, $vars);
	}

	protected function applyMessageTemplate($vars) {
		$template = $this->findThemeFile("live", "web_chat_message.php");
		if (empty($template)) {
			throw new Exception("No message template found", 0, null);
		}
		return $this->renderTemplate($template, $vars);
	}

	public function renderParticipantUI() {
		$template = $this->findThemeFile("live", "participant_ui.php");

		if (empty($template)) {
			throw new Exception("No message template found", 0, null);
		}

		return $this->renderTemplate($template);
	}

	public function formatContent($closedcontent) {
		$template = $this->findThemeFile("transcript", "web_chat_transcriptcontent.php");

		if (!empty($template)) {
			return $this->renderTemplate($template, array("closedcontent" => $closedcontent));
		}

		return $closedcontent;
	}

	protected function renderTemplate($template, $vars=array()) {
		if (is_array($vars)) extract($vars);

		ob_start();
		include $this->plugindir."/".$template;
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	protected function convertInlineWikiFormatting($msg) {
		$msg = $this->convertCharacterToHTMLTag("/", "em", $msg);
		$msg = $this->convertCharacterToHTMLTag("*", "strong", $msg);
		$msg = $this->convertWikiStyleLinks($msg);
		$msg = $this->convertWikiStyleBlockQuotes($msg);
		return $msg;
	}

	protected function convertCharacterToHTMLTag($character, $tag, $msg) {
		$msg = preg_replace(
			'/([^'.preg_quote($character, '/').'\w]|\A)'.preg_quote($character, '/').'([^'.preg_quote($character, '/').']+)'.preg_quote($character, '/').'([^'.preg_quote($character, '/').'\w]|\Z)/',
			'$1<'.$tag.'>$2</'.$tag.'>$3',
			$msg
		);

		return $msg;
	}

	protected function convertWikiStyleLinks($msg) {
		$url = $this->getURLRegex();
		$basicpattern = '\[\s*' . $url . '(.+)\s*\]';

		// Cannot capture the first character because that breaks the URL regex
		// so need to separate the basic pattern off to extract it within the callback function
		$msg = preg_replace_callback("/.?".$basicpattern."/i", function($m) use ($basicpattern) {

			// Omit patterns occuring within words
			if (!preg_match("/^".$basicpattern."$/i", $m[0])) {
				$firstchar = substr($m[0], 0, 1);
			}

			$pathkey = 10;
			$href = $m[1].(empty($m[$pathkey])?"":$m[$pathkey]);
			$textkey = 11;
			$text = (empty($m[$textkey])?$href:$m[$textkey]);

			return (empty($firstchar)?"":$firstchar)."<a href='".sanitize_text_field($href)."' target='_blank'>".sanitize_text_field($text)."</a>";

		}, $msg);
		return $msg;
	}

	protected function convertWikiStyleBlockQuotes($msg) {
		$pattern     = '/(?:\s)?&gt;(?:\s)?(.*)/im';
		$replacement = '<blockquote>'.PHP_EOL.PHP_EOL.'$1'.PHP_EOL.PHP_EOL.'</blockquote>';
		$html        = preg_replace($pattern, $replacement, $msg);
		$html        = preg_replace('/\<\/blockquote\>(?:\s)?\<blockquote\>/im', PHP_EOL, $html);
		return $html;
	}

	private function getURLRegex($addDelimeters = false, $addAnchors = false) {
		$scheme		= "(http:\/\/|https:\/\/)";
		$www		= "www\.";
		$ip			= "\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}";
		$name		= "[a-z0-9][\_\-a-z0-9]*\.";
		$tld		= "[a-z]{2,}(\.[a-z]{2,2})?";
		$the_rest	= '(\/?[a-z0-9\._\/\,\:\|\^\@\!~#&=;%+?$\\-_\\+\\*\'\\(\\)]+[a-z0-9\/#=?\\$\\-\\_\\.\\+\\!\\*\'\\(\\)\\,])?';
		$pattern	= "(".$scheme."?(?(2)(".$ip."|(".$name.")+".$tld.")|(".$www."(".$name.")+".$tld.")))(".$the_rest.")";

		if ($addAnchors) {
			$pattern = "^".$pattern."$";
		}

		if ($addDelimeters) {
			$pattern = "/".$pattern."/i";
		}

		return $pattern;
	}

	protected function replaceEmoticons($msg) {
		$msg = $this->replaceEmoticonShortcuts($msg);
		$msg = $this->replaceEmoticonPlaceholdersWithImageTags($msg);

		return $msg;
	}

	protected function replaceEmoticonShortcuts($msg) {
		$shortcuts = array();
		foreach ($this->emoticons as $emoticon) {
			$shortcut = $emoticon->getShortCut();
			if (!empty($shortcut)) {
				$shortcuts[$shortcut] = $emoticon->getPlaceHolder();
			}
		}

		return $this->changeTextOutsideHTMLTags($msg, function ($str, $uniquestring) use ($shortcuts) {
			$shortcutvalues = array_values($shortcuts);
			$newshortcutvalues = array();
			foreach ($shortcutvalues as $v) {
				$newshortcutvalues[] = $uniquestring.base64_encode($v).$uniquestring;
			}
			$shortcutvalues = $newshortcutvalues;

			return str_replace(array_keys($shortcuts), $shortcutvalues, $str);
		});
	}

	protected function replaceEmoticonPlaceholdersWithImageTags($msg) {
		$emoticons = $this->emoticons;

		return $this->changeTextOutsideHTMLTags($msg, function ($str, $uniquestring) use ($emoticons) {
			return preg_replace_callback("/\{([^\} ]+)\}/i", function ($m) use ($uniquestring, $emoticons) {

				// Find emoticon
				$emoticon = null;
				foreach ($emoticons as $thisemoticon) {
					if ($thisemoticon->getName() == $m[1]) {
						$emoticon = $thisemoticon;
						break;
					}
				}

				if (empty($emoticon)) return $m[0];

				return $uniquestring.base64_encode('<img src="'.$emoticon->getURL().'" alt="Emoticon" class="emoticon '.$emoticon->getCSSClass().'" />').$uniquestring;
			}, $str);
		});
	}

	protected function doAutoEmbeds($msg) {
		$msg = str_replace(array("\r\n", "\r"), "\n", $msg);
		$lines = explode("\n", $msg);

		$newlines = array();
		foreach ($lines as $line) {
			$trimmedline = trim($line);

			if (!preg_match($this->getURLRegex(true, true), $trimmedline)) {
				$newline = $line;

			} elseif (preg_match(self::IMAGE_URL_REGEX, $trimmedline, $m)) {
				$newline = '<img class="picture" src="'.$m[0].'" />';

			} elseif (preg_match(self::TWITTER_URL_REGEX, $trimmedline, $m)) {
				$newline = $this->getTwitterEmbedHTML($m[0]);

			} else {
				$newline = $this->convertMediaEmbeds($trimmedline);
			}

			$newlines[] = $newline;
		}

		return join("\n", $newlines);
	}

	public function getTwitterEmbedHTML($url) {
		$encodedurl = htmlspecialchars($url);
		$msg = "<p class='embeddedtweet'><a href='".$encodedurl."'>".$encodedurl."</a></p>";

		return $msg;
	}

	private function getOEmbedHTML($apiurl, $embedurl) {

		$memcache = Memcache::getMemcache();
		$memcachekey = "blogs-oembed-".md5($embedurl);
		$html = $memcache->get($memcachekey);
		if (!empty($html)) {
			return $html;
		}

		$resp = @json_decode(file_get_contents($apiurl));
		if (empty($resp->html)) {
			return null;
		}

		$memcache->set($memcachekey, $resp->html, 86400);
		return $resp->html;
	}

	protected function convertMediaEmbeds($content) {
		// @todo try to use hooks instead of checking for the class
		if (class_exists('FTBlogs_MediaEmbed')) {
			return htmlspecialchars(FTBlogs_MediaEmbed::convertMediaEmbeds(htmlspecialchars_decode($content,ENT_QUOTES)));
		}
	}

	protected function convertURLsAndEmailAddressesToLinks($msg) {

		return $this->changeTextOutsideHTMLTags($msg, function($v, $uniquestring) {
			return $uniquestring.base64_encode(TemplateManagerV5::modifier_autolink($v)).$uniquestring;
		});
	}

	/**
	 * This function allows applying transformations (string replace, etc)
	 * to a block of HTML text that only operate on those parts of the block
	 * that are neither tag attributes nor inside tags - i.e. only top-level
	 * text nodes.  It is necessary to prevent the various placeholder replacements
	 * and embedded videos from interfering with each other.
	 *
	 * Please note that the HTML snippet that is passed in should be well-formed
	 * or PHP DOMDocument will complain.  The 'callback' should accept two parameters:
	 *
	 * 1) The string to be transformed
	 * 2) A delimiter, consisting of a short string of characters that is not
	 * anywhere in the original snippet.  Any replacements made to the string
	 * should be base64 encoded, and surrounded on either side by the delimiter provided.
	 * This function will then decode afterwards; this is to get round the fact that,
	 * using PHP domdocument, you cannot insert HTML code into a text node (so instead
	 * the method used here is to base64 encode the HTML code, and decode it after the
	 * process has finished)
	 */
	private function changeTextOutsideHTMLTags($htmlsnippet, $callback) {

		// Find a unique ID number that does not occur anywhere in the snippet
		// (to allow the callback function to insert placeholders with this ID)
		// Demarcate with pipe characters so that base64 text between this markers
		// can be easily extracted using a regex, and for ease of reading.
		$uniquestring = "|".$this->findUniqueString($htmlsnippet)."|";

		// Mark up the HTML snippet to allow extracting top-level text nodes
		// (after this, they will be immediate children of the new root node)
		$wrapperid = "rootnode";
		$before = "<div id='".$wrapperid."'>";
		$after = "</div>";
		$str = $before.$htmlsnippet.$after;

		// Find all text-nodes immediately beneath the root node in the hierarchy
		// and run the callback function on them.
		$doc = new DOMDocument;

		// BODGE:WV:20130107:DOMDocument seems strip line-breaks following image tags,
		// if they do not encompass any text there is no text either side of them.
		// So, replace all line breaks with placeholder characters while loading the
		// string into DOMDocument and then unreplace them once it has been loaded.
		// Demarcate the unique strings with hyphens because these characters are valid
		// in all html attributes.
		$newline = "-".$this->findUniqueString($str.$uniquestring)."-";
		$carriagereturn = "-".$this->findUniqueString($str.$uniquestring.$newline)."-";
		$str = str_replace(
			array(
				"\n",
				"\r"
			),
			array(
				$newline,
				$carriagereturn
			),
			$str
		);

		// 'Load as UTF-8' technique from http://php.net/manual/en/domdocument.loadhtml.php#95251
		@$doc->loadHTML("<?xml encoding=\"UTF-8\">".$str);
		foreach ($doc->childNodes as $item) {
			if ($item->nodeType == XML_PI_NODE) {
				$doc->removeChild($item);
			}
		}
		$doc->encoding = 'UTF-8';

		// Update first-level text nodes
		$xpath = new DOMXpath($doc);
		$textNodes = $xpath->query("//*[@id=\"".$wrapperid."\"]/text()");
		foreach ($textNodes as $textNode) {
			$textNode->data = str_replace(
				array(
					$newline,
					$carriagereturn,
				),
				array(
					"\n",
					"\r"
				),
				$textNode->data
			);
			$textNode->data = call_user_func_array($callback, array($textNode->data, $uniquestring));
		}

		// Extract the root node's inner HTML
		$rootnode = $xpath->query("//*[@id=\"".$wrapperid."\"]")->item(0);
		$tmpdom = new DOMDocument;
		$tmpdom->appendChild($tmpdom->importNode($rootnode, true));
		$outerhtml = $tmpdom->saveHTML();
		$innerhtml = substr($outerhtml, strlen($before), ((strlen($outerhtml) - strlen($before)) - strlen($after)) - 1);

		// Return any new lines
		$innerhtml = str_replace(
			array(
				$newline,
				$carriagereturn,
			),
			array(
				"\n",
				"\r"
			),
			$innerhtml
		);

		// Do the final preg_replace to remove the placeholders
		$innerhtml = preg_replace_callback("/".preg_quote($uniquestring, "/")."(.*?)".preg_quote($uniquestring, "/")."/", function($m) {
			return base64_decode($m[1]);
		}, $innerhtml);

		return $innerhtml;
	}

	private function findUniqueString($haystack) {
		do {
			$currentstring = uniqid();
		} while (strpos($haystack, $currentstring) !== false);

		return $currentstring;
	}

	protected function getMessageClasses($data) {
		$classes = array(
			'msg',
			$data['msgtype'],
		);

		$messageParticipant = Assanka_Webchat::getInstance()->getMessageParticipant($data["user_id"]);
		if (!empty($messageParticipant->headshot)) {
			$classes[] = 'has-headshot';
		}

		$now = new DateTime('now', new DateTimeZone('GMT'));
		if ($data['pubdate'] > $now) {
			$classes[] = 'prepub';
		}

		return $classes;
	}

	/**
	 * Method allows to completely override the RHR sidebar using the FT Wrappers.
	 * By default, it does not do anything, but provides API for Brands to display custom
	 * sidebars (e.g. MarketsLive)
	 *
	 * @param $pagetype
	 * @param $placeholders
	 * @return mixed
	 */
	public function setCustomSidebar($pagetype, $placeholders) {
		return $placeholders;
	}

	public function shouldShowRightRail($pagetype) {
		return true;
	}

	public function getJavascriptConfig() {
		global $wp, $assanka_webchat;

		$userIsEditor = true;

		// Add participant token to the base URL of all ajax requests
		$baseurl = home_url() . '/' . $wp->request . '/';
		if (is_array($wp->query_vars) and is_numeric($wp->query_vars['participant_token'])) {
			$baseurl .= "?participant_token=".esc_attr($wp->query_vars['participant_token']);
			// Only WP editors can edit and delete messages
			$userIsEditor = false;
		}

		$alloweditanddeletepreviousmessages = ($userIsEditor && $this->alloweditanddeletepreviousmessages);
		$webchat_javascript_config = array(
			'baseurl'                            => $baseurl,
			'participants'                       => $assanka_webchat->getParticipants(),
			'pusherkey'                          => $_SERVER['PUSHER_KEY'],
			'fixed_height'                       => $this->fixed_height,
			'content_order'                      => $this->content_order,
			'authornamestyle'                    => $this->author_name_style,
			'alloweditanddeletepreviousmessages' => $alloweditanddeletepreviousmessages,
			'initial_polling_wait_time'          => $this->initial_polling_wait_time,
			'poll_interval'                      => $this->poll_interval,
			'connection_notification'            => $this->connection_notification,
			'insertkeytext'	                     => $this->insertkeytext
		);

		return $webchat_javascript_config;
	}

	/**
	 * Decorates the excerpt with optional notices
	 * (e.g. "This session is coming soon / in progress / closed").
	 *
	 * This is overridden for the Markets Live brand in register.php
	 *
	 * For closed webchats, the banner should be only shown if
	 * WP Admin > Settings > Webchats > Show Transcript Banner is enabled.
	 *
	 * Note: Whenever the notice banner is displayed, the "Read More" link is removed.
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

		$thumbnail = get_the_post_thumbnail(
			null,
			array(168,250),
			array('class' => 'alignleft')
		);

		// Add a notice to excerpts (on, e.g. the front page)
		$notice = '';
		if ($assanka_webchat->currentPostIsClosed()) {
			// Only show the transcript banner if enabled in WP Admin > Settings > Webchats:
			if ( get_option( 'webchats_show_transcript_banner', true) ) {
				// The notice banner will be prepended to the excerpt
				$notice  = '<div class="webchat-notice entry-meta-box is-closed">This ' . $this->singular_name .  ' session is closed. ';
				$notice .= '<a class="webchat-notice-more-link" href="'. get_permalink() .'">Read more</a></div>';

				// Remove Read More link in this case as it is already part of the notice
				$excerpt = preg_replace('/\<a ([^>]*?)more-link(.*?)\>(.*?)\<\/a\>/', '', $excerpt);
			}
		}

		return $notice . $thumbnail . $excerpt;
	}

	/**
	 * Decorates the title; by default does nothing.
	 * Can be overridden in child brand classes
	 *
	 * @param Assanka_Webchat $assanka_webchat
	 * @param string $title
	 * @return string
	 */
	public function decorateTitle(Assanka_Webchat $assanka_webchat, $title) {
		return $title;
	}

	/**
	 * Method to add an optional "lozenge" before the title, e.g. to indicate a
	 * live session is in progress. By default does not return anything and should
	 * be overridden in child classes if necessary.
	 *
	 * Example usage in theme template:
	 *   <?php echo (class_exists('Assanka_Webchat') ? Assanka_Webchat::getInstance()->getLozenge() : ''); ?>
	 *
	 * @param Assanka_Webchat $assanka_webchat
	 * @return string
	 */
	public function getLozenge(Assanka_Webchat $assanka_webchat)
	{
		$lozenge = '';
		if (!$assanka_webchat->currentPostIsClosed()) {
			if ($assanka_webchat->currentPostIsComingSoon()) {
				$lozenge = '<span class="webchat-lozenge webchat-comingsoon">Coming soon</span>';
			} else {
				$lozenge = '<span class="webchat-lozenge webchat-inprogress">In progress</span>';
			}
		} else {
			$lozenge = '<span class="webchat-lozenge webchat-closed">Closed</span>';
		}

		return $lozenge;
	}

	/**
	 * Hook adds "Status" column to the list of Web Chants in WP Admin
	 * @see http://code.tutsplus.com/articles/add-a-custom-column-in-posts-and-custom-post-types-admin-screen--wp-24934
	 *
	 * @param array $columns
	 * @return array
	 */
	public function hookAddWebchatColumnsHead($columns) {
		return array(
			'cb' => '<input type="checkbox" />',
			'title' => _x('Title', 'column name'),
			'status' => __('Status'),
			'tags' => __('Tags'),
			'date' => _x('Date', 'column name'),
		);
	}

	/**
	 * Hook renders content of "Status" column cells in the list of Web Chats in WP Admin
	 * @see http://code.tutsplus.com/articles/add-a-custom-column-in-posts-and-custom-post-types-admin-screen--wp-24934
	 *
	 * @param string $column
	 * @param int $postId
	 */
	public function hookAddWebchatColumnsContent($column, $postId) {
		if ($column === 'status') {
			if (Assanka_Webchat::getInstance()->postIsClosed($postId)) {
				echo '<span class="webchat-lozenge webchat-closed">Closed</span>';
			} elseif (Assanka_Webchat::getInstance()->postIsComingSoon($postId)) {
				echo '<span class="webchat-lozenge webchat-comingsoon">Coming soon</span>';
			} else {
				echo '<span class="webchat-lozenge webchat-inprogress">In progress</span>';
			}
		}
	}

	/**
	 * Hook to override custom Livefyre script URL for live webchat sessions. It returns value of
	 * the "webchat_livefyre_script" option, if it is set and non-empty; otherwise it returns what
	 * was passed in the $script parameter (the network-wide default script URL).
	 *
	 * Brand can overload this behaviour if required.
	 *
	 * Note: "webchat_livefyre_script" option is set in WP Admin > Settings > Webchats > Livefyre
	 *
	 * @see FTBlogs_Livefyre::enqueue_scripts()
	 *
	 * @hookedTo livefyre_script
	 * @param string $script
	 * @return string
	 */
	public function hookLivefyreScript($script) {

		// Only apply this to sessions in progress:
		if ($this->post_type != get_post_type() || Assanka_Webchat::getInstance()->currentPostIsClosed()) {
			return $script;
		}

		$customScript = trim(get_option('webchat_livefyre_script'));
		return ($customScript != '' ? $customScript : $script);
	}

	/**
	 * Hook to override custom Livefyre init script URL for live webchat sessions. It returns URL
	 * to file branding/brands/<brand>/js/livefyre.js, if it exists; otherwise it returns what was
	 * passed in the $script parameter (the default script placed within ftblogs-livefyre plugin).
	 *
	 * Brand can overload this behaviour if required.
	 *
	 * @see FTBlogs_Livefyre::enqueue_scripts()
	 *
	 * @hookedTo livefyre_init_script
	 * @param string $script
	 * @return string
	 */
	public function hookLivefyreInitScript($script) {

		// Only apply this to sessions in progress:
		if ($this->post_type != get_post_type() || Assanka_Webchat::getInstance()->currentPostIsClosed()) {
			return $script;
		}

		$customScript = '/brands/' . $this->slug . '/js/livefyre.js';

		return (
			file_exists(dirname(__FILE__) . $customScript) ?
				plugins_url($customScript, __FILE__) :
				$script
		);
	}

	/**
	 * Hook to register custom sidebar for WebChats to be displayed in WP Admin > Appearance > Widgets
	 * This sidebar will be than displayed instead of the default theme's sidebar on WebChats.
	 *
	 * Note: Brands may override this method to create their own specific sidebar or not show any sidebar
	 *       at all, should they choose to handle sidebars entirely in the code (e.g. MarketsLive)
	 *
	 * @hookedTo wp_loaded
	 */
	public function registerWebchatsSidebar() {
		register_sidebar( array(
			'name' => 'Webchats sidebar',
			'id'   => 'sidebar-webchats',
			'description' => 'Replaces the "Default sidebar" on WebChats.',
		));
	}

}
