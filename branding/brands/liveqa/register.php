<?php
/**
 * Register the configuration settings for the "Live Q&A" brand.
 * Only add methods here to overload those in the parent class.
 */
class Assanka_WebchatBrand_Liveqa extends Assanka_WebchatBrand {
	public $singular_name                      = 'Live Q&A';
	public $plural_name                        = 'Live Q&A';
	public $post_type                          = 'webchat-live-qa';
	public $slug                               = 'liveqa';
	public $default_excerpt                    = 'Live Q&A from FT.com';
	public $author_name_style                  = 'full';
	public $fixed_height                       = false;
	public $content_order                      = 'ascending';
	public $alloweditanddeletepreviousmessages = true;
	public $closecommentingonendofchat         = false;
	public $validWordpressThemes               = array("wrapper-falcon");
	public $initial_polling_wait_time          = 600;
	public $poll_interval                      = 300;
	public $requireparticipantinitials         = false;
	public $allowparticipantheadshots          = true;
	public $taxonomies                         = array('post_tag', 'category');
	public $connection_notification            = "Instant messaging is on. Updates appear automatically at the bottom of this page.";
	public $insertkeytext                      = "Insert key event...";
}
$brand = new Assanka_WebchatBrand_Liveqa(dirname(__FILE__));
Assanka_WebchatBrand::registerBrand($brand);
