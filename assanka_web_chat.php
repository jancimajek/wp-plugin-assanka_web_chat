<?php
/*
Plugin Name: Assanka Webchat
Plugin URI: http://blogs.ft.com
Description: Creates a custom post type that has live webchat functionality.
Author: Assanka
Version: 1.0
Author URI: http://assanka.net/
*/

require_once $_SERVER['COREFTCO'].'/helpers/cacheability/cacheability';
require_once dirname(__FILE__).'/assanka_web_chat_emoticon.php';

class Assanka_Webchat {
	public  $current_webchat_brand = false;

	private $logger                = null;
	private $notice                = null;
	private $plugindir             = null;
	private $pluginfile            = null;
	private $is_webchat            = false;
	private $webchat_pagetype      = false;
	private $embargoed_events      = array();
	private $possible_ajax_calls   = array(
		'poll'           => array('requiresparticipant' => false),
		'catchup'        => array('requiresparticipant' => false),
		'getmeta'        => array('requiresparticipant' => false),
		'getconfig'      => array('requiresparticipant' => false),
		'getprivs'       => array('requiresparticipant' => false),
		'gettime'        => array('requiresparticipant' => false),
		'sendmsg'        => array('requiresparticipant' => true),
		'editmsg'        => array('requireseditor' => true),
		'deletemsg'      => array('requireseditor' => true),
		'block'          => array('requiresparticipant' => true),
		'end'            => array('requireseditor' => true),
		'startSession'   => array('requireseditor' => true),
	);

	const PARTICIPANT_CAPABILITY = 'edit_posts';
	const EDITOR_CAPABILITY      = 'edit_published_posts';
	const EMBARGO_TIME           = 15;
	const RGX_MARKETSQUOTE       = "([A-Z\.]{2,5}\:(LSE|NSQ|BER|NYQ|ASX|CVE|PNK|ASQ|PAR))";

	static public function getPluginURL() {
		return plugins_url()."/".basename(dirname(__FILE__));
	}

	/**
	 * Singleton-like method to easily get the global Webchat object
	 *
	 * @return Assanka_Webchat
	 */
	public static function getInstance() {
		if (!isset($GLOBALS['assanka_webchat'])) {
			$GLOBALS['assanka_webchat'] = new Assanka_Webchat();
		}

		return $GLOBALS['assanka_webchat'];
	}

	public function __construct() {
		$this->logger = new FTLabs\Logger('blogs-webchat');

		// Disable all reporting to FT Labs error aggregator (129 is one higher than the highest severity level)
		$this->logger->setHandlerMinSeverity('report', 129);

		// Action/Filter hooks
		add_action('parse_request',          array($this, 'hook_parse_request'));
		add_action('init',                   array($this, 'hook_init'));
		add_action('wp',                     array($this, 'hook_wp'), 1, 1);
		add_filter('single_template',        array($this, 'hook_single_template'));
		add_filter('page_template',          array($this, 'hook_single_template'));
		add_filter('wp_nav_menu_objects',    array($this, 'hook_wp_nav_menu_objects'));
		add_filter('pre_get_posts',          array($this, 'hook_pre_get_posts'));
		add_filter('the_posts',              array($this, 'hook_the_posts'), 2, 2);
		add_action('the_post',               array($this, 'hook_the_post'));
		add_filter('the_title',              array($this, 'hook_the_title'));
		add_filter('the_excerpt',            array($this, 'hook_the_excerpt'));
		add_filter('wp_footer',              array($this, 'hook_wp_footer'));
		add_filter('ftwrapper_placeholders', array($this, 'hook_ftwrapper_placeholders'));
		add_filter('sidebars_widgets',       array($this, 'hook_sidebars_widgets'));
		add_filter('wp_enqueue_scripts',     array($this, 'enqueueJavascript'));
		add_filter('wp_enqueue_scripts',     array($this, 'enqueueStylesheets'));
		add_filter('query_vars',             array($this, 'hook_query_vars'));

		// Admin hooks
		add_action('wp_insert_post_data',    array($this, 'hook_wp_insert_post_data'), null, 2);
		add_action('post_updated',           array($this, 'hook_post_updated'));
		add_action('add_meta_boxes',         array($this, 'hook_add_meta_boxes'));
		add_action('edit_user_profile',      array($this, 'hook_edit_user_profile'));
		add_action('show_user_profile',      array($this, 'hook_edit_user_profile'));
		add_filter('edit_user_profile_save', array($this, 'hook_edit_user_profile_save'));
		add_action('admin_menu',             array($this, 'hook_admin_menu'));
		add_action('admin_init',             array($this, 'hook_admin_init'));
		add_action('admin_notices',          array($this, 'hook_admin_notices'));
		add_filter('mce_css',                array($this, 'hook_mce_css'));
		add_filter('admin_enqueue_scripts',  array($this, 'hook_admin_enqueue_scripts'));

		// Set-up and tear-down tasks
		add_action('activate_plugin',        array($this, 'hook_activate_plugin'),0,2);
		register_activation_hook(__FILE__,   array($this, 'webchat_db_install'));
		register_activation_hook(__FILE__,   array($this, 'webchat_flush_rewrite_rules'));
		register_uninstall_hook(__FILE__,    array($this, 'webchat_db_uninstall'));
		register_deactivation_hook(__FILE__, array($this, 'webchat_flush_rewrite_rules'));
	}

	/**
	 * Check to see if the current page request is for a webchat custom post.
	 */
	public function hook_parse_request($wp) {

		// Find webchat brand
		if (is_admin() or empty($wp->query_vars['post_type'])) { return null; }
		$brand = Assanka_WebchatBrand::getForPostType($wp->query_vars['post_type']);
		if ($brand == null) { return; }

		// Switch Wordpress query to this webchat
		$webchat_query = new WP_Query(array(
			'name'           => $wp->query_vars['name'],
			'post_type'      => $wp->query_vars['post_type'],
			'post_status'    => array('publish','draft','private'),
			'posts_per_page' => 1
		));

		if (!$webchat_query->have_posts()) return;

		$this->is_webchat            = true;
		$this->current_webchat_brand = $brand;
		$webchat_query->the_post();

		$this->setAndConfigureWebchatPageType();
		$this->detectParticipant();
		$this->handle_ajax_requests();

		wp_reset_postdata();
		wp_reset_query();
	}

	/**
	 * @todo: Figure out where "transcript" and "live" webchat_pagetypes are used and
	 * change them to a webchat_status solution. Leave other uses of webchat_pagetype alone.
	 */
	private function setAndConfigureWebchatPageType() {
		if ($this->currentPostIsClosed()) {
			$this->webchat_pagetype = 'transcript';
		} else {

			// If it's not a transcript, then it must be a live session.
			$this->webchat_pagetype = 'live';
		}
	}

	public function currentPostIsClosed() {
		return $this->postIsClosed(get_the_ID());
	}

	public function postIsClosed($postid) {
		return get_post_meta($postid, 'is_closed', $single = true);
	}

	public function currentPostIsComingSoon() {
		return $this->postIsComingSoon(get_the_ID());
	}

	public function postIsComingSoon($postid) {
		return (bool) get_post_meta($postid, 'webchat_use_comingsoon', $single = true);
	}

	/**
	 * Method replaces theme default "sidebar-1" with "sidebar-webchats" for WebChats.
	 *
	 * Note: There is no "sidebar-webchats" defined for MarketsLive, so it will always
	 *       display default "sidebar-1" except for in progress sessions that are
	 *       programmatically hardcoded!
	 *
	 * @hookedTo sidebars_widgets
	 * @param array $sidebars_widgets
	 * @return array
	 */
	public function hook_sidebars_widgets($sidebars_widgets = array()) {
		if ($this->isWebChatPage() &&
			is_array($sidebars_widgets) &&
			isset($sidebars_widgets['sidebar-1']) &&
			isset($sidebars_widgets['sidebar-webchats'])
		) {

			$sidebars_widgets['sidebar-1'] = $sidebars_widgets['sidebar-webchats'];
		}

		return $sidebars_widgets;
	}

	public function hook_ftwrapper_placeholders($placeholders = null) {
		if (!$this->isWebchatPage()) {
			return $placeholders;
		}
		if (!$this->current_webchat_brand->shouldShowRightRail($this->webchat_pagetype)) {

			// See the assanka_ftwrappers plugin for more info
			if (isset($placeholders['rail'])) {
				unset($placeholders['rail']);
			}
		} else {
			$placeholders = $this->current_webchat_brand->setCustomSidebar($this->webchat_pagetype, $placeholders);
		}

		return $placeholders;
	}


	/**
	 * Controller for ajax requests
	 */
	private function handle_ajax_requests() {
		global $wpdb, $post;

		if (!isset($this->possible_ajax_calls[$_REQUEST['action']])) {
			return;
		}

		$ajaxcall = (object)$this->possible_ajax_calls[$_REQUEST['action']];
		if (($ajaxcall->requiresparticipant && !current_user_can(self::PARTICIPANT_CAPABILITY))
			|| ($ajaxcall->requireseditor && !current_user_can(self::EDITOR_CAPABILITY))) {
			header("HTTP/1.1 403 Forbidden");
			exit ("Permission denied.");
		}

		$logdata = array_merge($_GET, $_POST);

		switch($_REQUEST['action']) {
			case 'gettime':
				Cacheability::noCache();
				$response = time();
				break;

			case 'getprivs':
				Cacheability::noCache();
				$response = array();
				$response['isparticipant'] = (!!current_user_can(self::PARTICIPANT_CAPABILITY));
				$response['channel'] = $this->getPusherChannel(current_user_can(self::PARTICIPANT_CAPABILITY));
				break;

			case 'getmeta':
				Cacheability::noCache();
				$response['channel'] = $this->getPusherChannel();
				if ($this->currentPostIsClosed()) {
					$status = 'closed';
				} elseif ($this->currentPostIsComingSoon()) {
					$status = 'comingsoon';
				} else {
					$status = 'inprogress';
				}
				$response['status'] = $status;
				break;

			case 'getconfig':
				Cacheability::noCache();
				$response = $this->current_webchat_brand->getJavascriptConfig();
				break;

			case 'poll':
				Cacheability::setVarnishExpiryTime(10);
				Cacheability::setExternalExpiryTime(0);
				Cacheability::outputHeaders();
				if (!empty($_REQUEST['channels'])) {
					$twominutesago = new DateTime('2 minutes ago', new DateTimeZone('UTC'));
					$channels = explode(',', $_REQUEST['channels']);
					$channels = array_map('addslashes', $channels);
					$channels = join('","', $channels);
					$qry = 'SELECT channel, event, data FROM '.$wpdb->prefix.'webchat_pusher WHERE channel IN ("'.$channels.'") AND datepushed_gmt > "'.$twominutesago->format('Y-m-d H:i:s').'" ORDER BY datepushed_gmt ASC';
					$response = $wpdb->get_results($qry, ARRAY_A);
					foreach ($response as $k=>$row) $response[$k]['data'] = json_decode($row['data']);
				} else {
					$response = array();
				}
				break;

			case 'catchup':
				Cacheability::expiresAfter(10);
				if (!empty($_REQUEST['format']) && $_REQUEST['format'] == 'json') {
					$response = array();
					$channel = $this->getPusherChannel();
					$direction = ($this->current_webchat_brand->content_order == 'descending') ? 'DESC' : 'ASC';
					$query = 'SELECT event, data FROM '.$wpdb->prefix.'webchat_pusher WHERE channel = "' . $channel . '" ORDER BY datepushed_gmt ' . $direction;
					$response = $wpdb->get_results($query, ARRAY_A);
					foreach ($response as $k=>$row) {
						$response[$k]['data'] = json_decode($row['data']);
					}
				} else {
					$response = $this->get_html();
				}
				break;

			case 'deletemsg':
				Cacheability::noCache();
				$logdata['event'] = 'delete-start';
				$this->logger->info('', $logdata);

				$messageid = (empty($_POST['messageid'])?null:$_POST['messageid']);
				if (empty($messageid)) {
					$logdata['validationerror'] = 'No message ID provided';
					$this->logger->info('', $logdata);
					$response = 'No message ID provided';
					break;
				}

				$msg = $this->loadMessageByID($messageid);
				if (empty($msg)) {
					$response = 'Submitted message ID not found';
					break;
				}

				$this->deleteMessageFromDB($msg->id);
				$this->sendToEveryoneViaPusher('delete', array(
					'messageid' => $msg->id
				));

				$response = true;
				break;

			case 'editmsg':
				Cacheability::noCache();
				$logdata['event'] = 'edit-start';
				$this->logger->info('', $logdata);

				$fields = array(
					'messageid'    => 'Message ID',
					'newtext'      => 'New message text',
					'keytext'      => 'Key event text',
					'isblockquote' => 'Whether or not the message is a quotation',
				);

				$submitted_data = array();
				$missing_fields = array();
				foreach ($fields as $field => $longname) {
					$submitted_data[$field] = (empty($_POST[$field])?null:$_POST[$field]);
					$logdata[$field] = (empty($_POST[$field])?"Not provided":$_POST[$field]);
					$this->logger->info('', $logdata);
					if (!isset($_POST[$field])) {
						$missing_fields[$field] = $longname;
					}
				}

				if (!empty($missing_fields)) {
					$response = 'The following input fields were not provided: {\''.join('\', \'', $missing_fields).'\'}';
					break;
				}

				$msg = $this->loadMessageByID($submitted_data['messageid']);
				if (empty($msg)) {
					$response = 'Submitted message ID not found';
					break;
				}

				$postdata = array(
					"isblockquote" => $submitted_data["isblockquote"],
					"msg"          => $submitted_data["newtext"],
					"keytext"      => $submitted_data["keytext"],
				);

				$result = $this->processSubmittedMessage($postdata, $logger, $logdata, $msg);
				if ($result["result"] == "error") {
					$response = $result["message"];
					break;
				}

				$response = $result["formattedmessage"];

				break;

			case 'sendmsg':
				Cacheability::noCache();
				$logdata['event'] = 'start';
				$logdata['msg'] = substr($logdata['msg'], 0, 50);
				$this->logger->info('', $logdata);

				if ($this->currentPostIsClosed()) {
					$response = "The chat has finished.  No more messages can be posted.";
					break;
				}

				// @TODO:WV:20121130:System messages should be retrospectively addable via editing messages
				$result = $this->processSubmittedMessage($_POST, $logger, $logdata);
				if ($result["result"] == "error") {
					$response = $result["message"];
					break;
				}
				$msg = $result["messagetext"];
				$data = $result["messagedata"];
				$response = true;

				// Detect and queue system messages
				$sysmsgs = $wpdb->get_results("SELECT keyword as k, message as v FROM ".$wpdb->prefix."webchat_systemmessages WHERE brand='".$this->current_webchat_brand->slug."'", OBJECT_K);
				foreach ($sysmsgs as $keyword => $sysmsg) {
					if (preg_match("/\b".preg_quote($keyword, "/")."\b/i", $msg)) {
						if (!$wpdb->get_var($wpdb->prepare("SELECT 1 as v FROM ".$wpdb->prefix."webchat_messages WHERE post_id=%d AND msgtype='sysmsg' AND msgtext=%s", get_the_ID(), $sysmsg->v))) {
							$data['msgtype'] = 'sysmsg';
							$data['msgtext'] = $sysmsg->v;
							$logdata['event'] = 'presend-sysmsg';
							$this->logger->info('', $logdata);
							$this->insertMessage($data);
						}
					}
				}

				// If a symbol has been mentioned, fetch the quote
				if (preg_match("/\b".self::RGX_MARKETSQUOTE."\b/", $msg, $m)) {
					$symb = $m[1];
					$op = shell_exec("curl -sL \"http://markets.ft.com/apis/mobile/quote.asp?symbol=".strtoupper($symb)."\" -m 5");
					$xml = simplexml_load_string($op, 'SimpleXMLElement', LIBXML_NOCDATA);
					if (isset($xml->name)) {
						$quotmsg = "<strong>".$xml->name." (".$xml->symbol."):</strong> Last: ".$xml->last.", ".(($xml->change>0)?"up ".abs((float)$xml->change)." (".$xml->changePercent.")":(($xml->change<0)?"down ".abs((float)$xml->change)." (".$xml->changePercent.")":" no change"));
						if ($xml->high > 0) $quotmsg .= ", High: ".$xml->high.", Low: ".$xml->low;
						if ($xml->volumeMagnitude) $quotmsg .= ", Volume: ".$xml->volumeMagnitude;
						$data['msgtype'] = 'price';
						$data['msgtext'] = $quotmsg;
						$logdata['event'] = 'presend-pricemsg';
						$this->logger->info('', $logdata);
						$response = $this->insertMessage($data);
						if ($response["pusherresult"] !== true) {
							$response = "Error sending pricing quote to Pusher: ".$response["pusherresult"];
						}
					}
				}

				break;

			case 'block':
				Cacheability::noCache();
				$current_user = wp_get_current_user();
				$current_user->initials = !empty($current_user->initials)?$current_user->initials:$current_user->webchat_initials;
				$wpdb->query($wpdb->prepare('UPDATE '.$wpdb->prefix.'webchat_messages SET blockedby_user_id=%d WHERE id=%d AND post_id=%d', $current_user->ID, $_POST['id'], get_the_ID()));

				$this->sendToParticipantsViaPusher('block', array('msgblocked'=>$_POST['id'], 'blockedby'=>$current_user->initials));
				$response = 'OK';
				break;

			case 'end':
				Cacheability::noCache();

				// Stop here if the session has already been ended
				if ($this->currentPostIsClosed()) {
					$data = array(
						'guid' => get_permalink()
					);
					break;
				}

				// Generate the webchat HTML
				$webchat_html = $this->get_html('chronological', true);

				// If chat was empty, move the post to trash
				if (!$webchat_html["msg"]) {
					wp_trash_post($post->ID);
					$data = array();
				} else {

					// Mark the post as ended - before wp_update_post, to ensure hook_save_post changes inferno theme correctly
					update_post_meta(get_the_ID(), 'is_closed', true);

					// Generate abstract containing current time and participants
					$abstract = $this->current_webchat_brand->getAbstract();

					// Generate header for the abstract
					$content = '';
					$content .= $this->current_webchat_brand->getHeader(array(
						'abstract' => $abstract,
						'excerpt'  => (empty($post->post_excerpt)?"":$post->post_excerpt),
						'keypoints'=> (isset($webchat_html["keypoints"]))?$webchat_html["keypoints"]:""
					));
					$content .= $webchat_html["msg"];
					$post->post_content = $content;

					// Close commenting if required
					if ($this->current_webchat_brand->closecommentingonendofchat) {
						$post->comment_status = 'closed';
					}

					unset($post->webchat_brand_name, $post->webchat_participants);
					remove_action('save_post', array($this, 'hook_save_post'));
					wp_update_post($post);

					$this->setInfernoConfig($post->ID, false);

					$data = array(
						'guid' => get_permalink()
					);
				}

				$response = $data;

				// Send an end-session event
				$this->sendToParticipantsViaPusher('end', $data);
				$this->embargoed_events[] = array('end', $data);
				$this->sendNotificationViaPusher('end', $data);
				break;

			case 'startSession':
				Cacheability::noCache();

				// Check that the session hasn't already started:
				if (!$this->currentPostIsComingSoon()) {
					$response = array(
						'status' => 'error_already_started',
					);
					break;
				}

				update_post_meta(get_the_ID(), 'webchat_use_comingsoon', false);

				$response = array(
					'status' => 'success',
				);

				// Send an start-session event
				$this->sendToEveryoneViaPusher('startSession', null);
				break;
		}

		$logdata['event'] = 'ajaxcomplete';
		$this->logger->info('', $logdata);

		// Output response directly to browser as JSON
		ignore_user_abort(true);
		$op = json_encode($response);

		// Allow *.ft.com for Access-Control-Allow-Origin.  While this is active vary: origin
		// must also be set so that any cacheing doesn't cause problems.
		if (!empty($_SERVER['HTTP_ORIGIN']) && (substr($_SERVER['HTTP_ORIGIN'], -7) === '.ft.com') && strlen($_SERVER['HTTP_ORIGIN']) > 7) {
			header('Vary: Origin');
			header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
			header('Access-Control-Allow-Credentials: true');
		}

		header("Content-Type: application/json");
		header("Content-Length: ".strlen($op));
		header('Connection: close');
		echo $op;

		// If there are embargoed events, close the connection and keep the thread alive while the embargo period runs
		if ($this->embargoed_events) {
			while (ob_get_level()) {
				ob_end_flush();
			}
			ob_flush();
			flush();
			sleep(self::EMBARGO_TIME);
			foreach ($this->embargoed_events as $data) {

				// If it's a msg event, ensure the message has not been blocked while embargoed
				if ($data[0] == 'msg' && !$wpdb->get_var('SELECT 1 as test FROM '.$wpdb->prefix.'webchat_messages WHERE id='.$data[1]['mid'].' AND blockedby_user_id IS NULL')) {
					continue;
				}
				if (!empty($data[2])) {
					$data[1]['html'] = $this->formatMessage($data[2]);
				}
				$logdata['event'] = 'presend-embargoed';
				$this->logger->info('', $logdata);
				$this->sendToNonParticipantsViaPusher($data[0], $data[1]);
			}
		}
		exit;
	}

	public function shouldShowParticipantOptions() {
		if (!empty($_REQUEST['action']) and $_REQUEST['action'] == 'end') {
			return false;
		}

		return true;
	}

	private function loadMessageByID($messageid) {
		global $wpdb;

		return $wpdb->get_row($wpdb->prepare('SELECT id, user_id, post_id, msgtype, msgtext, keyevent, dateposted_gmt, datemodified_gmt, blockedby_user_id FROM '.$wpdb->prefix.'webchat_messages WHERE id = %d', $messageid));
	}

	private function processSubmittedMessage($postdata, $logger, &$logdata, $existingmessage = false) {

		// Validate for authentication or format errors
		if (!current_user_can(self::PARTICIPANT_CAPABILITY)) {
			return array(
				"result"  => "error",
				"message" => "You do not have sufficient privileges to post messages in chat.  You need the '".self::PARTICIPANT_CAPABILITY."' capability. See http://codex.wordpress.org/Roles_and_Capabilities#Capability_vs._Role_Table details"
			);
		}

		$brand = Assanka_WebchatBrand::getForPostID(get_the_ID());
		$current_user = wp_get_current_user();
		$current_user->initials = !empty($current_user->initials)?$current_user->initials:$current_user->webchat_initials;
		if ($brand->requireparticipantinitials and empty($current_user->initials)) {
			return array(
				"result"  => "error",
				"message" => "You don't have any initials on your user account. Please set up some initials so that your messages can be attributed to you in the chat window."
			);
		}

		// Message was url-encoded to dodge wordpress addslashes to post input; decode it
		$msg = empty($postdata["msg"]) ? "" : trim(rawurldecode($postdata["msg"]));

		// Key event text
		$keytext = empty($postdata["keytext"])  ? "" : trim(stripslashes_deep(wp_strip_all_tags($postdata["keytext"])));

		if (!(strlen($keytext) <= 1125)) {
			return array(
				"result"  => "error",
				"message" => "Invalid key text - too long (1000 chars max)",
			);
		}

		// Strip invalid UTF-8 characters
		// See http://stackoverflow.com/questions/7502164/replacing-non-utf8-caracters
		$msg = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]|[\x00-\x7F][\x80-\xBF]+|'. '([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})|'. '[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S', '', $msg);
		$msg = preg_replace('/\xE0[\x80-\x9F][\x80-\xBF]|\xED[\xA0-\xBF][\x80-\xBF]/S','', $msg);

		if (!(strlen($msg) > 0 and strlen($msg) <= 4125)) {
			return array(
				"result"  => "error",
				"message" => "Invalid message - too long or too short (valid range 0-4000 chars)",
			);
		}

		// Generate data for DB
		if ($existingmessage === false) {
			$message_author = $this->getMessageParticipant(get_current_user_id());
			$pubdate = new DateTime('@'.(time()+self::EMBARGO_TIME), new DateTimeZone('GMT'));
			$id = NULL;
		} else {
			$message_author = $this->getMessageParticipant($existingmessage->user_id);
			$pubdate = new DateTime($existingmessage->dateposted_gmt, new DateTimeZone('GMT'));
			$id = $existingmessage->id;
		}

		$data = array(
			'id' => (int)$id,
			'post_id'  => get_the_ID(),
			'user_id'  => $message_author->user_id,
			'initials' => $message_author->initials,
			'authordisplayname' => $message_author->display_name,
			'authorcolour' => $message_author->colour,
			'pubdate' => $pubdate,
			'headshot' => $message_author->headshot,
			'authorcolour' => $message_author->colour,
		);

		$data['keytext'] = $keytext;
		// Add modification date for client-side versioning
		$data['datemodified'] = new DateTime('now', new DateTimeZone('GMT'));

		// Process as normal message only if it contains something other than a quote symbol, and does not contain a separator
		if (!preg_match("/^".self::RGX_MARKETSQUOTE."$/", $msg) and strpos($msg, "{separator}") === false) {
			if (empty($postdata["isblockquote"])) {
				$data['msgtype'] = 'normal';
			} else {
				$data['msgtype'] = 'blockquote';
			}
			$data['msgtext'] = $msg;

		// Process separators
		} elseif (strpos($msg, "{separator}") !== false) {
			$data['msgtype'] = 'separator';
			$londontime = new DateTime('now', new DateTimeZone('Europe/London'));
			$data['msgtext'] = $londontime->format("g:iA");
		}
		if (!empty($data['msgtype'])) {
			$logdata['event'] = 'presend-msg';
			$this->logger->info('', $logdata);

			if (empty($existingmessage)) {
				$response = $this->insertMessage($data);
			} else {
				$response = $this->updateExistingMessage($data);
			}
			if ($response["pusherresult"] !== true) {
				return array(
					"result"  => "error",
					"message" => "Error sending message to Pusher: ".$response,
				);
			}
		}
		return array(
			"result"           => "success",
			"formattedmessage" => $response["formattedmessage"],
			"messagetext"      => $msg,
			"keytext"		   => $keytext,
			"messagedata"      => $data,
		);
	}

	private function updateExistingMessage($data) {
		$this->updateMessageInDB($data);
		$data['html'] = $this->formatMessage($data);
		$pusherresult = $this->sendMessageUpdateToEveryoneViaPusher($data);
		return array (
			"pusherresult"     => $pusherresult,
			"formattedmessage" => $data['html'],
		);
	}

	private function insertMessage($data) {
		global $wpdb;

		$data['id'] = $this->updateMessageInDB($data);
		$data['html'] = $this->formatMessage($data);
		$pusherresult = $this->sendMessageUpdateToParticipantsViaPusherAndEmbargoForGeneralRelease($data);

		if (empty($pusherresult)) {
			$this->deleteMessageFromDB($data['id']);
		}

		// Send 'start chat' msg if the just-added message was the first in the chat
		$msgcount = $wpdb->get_var('SELECT count(*) as v FROM '.$wpdb->prefix . 'webchat_messages WHERE post_id='.$data['post_id']);
		if ($msgcount == 1) {
			$this->sendNotificationViaPusher('start', array(
				'guid' => get_permalink()
			));
		}

		return array(
			"pusherresult"     => $pusherresult,
			"formattedmessage" => $data['html'],
		);
	}

	private function sendMessageUpdateToEveryoneViaPusher($data) {
		$pusherdata = $this->getPusherDataForMessage($data);
		$pusherresult = $this->sendToEveryoneViaPusher('editmsg', $pusherdata);
		return $pusherresult;
	}

	private function sendMessageUpdateToParticipantsViaPusherAndEmbargoForGeneralRelease($data) {
		$pusherdata = $this->getPusherDataForMessage($data);
		$pusherresult = $this->sendToParticipantsViaPusher('msg', $pusherdata);
		if ($pusherresult === true) {
			$this->embargoed_events[] = array(
				'msg',
				$pusherdata,
				$data
			);
		}
		return $pusherresult;
	}

	/**
	 * Send pusher a modified subset of data
	 * @param  array $data Full dataset of the message
	 * @return array $pusherdata A modified subset of the message data
	 */
	private function getPusherDataForMessage($data) {
		$pusherdata = array(
			'mid'               => $data['id'],
			'author'            => $data['initials'],
			'authordisplayname' => $data['authordisplayname'],
			'authornamestyle'   => $this->current_webchat_brand->author_name_style,
			'authorcolour'      => $data['authorcolour'],
			'headshot'          => $data['headshot'],
			'text'              => $data['msgtext'],
			'keytext'           => $data['keytext'],
			'emb'               => $data['pubdate']->format('U'),
			'datemodified'      => $data['datemodified']->format('U'),
			'html'              => $data['html'],
		);

		// Include a rendered version of the plaintext message body for third parties (e.g. the web app)
		$pusherdata['textrendered'] = $this->current_webchat_brand->renderMessageText($data["msgtext"],$data["msgtype"]);

		return $pusherdata;
	}

	private function updateMessageInDB($data) {
		global $wpdb;

		$wpdb->query($this->getUpdateMessageQuery($data));
		if (empty($data['id'])) {
			return $wpdb->insert_id;
		}
	}

	private function deleteMessageFromDB($id) {
		global $wpdb;

		$wpdb->query($wpdb->prepare('DELETE FROM '.$wpdb->prefix . 'webchat_messages WHERE id = %d', $id));
	}

	private function getUpdateMessageQuery($data) {
		global $wpdb;

		$basequery = $wpdb->prepare($wpdb->prefix . 'webchat_messages SET user_id=%d, post_id=%d, msgtype=%s, msgtext=%s, keyevent=%s, datemodified_gmt=%s', $data['user_id'], $data['post_id'], $data['msgtype'], $data['msgtext'], wp_strip_all_tags($data['keytext']), $data['datemodified']->format('Y-m-d H:i:s'));
		if (!empty($data['pubdate'])) {
			$basequery .= $wpdb->prepare(', dateposted_gmt=%s', $data['pubdate']->format('Y-m-d H:i:s'));
		}
		if (empty($data['id'])) {
			$fullquery = 'INSERT INTO '.$basequery;
		} else {
			$fullquery = 'UPDATE '.$basequery.$wpdb->prepare(' WHERE id = %d', $data['id']);
		}
		return $fullquery;
	}

	function getPusherChannel($participant = false) {
		return 'webchat.blog-'.get_current_blog_id().'.chat-'.md5(get_the_ID().get_permalink().($participant?'-participants':''));
	}

	function getPusherNotifyChannel() {
		return 'webchat.'.(empty($_SERVER['IS_LIVE'])?'staging-':'').'blog-'.get_current_blog_id().'.notify';
	}

	function formatMessage($data) {
		return $this->current_webchat_brand->formatMessage($data);
	}

	public function getMessageParticipant($user_id){
		foreach ($this->getParticipants() as $participant) {
			if ($participant->user_id == $user_id) {
				return $participant;
			}
		}
		return false;
	}

	/**
	 * There are two kinds of Participants.
	 *  (1) Non-WordPress Users who access via their participant-permalink
	 *  (2) WordPress users who access by signing into WordPress
	 */
	public function getParticipants($post_id=null) {
		global $wpdb, $post;
		if (empty($post_id)) {
			$post_id = $post->ID;
		}
		$post_id = (int)$post_id;

		/**
		 * (1) Non-WordPress Users who access via their participant-permalink
		 */
		$participantsPostMeta = get_post_meta($post_id, 'participants', true);
		$participantUserIDs   = array();
		$participants         = array();
		if (empty($participantsPostMeta) || !is_array($participantsPostMeta)) {
			$participantsPostMeta = array();
		}
		foreach ($participantsPostMeta as $participant) {

			// If the participant's user_id is a wordpress user, ignore it,
			// because it's handled in (2) below.
			/** @var WP_User $user */
			$user = get_userdata($participant->user_id);
			if (is_object($user) && ($user instanceof WP_User)) continue;

			$participant['is_wp_user'] = false;
			$participants[]            = (object)$participant;
			$participantUserIDs[]      = $participant->user_id;
		}

		/**
		 * (2) WordPress users who access by signing into WordPress
		 */

		// Get a list of all users IDs in the webchat_messages that have messages for this post.
		$usersWhoWroteMessages = $wpdb->get_col($wpdb->prepare('SELECT DISTINCT user_id FROM '.$wpdb->prefix . 'webchat_messages WHERE post_id=%d AND blockedby_user_id IS NULL ORDER BY dateposted_gmt', $post_id));

		/**
		 * If a message is being added, and the current WordPress user is not
		 * in the list of users who wrote messages, then add the current WordPress user.
		 */
		if (!empty($_REQUEST['action']) && $_REQUEST['action']== 'sendmsg') {
			if (!in_array(get_current_user_id(),$usersWhoWroteMessages)) {
				$usersWhoWroteMessages[] = get_current_user_id();
			}
		}

		$participantWordPressUsers = array();
		foreach ($usersWhoWroteMessages as $user_id) {

			// Don't add users who are already saved as participants
			if (in_array($user_id, $participantUserIDs)) continue;

			/** @var WP_User $user */
			$user = get_userdata($user_id);
			if (is_object($user) && ($user instanceof WP_User)) {

				// Participants have user accounts in the database, because they
				// need a legitimate user ID. However, these accounts don't contain
				// any usable participant information, so they should be ignored.
				// They're are determined by a user-meta flag called "is_participant".
				if ($user->is_participant) continue;

				$headshot = $user->_ftblogs_headshoturl;
				if (empty($headshot)) $headshot = $this->getHeadshot($user->user_email);
				$participantUser = array(
					'user_id'      => $user->ID,
					'display_name' => $user->display_name,
					'initials'     => $user->webchat_initials,
					'email'        => $user->user_email,
					'headshot'     => $headshot,
					'is_wp_user'   => true,
				);
				$participantWordPressUsers[] = (object)$participantUser;
			}
		}

		// Make sure the WP User participants are at the top of the array
		$allParticipants = array_merge($participantWordPressUsers,$participants);

		// Assign a colour number to each participant
		foreach ($allParticipants as $key => &$participant) {
			$participant->colour = $key+1;
		}

		return $allParticipants;
	}

	private function getHeadshot($user_email) {
		$headshot = null;
		update_option('show_avatars', true);
		$avatar = get_avatar($user_email, 45);
		if (!empty($avatar)) {
			preg_match("/src='(.*?)'/", $avatar, $matches);
			if(count($matches) > 1){
				$headshot = $matches[1];
			}
		}
		return $headshot;
	}

	private function sendToParticipantsViaPusher($event, $data) {
		$channel = $this->getPusherChannel(true);
		return $this->sendToPusher($channel, $event, $data);
	}

	private function sendToNonParticipantsViaPusher($event, $data) {
		$channel = $this->getPusherChannel(false);
		return $this->sendToPusher($channel, $event, $data);
	}

	private function sendToEveryoneViaPusher($event, $data) {
		$this->sendToParticipantsViaPusher($event, $data);
		$this->sendToNonParticipantsViaPusher($event, $data);
		return true;
	}

	private function sendNotificationViaPusher($event, $data) {
		$channel = $this->getPusherNotifyChannel();
		return $this->sendToPusher($channel, $event, $data);
	}

	private function sendToPusher($channel, $event, $data) {
		global $wpdb;
        $pusherStart = microtime(true);
		$pusher = new Pusher($_SERVER['PUSHER_KEY'], $_SERVER['PUSHER_SECRET'], $_SERVER['PUSHER_APPID'], true, 'http://api.pusherapp.com', '80', 3);
		try {
			$data['event'] = $event;
			$resp = $pusher->trigger($channel, $event, $data);
		} catch(Exception $e) {
			$resp = $e->getMessage();
		}
        $pusherEnd = microtime(true);

		// Also add the message to the database to enable fallback for UAs that cannot connect to pusher
		if ($resp === true) {
			$now = new DateTime('Now', new DateTimeZone('UTC'));
			$wpdb->query($wpdb->prepare('INSERT INTO '.$wpdb->prefix.'webchat_pusher SET channel=%s, event=%s, data=%s, datepushed_gmt=%s', $channel, $event, json_encode($data), $now->format('Y-m-d H:i:s')));
		}

        $pusherDuration = $pusherEnd - $pusherStart;
        $dbWriteDuration = microtime(true) - $pusherEnd;


		// Ensure data is an array to prevent array_merge() errors
		if (empty($data)) {
			$data = array();
		} elseif (!is_array($data)) {
			$data = array($data);
		}
		$logdata = array_merge($_POST, $_GET, $data);
        if (isset($logdata['html'])) unset($logdata['html']);
        $logdata['channels'] = $channel;
        $logdata['event'] = $event;
        $logdata['pusher_duration'] = $pusherDuration;
        $logdata['db_write_duration'] = $dbWriteDuration;
        $this->logger->info('Pusher curl request profiler', $logdata);

		return $resp;
	}

	function hook_init() {
		$this->plugindir  = dirname(__FILE__);
		$this->pluginfile = basename(__FILE__);
		$this->loadBrands();

		//Add custom thumbnail size options in media uploader
		add_image_size('participant-headshot', 35, 45, true);
		add_filter('image_size_names_choose', function($sizes){
			return array_merge( $sizes, array('participant-headshot' => __('Participant headshot')));
		});
	}

	private function loadBrands() {
		require_once($this->plugindir."/branding/assanka_web_chat_brand.php");
		$brandsdir = $this->plugindir."/branding/brands";
		$brands_to_load = array();

		if (stristr($_SERVER['HTTP_HOST'], 'ftalphaville.ft.com')){
			$brands_to_load[] = "marketslive";
		} else {
			$brands_to_load[] = "liveblogs";
			$brands_to_load[] = "liveqa";
		}

		foreach ($brands_to_load as $brand_dir_name) {
			$registrationfile = $brandsdir . "/" . $brand_dir_name . "/register.php";
			if (file_exists($registrationfile)) {
				include $registrationfile;
			}
		}
	}


	/**
	 * Redirect from page to live webchat session if appropriate
	 *
	 * When a page is about to display, check if it has "webchat brand" meta-option set.
	 * If it does,
	 *   - check to see if there's a session in progress for that brand.
	 *      - If there is, redirect to that post.
	 */
	function hook_wp($wp){

		// Don't run on webchat posts, unpublished posts or non-pages
		if ($this->is_webchat
			or get_post_status() != 'publish'
			or get_post_type()   != 'page') { return $wp; }

		// Don't run on non-webchat pages
		$webchat_brand = get_post_meta(get_the_ID(), 'webchat_brand', true);
		if (empty($webchat_brand)) return $wp;

		// See if we need to redirect to a live webchat
		$brand = Assanka_WebchatBrand::getForPostType($webchat_brand);
		if (!empty($brand)) {
			$this->current_webchat_brand = $brand;
		}

		if ($this->current_webchat_brand) {

			// See if there's a session in progress for this brand
			$webchat_query = new WP_Query(array(
				'post_type'      => $this->current_webchat_brand->post_type,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'posts_per_page' => 10
			));

			if ($webchat_query->have_posts()) {
				while ($webchat_query->have_posts()){
					$webchat_query->the_post();
					$is_closed = $this->currentPostIsClosed();
					$is_unlisted = get_post_meta(get_the_ID(), 'is_unlisted', $single = true);
					if (empty($is_closed) and empty($is_unlisted)) {

						// This webchat is not closed, and it's not unlisted, so it must be in progress. Redirect to the webchat session.
						ob_end_clean();
						header('Location: ' . get_permalink());
						exit();
					}
				}
			}

			// No webchats are in progress, so the static brand page will be displayed.
			// Webchat Brand pages require a special template which shows a list of closed webchats.
			$this->webchat_pagetype = 'brand_page';

			// Don't allow brand pages to be cached externally, to ensure that users refreshing the page after the chat starts will be directed to the chat page rather than seeing the brand page again.  Requires 'Assanka cache interaction' plugin to be active
			if (class_exists('Cacheability')) {
				Cacheability::setExternalExpiryTime(0);
				Cacheability::outputHeaders();
			}
		}
	}

	/**
	 * Controller for webchat templates
	 */
	function hook_single_template($single_template) {
		if (!$this->isWebchatPage()) { return $single_template; }

		$templatefilelocation = $this->current_webchat_brand->findThemeFile($this->webchat_pagetype, "web_chat_".$this->webchat_pagetype.".php");

		return $this->convertToFullPath($templatefilelocation);
	}

	public function isWebchatPage() {
		return (!empty($this->webchat_pagetype));
	}

	public function enqueueStylesheets() {

		// Styles to add to all pages
		$this->registerAndEnqueueStyle(
			"global",
			$this->getPluginURL()."/assanka_web_chat.css"
		);

		// Styles to add to webchat pages only
		if (!$this->isWebchatPage()) return;
		$this->registerAndEnqueueStyle(
			"shared",
			$this->current_webchat_brand->findThemeFile("shared", "web_chat_shared.css")
		);
		$this->registerAndEnqueueStyle(
			$this->webchat_pagetype,
			$this->current_webchat_brand->findThemeFile($this->webchat_pagetype, "web_chat_".$this->webchat_pagetype.".css")
		);

	}

	public function enqueueJavascript() {
		if (!$this->isWebchatPage()) return;

		$this->registerAndEnqueueScript(
			"shared",
			$this->current_webchat_brand->findThemeFile("shared", "web_chat_shared.js"),
			array('jquery'),
			CACHEBUSTER
		);

		$this->registerAndEnqueueScript(
			$this->webchat_pagetype,
			$this->current_webchat_brand->findThemeFile($this->webchat_pagetype, "web_chat_".$this->webchat_pagetype.".js"),
			array('jquery'),
			CACHEBUSTER
		);

		$this->registerAndEnqueueScript(
			"pusherflags",
			"/wp-content/plugins/assanka_web_chat/pusher-flags.js",
			array(),
			false,
			false
		);

		$this->registerAndEnqueueScript(
			"pusher",
			"http://js.pusher.com/2.0.5/pusher.min.js",
			array(),
			false
		);
	}

	private function registerAndEnqueueStyle($style_name, $style_location) {
		if (empty($style_name) || empty($style_location)) {
			return false;
		}

		$namespaced_style_name = $this->namespaceResource($style_name);
		wp_register_style(
			$namespaced_style_name,
			$this->convertToURL($style_location),
			false,
			CACHEBUSTER
		);
		wp_enqueue_style($namespaced_style_name);
	}

	private function registerAndEnqueueScript($script_name, $script_location, $dependencies = array('jquery'), $cachebuster = false, $infooter = true) {
		if (empty($script_name) || empty($script_location)) {
			return false;
		}

		if ($script_name != "pusher") {
			$script_name = $this->namespaceResource($script_name);
		}

		wp_register_script(
			$script_name,
			$this->convertToURL($script_location),
			$dependencies,
			$cachebuster,
			$infooter
		);
		wp_enqueue_script($script_name);
	}

	private function namespaceResource($resource_name) {
		return 'web_chat_'.$resource_name;
	}

	private function convertToURL($path) {
		if (preg_match("|^(http:/)?/|", $path)) {
			return $path;
		}

		return plugins_url()."/".basename(dirname(__FILE__))."/".$path;
	}

	private function convertToFullPath($path) {
		if (substr($path, 0, 1) == "/") {
			return $path;
		}
		return $this->plugindir."/".$path;
	}


	/**
	 * Add appropriate classes to primary navigation menu items (used for Markets Live)
	 * @param  array $sorted_menu_items Standard WordPress menu array
	 * @return array $sorted_menu_items Same menu but with appropriate classes added
	 */
	function hook_wp_nav_menu_objects($sorted_menu_items){

		// @todo: change this to (e.g.) is_closed(), because transcript is a status, not a page type.
		if ($this->webchat_pagetype == 'transcript' or empty($this->current_webchat_brand)) {
			return $sorted_menu_items;
		}

		foreach ($sorted_menu_items as &$item) {

			// Get the post id from the menu item's ['url']
			$post_id = url_to_postid($item->url);
			if (empty($post_id)) {

				// Get the post ID from the path
				if (preg_match("/(\/blog)?\/\d{4}\/\d{2}\/\d{2}\/(\d+)\//", $item->url, $matches)) {
					$post_id = $matches[2];
				}
			}
			if (empty($post_id)) continue;

			// Get the webchat_brand for that post id
			$menu_item_webchat_brand = get_post_meta($post_id, 'webchat_brand', true);

			// Check if the meta data has a connected brand, and the same brand is currently being viewed.
			if($menu_item_webchat_brand == $this->current_webchat_brand->post_type){

				// Strip the current-menu-ancestor class from any menu items
				foreach ($sorted_menu_items as &$item2) {
					if($key = array_search('current-menu-ancestor', $item2->classes)){
						unset($item2->classes[$key]);
					}
				}

				// Add the current-menu-ancestor class to the matching menu item
				$item->classes[] = 'current-menu-ancestor';
				continue;
			}
		}
		return $sorted_menu_items;
	}

	/**
	 * Add participant_token to the WordPress public query variables
	 * @param  array $vars WordPress public query variables
	 * @return array $vars WordPress public query variables
	 */
	function hook_query_vars( $vars ){
		$vars[] = "participant_token";
		return $vars;
	}

	/**
	 * Include webchat post types in the WP query.
	 */
	function hook_pre_get_posts($query){
		if(is_admin()
			or $query->is_singular()
			or !($query->is_main_query()) ) { return; }

		$post_types = array();

		// The WP query might already have a value in its post_type variable, so allow for that here.
		$query_post_type = get_query_var('post_type');
		if(!empty($query_post_type)) {
			if(is_array($query_post_type)){
				$post_types = $query_post_type;
			} else {
				$post_types[] = $query_post_type;
			}
		}

		// Add webchat brand post types to the wp query.
		$brands = Assanka_WebchatBrand::getAll();
		foreach($brands as $brand) {
			if (!in_array($brand->post_type, $post_types)) { $post_types[] = $brand->post_type; }
		}

		if (!in_array('post', $post_types)) { $post_types[] = 'post'; }
		$query->set( 'post_type', $post_types );
	}

	/**
	 * Loop through the posts array and filter out any webchat posts that have an "is_unlisted" meta-option of 1.
	 *
	 * Note: You need to manually add "is_unlisted:1" to the wp_nn_postmeta table for this to work.
	 */
	function hook_the_posts($posts, $query) {
		if (empty($posts)) return;
		if (is_singular() or $query->is_singular) return $posts;

		$newposts = array();
		foreach ($posts as $post) {

			$is_unlisted = get_post_meta($post->ID, 'is_unlisted', $single = true);
			$brand       = Assanka_WebchatBrand::getForPost($post);

			if (empty($brand) or !$is_unlisted) {
				$newposts[] = $post;
			}
		}
		return $newposts;
	}

	/**
	 * Add Participant properties to webchat posts and output JS config
	 */
	function hook_the_post( $post ) {
		if (!is_object($post) or !$this->current_webchat_brand) { return $post; }
		$post->webchat_brand_name = $this->current_webchat_brand->singular_name;
		wp_localize_script(
			$this->namespaceResource($this->webchat_pagetype),
			'webchat_config',
			$this->current_webchat_brand->getJavascriptConfig()
		);

		return $post;
	}

	/**
	 * Detecting Participants
	 *
	 * Webchats utilize the "edit_posts" WordPress capability to determine
	 * whether or not the user should be considered a Participant.
	 *
	 * Participants can also be determined by a unique token in the URL of
	 * a webchat session. If that token URL is detected and is valid, then the
	 * current WordPress user object is changed: The username, initials and
	 * capability is set.
	 *
	 * Then, because that user object has the right capability setting, the
	 * user is considered a Participant.
	 *
	 * If the token is not detected or is invalid, but the current WordPress user
	 * has the appropriate capability setting, then they're considered a Participant
	 * anyway (as is currently the case).
	 */
	private function detectParticipant() {
		global $wp;
		if (!is_array($wp->query_vars) or empty($wp->query_vars['participant_token'])) return;

		// Get all users who have the same participant_token as in the current URL
		foreach ($this->getParticipants() as $participant) {
			if ($participant->token != $wp->query_vars['participant_token']) continue;

			/**
			 * Override the user currently signed into WordPress.
			 * Their role and ID are replaced — effectively making
			 * the WordPress user appear to be signed in as the Participant;
			 * but only whilst the user is looking at this specific page.
			 */
			global $current_user;
			if(!is_object($current_user->data)) {
				$current_user->data = new stdClass();
			}
			if (!is_user_logged_in()) {
				$current_user->set_role('contributor');
			}
			$current_user->ID = $participant->user_id;
			$current_user->data->ID = $participant->user_id;
			$current_user->display_name = $participant->display_name;
			$current_user->initials = $participant->initials;
			$current_user->headshot = $participant->headshot;
			$current_user->colour = $participant->colour;
			break;
		}
	}

	/**
	 * Api to customise the title
	 * The actual decoration happens in the brand classes
	 */
	public function hook_the_title($title) {

		// See if this post has a webchat brand
		$post_webchat_brand = Assanka_WebchatBrand::getForPostType(get_post_type());
		if (!$post_webchat_brand) { return $title; }

		return $post_webchat_brand->decorateTitle($this, $title);
	}

	/**
	 * Override the "Read more" link with a "session is live" notice
	 * The actual decoration now happens in the brand classes
	 */
	public function hook_the_excerpt($excerpt) {

		// See if this post has a webchat brand
		$post_webchat_brand = Assanka_WebchatBrand::getForPostType(get_post_type());
		if (!$post_webchat_brand) { return $excerpt; }

		return $post_webchat_brand->decorateExcerpt($this, $excerpt);
	}

	public function getLozenge() {
		$post_webchat_brand = Assanka_WebchatBrand::getForPostType(get_post_type());
		if (!$post_webchat_brand) { return ''; }

		return $post_webchat_brand->getLozenge($this);
	}

	/**
	 * get_html return html for messages and the text for the key events
	 * @param  string  $direction acending/descending
	 * @param  boolean $forpermanentarchive 
	 * @return array("msg"=>the message in html format, "keyevent"=>key event text)
	 */
	private function get_html($direction = null, $forpermanentarchive = false) {
		if (!$this->isWebchatPage()) {
			throw new Exception("Webchat HTML requested for non-webchat page", 0, null);
		}

		if ($this->currentPostIsClosed()) {
			return get_the_content();
		}
		$messages = $this->getMessagesInChatBeforeNow($direction);

		$html["msg"] = '';
		foreach ($messages as $message) {
			$message["forpermanentarchive"] = (bool)$forpermanentarchive;
			$html["msg"] .= $this->formatMessage($message);
			if ($message["keyevent"]) $keypoints[] = array("id" => $message["id"], "keytext" => $message["keyevent"]);
				
		}

		$html["msg"] = $this->current_webchat_brand->formatContent($html["msg"]);

		if (isset($keypoints)) $html["keypoints"] = $this->get_keypoints_html($keypoints);
		
		return $html;
	}
	
	/**
	 * get_keypoints_html get html to display keypoints list
	 * @param  array  $keypoints id and keytext
	 * @return string
	 */
	private function get_keypoints_html ($keypoints) { 
		$key_items = '';
		foreach ($keypoints as $keypoint) 
			$key_items .= "<li id='msg-".$keypoint["id"]."'><a href='#".md5('message'.$keypoint["id"])."'>".$keypoint["keytext"]."</a></li>";

		return "<ul class='key-list'>".$key_items."</ul>"; 
	}

	private function getMessagesInChatBeforeNow($direction = null) {
		global $wpdb;

		if (!empty($direction) && $direction == 'chronological') {
			$sortdir = 'ASC';
		} else {
			$sortdir = (($this->current_webchat_brand->content_order == 'descending')?'DESC':'ASC');
		}
		$beforedate = new DateTime('now', new DateTimeZone('UTC'));
		$data = $wpdb->get_results($wpdb->prepare('SELECT * FROM '.$wpdb->prefix . 'webchat_messages WHERE post_id=%d AND blockedby_user_id IS NULL AND dateposted_gmt < %s ORDER BY dateposted_gmt '.$sortdir.', id '.$sortdir, get_the_ID(), $beforedate->format('Y-m-d H:i:s')), ARRAY_A);

		$messages = array();
		foreach ($data as $message) {
			$message['pubdate']      = new DateTime($message['dateposted_gmt'],   new DateTimeZone('GMT'));
			$message['datemodified'] = new DateTime($message['datemodified_gmt'], new DateTimeZone('GMT'));
			$messages[] = $message;
		}

		return $messages;
	}

	/**
	 * Output JavaScript to toggle webchat-on-air class
	 */
	function hook_wp_footer() {

		// See if there is an active webchat within the last 5 webchats to be published
		$postTypes = array();
		$brands = Assanka_WebchatBrand::getAll();
		foreach ($brands as $brand) {
			$postTypes[] = $brand->post_type;
		}
		$posts = get_posts(array(
			'post_type'   => $postTypes,
			'orderby'     => 'post_date',
			'order'       => 'DESC',
			'numberposts' => 5
		));

		$inprog = false;
		foreach ($posts as $post) {
			if (!$this->postIsClosed($post->ID)) {
				$inprog = true;
				break;
			}
		}
		?>
		<script>
		(function() {
			function start(data) {
				$('body').addClass('webchat-on-air');
				if (location.pathname.replace(/\/$/, '') == '/marketslive' && data.guid) location.replace(data.guid);
			};
			function end() {
				$('body').removeClass('webchat-on-air').addClass('webchat-finished');
			};
			setTimeout(function() {
				if (typeof Pusher === 'function') {
					if (!window.pusher) window.pusher = new Pusher('<?=$_SERVER['PUSHER_KEY']?>');
					var webchat_channel = window.pusher.subscribe('<?=$this->getPusherNotifyChannel()?>');
					webchat_channel.bind('start', start);
					webchat_channel.bind('end', end);
				}
			}, 5000);
			<?=($inprog)?'start();':''?>
		}());
		</script>
		<?php
	}

	/**
	 * Before publishing a post or saving it as draft: Make sure the title, slug and excerpt are appropriate.
	 */
	function hook_wp_insert_post_data( $thepost_data, $postarr ) {

		$thepost = get_post($postarr['ID']);
		$brand   = Assanka_WebchatBrand::getForPost($thepost);
		if (empty($brand)) { return $thepost_data; }

		if(!in_array($thepost_data['post_status'], array('draft','publish'))) { return $thepost_data; }

		// Title
		if(empty($thepost_data['post_title'])) {
			$thepost_data['post_title'] = $brand->singular_name . ': ' . $this->getPostDateTime($thepost)->format('l, jS F, Y');
		}

		// Slug
		if(empty($thepost_data['post_name'])
			or $thepost_data['post_name'] == $thepost_data['ID']
			or $thepost_data['post_name'] == sanitize_title($thepost_data['post_title'])) {
			$thepost_data['post_name'] = wp_unique_post_slug(
				$this->getPostDateTime($thepost)->format('Y-m-d'),
				$thepost_data['ID'],
				$thepost_data['post_status'],
				$thepost_data['post_type'],
				$thepost_data['post_parent']
			);
		}

		// Excerpt
		if (empty($thepost_data['post_excerpt'])) {
			$thepost_data['post_excerpt'] = $brand->default_excerpt;
		}

		return $thepost_data;
	}


	/**
	 * Save appropriate post meta data for webchat posts and brand pages.
	 * In WP 3.1, post_updated is called only on a save/create event; better than hooking save_post
	 */
	function hook_post_updated( $post_id ) {
 		if (!current_user_can( 'edit_post', $post_id )) { return; }

		$this->saveBrandToPostIfSubmittedAndValid($post_id);
		$this->saveParticipantsIfSubmittedAndValid($post_id);
		$this->saveMessageBylineOptionsIfSubmittedAndValid($post_id);

		$thepost = get_post($post_id);
		$brand   = Assanka_WebchatBrand::getForPost($thepost);
		if (empty($brand)) { return; }

		$islivediscussion = !$this->postIsClosed($post_id);
		$this->setInfernoConfig($post_id, $islivediscussion);

		$this->sendToEveryoneViaPusher(
			'postSaved', array(
				'title' => $thepost->post_title,
				'excerpt' => $thepost->post_excerpt,
			)
		);
	}

	private function getPostDateTime($thepost) {
		return new DateTime($thepost->post_date, timezone_open('Europe/London'));
	}

	private function saveBrandToPostIfSubmittedAndValid($post_id) {
		if ($this->aValidBrandWasSubmittedWhenSavingPost()) {
			update_post_meta($post_id, 'webchat_brand', $_POST['webchat_brand']);
		}
	}

	private function aValidBrandWasSubmittedWhenSavingPost() {
		if (!wp_verify_nonce($_POST['nonce'], plugin_basename(__FILE__))) {
			return false;
		}
		if (empty($_POST['webchat_brand'])) {
			return false;
		}

		return true;
	}

	/**
	 * The participant needs to have a user_id value, even though they do not
	 * have a corresponding WordPress User. The participant's user_id is needed
	 * for rows in the "messages" table.
	 *
	 * Originally, participants were determined by querying the messages table
	 * for all messages with a given post_id, returning a list of user_ids,
	 * which was considered to be the list of participants. (See getParticipants().)
	 *
	 * Now that participants can be added to a webchat post via WP-Admin,
	 * participants are determined differently. However, it still runs the same
	 * user ID query on the messages table.
	 *
	 * Note: If the random ID generated here happens to be the same as an existing
	 * WordPress User, it doesn't matter and won't have any effect, unless that User
	 * also happens to be a participant on the same webchat post, which is unlikely.
	 */
	private function saveParticipantsIfSubmittedAndValid($post_id) {
		if (!wp_verify_nonce($_POST['nonce'], plugin_basename(__FILE__))) return false;

		if (empty($_POST['participants'])) return false;

		$participants = array();
		foreach ($_POST['participants'] as $participant) {
			$participant = array_map('trim',$participant);

			// Remove participants marked for deletion — and delete any messages they made for this post.
			if (!empty($participant["delete_user_id"]) and is_numeric($participant["delete_user_id"])) {
				global $wpdb, $post;
				$wpdb->query($wpdb->prepare('DELETE FROM '.$wpdb->prefix . 'webchat_messages WHERE post_id=%d AND user_id=%d AND blockedby_user_id IS NULL ORDER BY dateposted_gmt', $post_id, $participant["delete_user_id"]));
				continue;
			}

			if (empty($participant["display_name"]) && empty($participant["initials"])) continue;

			if (empty($participant["user_id"])) {
				$participant["user_id"] = wp_create_user("participant-".$participant["display_name"]."-".time(), wp_generate_password());
				update_user_meta( $participant["user_id"], "is_participant", true );
			}

			// @todo: Discover why Participant tokens only work if they're numeric.
			if (empty($participant["token"])) {
				$participant["token"] = get_current_blog_id().$post_id.$participant["user_id"].time();
			}

			$participants[] = $participant;
		}

		// Details of the Participant are saved as post meta data.
		return update_post_meta($post_id, "participants", $participants);
	}

	/**
	 * Save MessageBylineOptions if submitted and valid
	 * @param  integer $post_id ID of the current post
	 */
	private function saveMessageBylineOptionsIfSubmittedAndValid($post_id) {
		if (!wp_verify_nonce($_POST['nonce'], plugin_basename(__FILE__))) return false;

		// Message bylines
		if (!empty($_POST['webchat_show_message_authornames']) && $_POST['webchat_show_message_authornames'] == 1) {
			update_post_meta($post_id, "webchat_show_message_authornames", 1);
		} else {
			delete_post_meta($post_id, "webchat_show_message_authornames");
		}

		// Headshots
		if (!empty($_POST['webchat_show_message_headshots']) && $_POST['webchat_show_message_headshots'] == 1) {
			update_post_meta($post_id, "webchat_show_message_headshots", 1);
		} else {
			delete_post_meta($post_id, "webchat_show_message_headshots");
		}
	}

	// Set the Inferno theme appropriately based on whether the post is in progress or closed
	function setInfernoConfig($post_id, $islive) {
		if (class_exists('Assanka_Inferno') and $opts = get_option('inferno_options')) {
			$ref = (empty($_SERVER['IS_LIVE'])?'staging_':'').get_current_blog_id().'_'.$post_id;

			// set most options
			$operations = array(
				'method' => 'editContent',
				'args'   => array(
					'gentime' => time(),
					'ref'     => $ref,
					'uri'     => get_permalink($post_id)
				)
			);
			$operations['args']['theme'] = ($islive) ? 'ft/marketslive' : '';

			// Add sort order
			$brand = Assanka_WebchatBrand::getForPostID($post_id);
			if (!empty($brand) and $islive) {
				$operations['args']['sort'] = 'date'.(($brand->content_order == 'descending') ? 'desc' : 'asc');
			} else {
				$operations['args']['sort'] = '';
			}

			// Sign operations
			ksort($operations['args']);
			$operations['args']['sig']   = md5(join($operations['args']).$opts['secret']);

			$req = array(
				'site'        => $opts['siteid'],
				'output'      => array('format'=>'json'),
				'thiscontent' => $ref,
				'operations'  => array($operations)
			);
			$cmd = 'curl -sL -d '.escapeshellarg(json_encode($req)).' -H "Content-Type: application/json" '.$_SERVER['INFERNO_API_URL'];

			$output = shell_exec($cmd);
		}
	}

	/**
	 * Display the Webchat options box on WP-Admin->Page->Edit/Add
	 */
	function hook_add_meta_boxes(){
		$metaBoxes = array();

		// Webchat options for webchat-brand posts
		$brand = Assanka_WebchatBrand::getForPostType(get_post_type());
		if (!empty($brand) && is_object($brand)) {
			$metaBoxes[] = array(
				'id'            => 'webchatParticipants',
				'title'         => 'Participants for this '.$brand->singular_name.' session',
				'callback'      => array($this, 'add_webchat_participant_options'),
				'post_type'     => get_post_type(),
				'context'       => 'normal',
				'priority'      => 'default',
				'callback_args' => array('brand'=>$brand),
			);

			// Add message byline options if the brand supports message bylines
			if ($brand->allowMessageBylines === true) {
				$metaBoxes[] = array(
					'id'            => 'webchatMessageBylines',
					'title'         => 'Message bylines',
					'callback'      => array($this, 'add_message_byline_options'),
					'post_type'     => get_post_type(),
					'context'       => 'normal',
					'priority'      => 'default',
					'callback_args' => array('brand'=>$brand),
				);
			}
		} else {

			// Webchat options for standard WordPress pages
			// "If you select a webchat brand, this page will automatically redirect to that brand's live webchat sessions."
			$metaBoxes[] = array(
				'id'            => 'webchatPageOptions',
				'title'         => 'Webchat: Options',
				'callback'      => array($this, 'add_webchat_options'),
				'post_type'     => 'page',
				'context'       => 'side',
				'priority'      => 'low',
				'callback_args' => NULL,
			);
		}

		foreach ($metaBoxes as $metaBox) {
			add_meta_box(
				$metaBox['id'],
				$metaBox['title'],
				$metaBox['callback'],
				$metaBox['post_type'],
				$metaBox['context'],
				$metaBox['priority'],
				$metaBox['callback_args']
			);
		}
	}

	/**
	 * Options displayed on the webchat post's WP-Admin page
	 */
	function add_webchat_participant_options($post, $metaBox) {

		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), 'nonce' );

		$brand = $metaBox['args']['brand'];
		require_once($this->plugindir."/admin/participants_meta_box.phtml");
	}

	/**
	 * These options for message bylines are displayed on the webchat post's WP-Admin page
	 */
	function add_message_byline_options($post, $metaBox) {

		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), 'nonce' );

		$brand = $metaBox['args']['brand'];
		require_once($this->plugindir."/admin/message_bylines_meta_box.phtml");
	}

	/**
	 * These options for the webchat plugin are displayed on standard WordPress pages.
	 */
	function add_webchat_options() {
		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), 'nonce' );

		// Get the selected webchat brand
		$selected_webchat_brand = get_post_meta(get_the_ID(), 'webchat_brand');
		$selected_webchat_brand = $selected_webchat_brand[0];
		?>

		<label for="webchat_brand">Webchat brand: </label>
		<select id='webchat_brand' name='webchat_brand'>
			<option>None</option>

			<?php
			$brands = Assanka_WebchatBrand::getAll();
			foreach($brands as $brand) {
				?>
				<option <?php selected( $selected_webchat_brand, $brand->post_type ); ?> value="<?php echo $brand->post_type; ?>"><?php echo $brand->singular_name; ?></option>
				<?php
			}
			?>
		</select>
		<p><span class="description">If you select a webchat brand, this page will automatically redirect to that brand's live webchat sessions.</span></p>
		<p><span class="description">If there are no sessions in progress, this page will list previous sessions.</span></p>
		<?
	}

	/**
	 * Display the Webchat Participant options on WP-Admin->User->Edit/Add
	 */
	function hook_edit_user_profile() {
		global $user_id;
		if (!current_user_can('edit_user', $user_id)) return;

		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), 'nonce' );

		?>
		<h3>Webchat: Participant Details</h3>
		<table class="form-table">
			<tr>
				<th>
					<label for="webchat_initials">Participant initials</label>
				</th>
				<td>
					<input id="webchat_initials" type="text" name="webchat_initials" value="<?php echo get_user_meta($user_id, 'webchat_initials', true); ?>" /><br />
					<span class="description">Displayed next to the participant's entries in webchats.</span>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save appropriate user meta data for webchat participants.
	 */
	function hook_edit_user_profile_save() {
		global $user_id;
		if (!current_user_can('edit_user', $user_id) or !wp_verify_nonce( $_POST['nonce'], plugin_basename( __FILE__ ))) return;

		if (!empty($_POST['webchat_initials'])) {
			update_usermeta($user_id, 'webchat_initials', trim($_POST['webchat_initials']));
		}
	}

	/**
	 * Add webchat CSS styles to the tiny MCE editor
	 */
	function hook_mce_css($mce_css){

		// Only load webchat-specific styles on webchat pages
		$brand = Assanka_WebchatBrand::getForPostType(get_post_type());
		if (empty($brand)) {
			return $mce_css;
		} else {
			$this->current_webchat_brand = $brand;
		}

		// Add this plugin's CSS to the existing MCS css array
		if (!empty($mce_css)) { $mce_css .= ','; }
		$mce_css .= plugins_url($this->current_webchat_brand->themefiles['shared']->stylesheet, __FILE__ );
		return $mce_css;
	}

	/**
	 * Enqueue javascript for appropriate metaboxes in admin
	 */
	function hook_admin_enqueue_scripts() {
		$brand = Assanka_WebchatBrand::getForPostType(get_post_type());
		if (!empty($brand) && is_object($brand)) {

			// Participants
			wp_register_script( 'participants_meta_box', plugins_url( 'admin/participants_meta_box.js', __FILE__ ), null, CACHEBUSTER);
			wp_enqueue_script( 'participants_meta_box' );
			wp_enqueue_style('participants_meta_box', plugins_url('admin/participants_meta_box.css', __FILE__), false, CACHEBUSTER);

			// Message bylines
			if ($brand->allowMessageBylines === true) {
				wp_register_script( 'message_bylines', plugins_url( 'admin/message_bylines.js', __FILE__ ), null, CACHEBUSTER);
				wp_enqueue_script( 'message_bylines' );
				wp_enqueue_style('message_bylines', plugins_url('admin/message_bylines.css', __FILE__), false, CACHEBUSTER);
			}

			// Webchat-specific styles, just for WP Admin
			wp_enqueue_style('webchat_admin', plugins_url('admin/webchat_admin.css', __FILE__), false, CACHEBUSTER);
		}
	}

	/**
	 * Add admin menu pages
	 */
	function hook_admin_menu() {
		$brands = Assanka_WebchatBrand::getAll();
		foreach($brands as $brand) {
			add_submenu_page(
				'edit.php?post_type=' . $brand->post_type,   // $parent_slug
				'System messages: ' . $brand->singular_name, // $page_title
				'System messages',                           // $menu_title
				'manage_options',                            // $capability
				'system_messages',                           // $menu_slug
				array($this, 'options_page')                 // $function
			);
		}

		/**
		 * When new brands are added, appropriate rewrite rules need to be created.
		 * This plugin handles it on activation/deactivation, but it's not acceptable
		 * to require the WordPress Admin to manually deactivate and reactivate
		 * this plugin. But flushing the rewrite rules slows down page load, so
		 * check whether it's required when the WordPress admin menu is loaded.
		 */
		global $wp_rewrite;
		foreach ($brands as $brand) {
			$rewrite_rules = array_keys($wp_rewrite->wp_rewrite_rules());
			$slug = $brand->slug;
			$matches = array_filter($rewrite_rules, function($var) use ($slug){
				return stripos($var, $slug) !== false;
			});
			if (count($matches) <= 0) {
				flush_rewrite_rules();
				$this->logger->info('Flushed rewrite rules.', array($brand->singular_name,$brand->slug));
			}
		}

		// Adds webchat menu into the Settings
		add_options_page(
			'Webchats',                  // $page_title
			'Webchats',                  // $menu_title
			'manage_options',             // $capability
			'web_chats',                  // $menu_slug
			array($this, 'settings_page') // $function
		);
	}

	private function getNameOfActiveWordpressTheme() {

		// @TODO:WV:20121220:Current version of Wordpress has wp_get_theme() for this
		// but the version in use for this project does not provide that function.
		// So use get_stylesheet instead, which will always return the name of the current
		// theme unless a child-theme is in use; see http://codex.wordpress.org/Function_Reference/get_stylesheet.

		return get_stylesheet();
	}


	function hook_admin_notices() {
		$currentscreen = get_current_screen();

		if ($currentscreen->action == "add" and $currentscreen->base == "post" and !empty($currentscreen->post_type)) {
			$brand = Assanka_WebchatBrand::getForPostType($currentscreen->post_type);
			if (!empty($brand)) {
				$activeWPTheme = $this->getNameOfActiveWordpressTheme();
				$validWPThemes = $brand->validWordpressThemes;
				if (!in_array($activeWPTheme, $validWPThemes)) {
					echo "<div class='error' style='font-size:3em; line-height:1.5em;'>Warning: the currently enabled Wordpress theme, <em>".htmlspecialchars($activeWPTheme)."</em> is not valid for this webchat brand ('".htmlspecialchars($brand->plural_name)."') which requires ".((count($validWPThemes) == 1)?("the theme <em>".htmlspecialchars($validWPThemes[0])."</em>"):("one of the following themes: {".htmlspecialchars(join(", ", $validWPThemes))."}")).".  It may not have an acceptable appearance when viewed.</div>";
				}
			}
		}
	}

	/**
	 * Register settings. Add the settings section, and settings fields
	 */
	function hook_admin_init() {
		// Handle Settings > Webchats form submission first:
		$this->handle_settings_page_save();

		if (empty($_GET['post_type'])) return;

		// Get the current brand
		$this->current_webchat_brand = Assanka_WebchatBrand::getForPostType($_GET['post_type']);

		// If the form's been submitted, process.
		if ( !empty($_POST) and !empty($_POST['webchat_options']['system_messages_array']) ) {
			global $wpdb;
			foreach ($_POST['webchat_options']['system_messages_array'] as $key => $system_message) {
				if ($key == 'new') {
					if (!empty($system_message['keyword']) and !empty($system_message['message'])) {
						$sql  = 'INSERT INTO ' . $wpdb->prefix . 'webchat_systemmessages (brand, keyword, message) ';
						$sql .= 'VALUES ("'. $this->current_webchat_brand->slug .'", "'. $system_message['keyword'] .'", "'. $system_message['message'] .'");';
					}
				} elseif(is_numeric($key)) {
					if (empty($system_message['keyword']) or empty($system_message['message']) or !empty($system_message['deleteme'])) {
						$sql = 'DELETE FROM ' . $wpdb->prefix . 'webchat_systemmessages WHERE `id` = "'. $key .'"';
					} else {
						$sql  = 'REPLACE INTO ' . $wpdb->prefix . 'webchat_systemmessages (id, brand, keyword, message) ';
						$sql .= 'VALUES ("'. $key .'", "'. $this->current_webchat_brand->slug .'", "'. $system_message['keyword'] .'", "'. $system_message['message'] .'");';
					}
				}
				$wpdb->query($sql);
				$this->notice = 'Update successful.';
			}
		}

		// Site options
		add_settings_section('webchat_system_messages_options', null, array($this, 'description_system_messages_options'),  __FILE__);
		add_settings_field('system_messages_array',  '', array($this, 'setting_system_messages_array'),  __FILE__, 'webchat_system_messages_options');
	}

	/**
	 * Admin page for system messages.
	 */
	function options_page() {
		?>
		<div class="wrap">
			<div class="icon32" id="icon-options-general"><br></div>
			<h2><?php echo $this->current_webchat_brand->singular_name; ?>: System messages</h2>
			<form id="webchat-form" method="post" style="position: relative;">
				<?php settings_fields('webchat_options'); ?>
				<?php do_settings_sections(__FILE__); ?>
			</form>
		</div>
	<?php
	}

	/**
	 * Handles form submission for Settings > Webchats
	 */
	public function handle_settings_page_save() {

		if (isset($_POST['web_chat_settings']) && $_POST['web_chat_settings'] === 'saved') {

			// Reset the result flag
			unset ($_GET['result']);

			// Handle Livefyre custom script URL
			if (isset($_POST['webchat_livefyre_script'])) {

				// Validate Livefyre custom script URL
				$webchat_livefyre_script = trim($_POST['webchat_livefyre_script']);
				if ($webchat_livefyre_script != '' && filter_var($webchat_livefyre_script, FILTER_VALIDATE_URL) == false) {

					// Build redirect URL
					$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
					$_GET['result'] = 'webchat_livefyre_script_error';

					// Redirect to improve UX when user reloads the page
					wp_redirect( $path . '?' . build_query($_GET) );
					exit;
				}

				// Save Livefyre custom script URL
				if ($webchat_livefyre_script == '') {
					delete_option('webchat_livefyre_script');
				} else {
					update_option('webchat_livefyre_script', $webchat_livefyre_script);
				}
			}

			// Save banner settings
			update_option(
				'webchats_show_transcript_banner',
				(int)(isset($_POST['webchat_show_transcript_banner']) && $_POST['webchat_show_transcript_banner'] === '1')
			);
			update_option(
				'webchats_use_comingsoon_default',
				(int)(isset($_POST['webchat_use_comingsoon']) && $_POST['webchat_use_comingsoon'] === '1')
			);

			// Build redirect URL
			$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
			$_GET['result'] = 'saved';

			// Redirect to improve UX when user reloads the page
			wp_redirect( $path . '?' . build_query($_GET) );
			exit;
		}

		if (isset($_GET['result']) && $_GET['result'] === 'saved') {
			$this->notice .= 'Settings saved.';
		} elseif (isset($_GET['result']) && $_GET['result'] === 'webchat_livefyre_script_error') {
			$this->notice = 'Error: Livefyre custom script must be a valid URL.';
		}
	}

	/**
	 * Settings page for system messages.
	 */
	public function settings_page() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		$show_transcript_banner = get_option( 'webchats_show_transcript_banner', true);
		$livefyre_script = get_option('webchat_livefyre_script');
		$use_comingsoon = get_option( 'webchats_use_comingsoon_default', false);

		?>
		<div class="wrap">
			<div class="icon32" id="icon-options-general"><br></div>
			<h2>Webchat Settings</h2>

			<?php if(!empty($this->notice)): ?>
				<div id="setting-error-settings_updated" class="updated settings-error"><p><strong><?php echo $this->notice; ?></strong></p></div>
			<?php endif; ?>

			<form name="webchat-settings-form" method="post" action="">
				<h3>Webchat Banner</h3>
				<div style="margin-left:20px;">
					<p>
						If enabled, a banner is inserted above the excerpt of each closed webchat on index pages.<br />
						If a majority of your blog's posts are webchats, you might want to disable this option, as it'll be too repetitive.
					</p>
					<p>
						<img src="/wp-content/plugins/assanka_web_chat/img/transcript-banner-example.png" alt="Banner example" title="Banner example"/>
					</p>
					<p>
						<input type="checkbox" name="webchat_show_transcript_banner" id="webchat_show_transcript_banner" value="1" <?php if ( $show_transcript_banner ) { echo 'checked'; } ?>/>
						<label for="webchat_show_transcript_banner">Show Banner for Closed Webchats</label>
					</p>
				</div>

				<h3>Livefyre</h3>
				<div style="margin-left:20px;">
					<p>
						If the implementation requires a custom Livefyre script to be used on <em>in progress pages</em>, you should specify it here.<br />
						It will be used instead of the network-wide Livefyre script. If left empty, the newtwork-wide Livefyre script will be used.<br />
						<strong>This is currently only required by Markets Live.</strong>
					</p>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><label for="webchat_livefyre_script">Livefyre custom script URL</label></th>
							<td>
								<input type="text" name="webchat_livefyre_script" id="webchat_livefyre_script" class="large-text" value="<?php echo $livefyre_script; ?>"/>
								<span class="description">E.g. <code>http://www.ft-static.com/sp/prod/comments/comment-client/1.0.0/long/marketsLiveIntegration.min.js</code></span>
							</td>
						</tr>
					</table>
				</div>
				<h3>Initial status on Publish</h3>
				<div style="margin-left:20px;">
					<p>
						If enabled, all Webchat sessions will start with the "Coming Soon" status checked by default
						.<br />
						This option can be changed for each webchat session individually.
					</p>
					<p>
						<input type="checkbox" name="webchat_use_comingsoon" id="webchat_use_comingsoon" value="1" <?php if ( $use_comingsoon ) { echo 'checked'; } ?>/>
						<label for="webchat_use_comingsoon">Preset "Coming Soon" status for all new Webchats by default</label>
					</p>
				</div>
				<p class="submit">
					<input type="hidden" name="web_chat_settings" value="saved" />
					<input type="submit" value="Save Changes" id="submit" class="button-primary" name="submit"/>
				</p>
			</form>
		</div>
	<?php
	}

	/**
	 * Section: System messages options
	 */
	function description_system_messages_options() {
		?>

		<?php if(!empty($this->notice)): ?>
		<div id="message" class="updated below-h2"><p><?php echo $this->notice; ?></p></div>
		<?php endif; ?>

		<div class="admin-explanation">
			<p>During a live webchat, system messages are displayed automatically when they detect their keywords.</p>
			<center>
				<img src="/wp-content/plugins/assanka_web_chat/img/system-message-example.png" />
				<br />
				<span class="description">Above: In the first row, the keyword "Pearson" automatically triggered its corresponding system message in the second row.</span>
			</center>
		</div>
		<br />
		<?php
	}

	function setting_system_messages_array() {

		// Include a row for adding new system messages
		?>
		<tr colspan="2"><td><strong>Add a new system message</strong></td></th>
		<tr class="system-message" id="system-message-new">
			<td style="padding: 0;">
				<table>
					<tr valign="top">
						<th scope="row"><label for="title">Keyword</label></th>
						<td>
							<input class="regular-text" type="text" name="webchat_options[system_messages_array][new][keyword]" value=""> * Required; case-insensitive
							<p>
								e.g: <span class="description">Pearson</span>
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="title">Message</label></th>
						<td>
							<input class="regular-text" style="width: 950px;" type="text" name="webchat_options[system_messages_array][new][message]" value=""> * Required
							<p>e.g: <span class="description">Pearson plc is the parent company of the Financial Times, publisher of FT Alphaville.</span></p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr colspan="2">
			<td>
				<p class="submit">
					<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save'); ?>" />
				</p>
			</td>
		</tr>

		<?
		global $wpdb;
		$system_messages = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."webchat_systemmessages WHERE brand='".$this->current_webchat_brand->slug."' ORDER BY `keyword` ASC");
		?>

		<?php if(!empty($system_messages)): ?>
		<tr colspan="2"><td><strong>Update existing system messages</strong></td></th>
		<tr>
			<td>
				<table>
					<tr>
						<th>Delete</th>
						<th>Keyword</th>
						<th>Message</th>
					</tr>
					<?php foreach ($system_messages as $system_message): ?>
					<tr>
						<td>
							<input type="checkbox" name="webchat_options[system_messages_array][<?php echo $system_message->id; ?>][deleteme]" id="deleteme-<?php echo $key; ?>" />
						</td>
						<td>
							<input class="regular-text keyword" style="width: 120px;" type="text" name="webchat_options[system_messages_array][<?php echo $system_message->id; ?>][keyword]" id="keyword-<?php echo $key; ?>" value="<?php echo esc_attr($system_message->keyword); ?>">
						</td>
						<td>
							<input class="regular-text message" style="width: 950px;" type="text" name="webchat_options[system_messages_array][<?php echo $system_message->id; ?>][message]" id="message-<?php echo $key; ?>" value="<?php echo esc_attr($system_message->message); ?>">
						</td>
					</tr>
					<?php endforeach; ?>
				</table>
			</td>
		</tr>
		<tr colspan="2">
			<td>
				<p class="submit">
					<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Update'); ?>" />
				</p>
			</td>
		</tr>
		<?php
		endif;
	}

	/**
	 * Don't allow this plugin to be network activated, because network activation does not trigger the activation hooks.
	 */
	function hook_activate_plugin($plugin, $network_wide){
		if($network_wide and stristr($plugin, 'assanka_web_chat')) {
			wp_die('<h2>Sorry.</h2> <p>Assanka Webchat cannot be network-activated. You can activate this plugin only on a per-blog basis.</p>');
		}
	}

	/**
	 * Create sql table for webchat messages on plugin activation
	 */
	function webchat_db_install() {
		global $wpdb;

		$sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "webchat_messages (
			`id` mediumint(9) unsigned NOT NULL AUTO_INCREMENT,
			`user_id` mediumint(9) unsigned NULL,
			`post_id` mediumint(9) unsigned NOT NULL,
			`msgtype` ENUM('normal','blockquote','price','sysmsg','separator') NOT NULL DEFAULT 'normal',
			`msgtext` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
			`keyevent` TEXT DEFAULT NULL,
			`dateposted_gmt` DATETIME NOT NULL,
			`datemodified_gmt` DATETIME NOT NULL,
			`blockedby_user_id` MEDIUMINT(8) UNSIGNED DEFAULT NULL,
			PRIMARY KEY (`id`),
			KEY `session` (`post_id`,`id`)
			) ENGINE=INNODB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1;";

		$sql .= "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "webchat_systemmessages (
			`id` mediumint(9) unsigned NOT NULL AUTO_INCREMENT,
			`brand` VARCHAR(20) NULL,
			`keyword` VARCHAR(30) NOT NULL,
			`message` TEXT NOT NULL,
			PRIMARY KEY (`id`)
			) ENGINE=MYISAM DEFAULT CHARSET=utf8;";

		$sql .= "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "webchat_pusher (
			`id` mediumint(9) unsigned NOT NULL AUTO_INCREMENT,
			`channel` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			`event` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			`data` text COLLATE utf8_unicode_ci NOT NULL,
			`datepushed_gmt` datetime NOT NULL,
			PRIMARY KEY (`id`),
			 KEY `datepushed_gmt` (`datepushed_gmt`),
			 KEY `channel-datepushed_gmt` (`channel`,`datepushed_gmt`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

		$sql .= 'INSERT INTO ' . $wpdb->prefix . 'webchat_systemmessages (brand, keyword, message) VALUES ("marketslive", "Pearson", "Pearson plc is the parent company of the Financial Times, publisher of FT Alphaville."), ("marketslive", "crock", "Readers may also know this former bank as Northern Rock."), ("marketslive", "assanka", "Cracking little software shop who built FT Alphaville"), ("marketslive", "goo", "Gold Oil - little resources play, with assets in Peru, Colombia and Cuba. Not for the faint-hearted."), ("marketslive", "Draaismaland", "Draaismaland - a warm and happy place, home of the former Super Bull, Teun Draaisma of Morgan Stanley. Sadly, it turned out to be make-believe"), ("marketslive", "southampton", "Paul Murphy was raised in Portsmouth and tends to be abusive of anything Southampton-related"), ("marketslive", "misys", "Strange software outfit, seemingly controlled by US investor ValueAct Capital."), ("marketslive", "feltcollar", "A felt is just a PR man/woman, so don\'t get too excited. Type that wears a felt collar, rather than likely to have their collar felt."), ("marketslive", "Jeff Randall", "Jeff Randall, the Telegraph\'s Editor-at-Large is available for speaking engagements through The Gordon Poole Agency, the UK\'s premier talent and speaker bureau. Fee group: Â£5k - Â£10k."), ("marketslive", "China Take", "The Truth! Unvarnished. The price of rice always falls. Shanghai investors do not sell stocks. Torch protestors are vile."), ("marketslive", "traitor stock", "Shire recently announced that it is to reorganise itself as an Irish company for tax purposes, registered in Jersey. Murphy is routinely critical of those who successfully avoid tax - possibly because he has never managed to avoid any himself."), ("marketslive", "Portacabin", "Portacabin Ltd - a portable building hire firm based in New Zealand. No relation to Portakabin, the UK\'s leading supplier of re-locatable modular building and occasional sender of irritating letters to P Murphy"), ("marketslive", "raw", "RAW is market chatter - information that has not been formally tested through traditional journalistic channels (PRs etc\). The story might be complete rubbish, but if we believe there is some substance to it we will say so.  Either way, Reader Beware."), ("marketslive", "informa", "We don\'t know what\'s going on. The original source that detailed the Providence approach for Informa will not talk to us at present. If you own the shares and are worried that the bid will fail, sell the shares and stop worrying."), ("marketslive", "prty", "Muppet stock. PartyGaming would be a penny dreadful, but for a share consolidation."), ("marketslive", "pestowire", "Top News from Top Sources. The BBC\'s Business Editor, Robert Peston, has played in important role keeping the British public fully informed during these difficult times."), ("marketslive", "SouthEastern", "Is this the worst train operator in Britain? It\'s got competition, sure, but the pitiful way it responded to recent weather makes it a scandal in our book."), ("marketslive", "ZAP", "Warning to rude and abusive commenters - your ability to comment will be terminated immediately and permanently, without warning. Henceforth, FTAlphaville has instituted a One Strike and You Are Out policy. We\'ve had enough. We are going to clean up these pixels once and for all."), ("marketslive", "GKP:LSE", "The next supermajor, potentially sitting on 60bn barrels of oil in Kurdistan. Loved by muppets across the globe."), ("marketslive", "Ocado", "An internet food retailer that many believe is the second coming of Webvan. Loss making yet valued at close to £1bn on flotation."), ("marketslive", "Muppet", "A term of endearment used to describe BB share promoters on FT Alphaville. "), ("marketslive", "Muppet Alpha", "FT Alpha\'s fantasy investment portfolio. We employ a modifed version of cartoonist Scott Adams\'s bet on the bad guys for stock selection. "), ("marketslive", "EGU:LSE", "WARNING! Crowded long. Every hedge fund in London owns this Greek gold play, even though it\'s just sold to Qatar for a disappointing premium. In the words of one analyst: \"Not the outcome that we believe the majority of shareholders would have preferred.\""), ("marketslive", "Frank Timis", "The world\'s greatest living natural resources entrepreneur. He also does a lot of good work for charity. Known to like a vodka. "), ("marketslive", "Lorcan Roche Kelly", "Ireland\'s \"premier central bank watcher\", according to no less an authority than The Irish Independent."), ("marketslive", "Webvan 2.0", "Our name for Ocado. The internet grocer with customer service so good that it will eventually result in bankruptcy."), ("marketslive", "Milky", "(@Milky: yellow.\)");';

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		add_option('webchat_db_version', '2.0');
	}

	/**
	 * Delete sql table for webchat messages on plugin deactivation
	 */
	function webchat_db_uninstall() {
		global $wpdb;

		// Using $wpdb->query here as dbDelta() does not support DROP TABLE.
		$wpdb->query('DROP TABLE ' . $wpdb->prefix . 'webchat_messages');
		$wpdb->query('DROP TABLE ' . $wpdb->prefix . 'webchat_systemmessages');
		delete_option('webchat_db_version');
	}

	function webchat_flush_rewrite_rules() {
		delete_option('rewrite_rules');
	}

	/**
	 * Getter for the Webchat Page Type
	 * @return string
	 */
	public function getWebchatPagetype() {
		return $this->webchat_pagetype;
	}
}
$assanka_webchat = new Assanka_Webchat();
