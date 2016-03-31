<?php
/**
 * Register the configuration settings for the "Live Blogs" brand.
 * Only add methods here to overload those in the parent class.
 */
class Assanka_WebchatBrand_Liveblogs extends Assanka_WebchatBrand {
	public $singular_name                      = 'Live Blog';
	public $plural_name                        = 'Live Blogs';
	public $post_type                          = 'webchat-live-blogs';
	public $slug                               = 'liveblogs';
	public $default_excerpt                    = 'A live blog from FT.com';
	public $author_name_style                  = 'full';
	public $fixed_height                       = false;
	public $content_order                      = 'descending';
	public $alloweditanddeletepreviousmessages = true;
	public $validWordpressThemes               = array("wrapper-falcon");
	public $initial_polling_wait_time          = 600;
	public $poll_interval                      = 300;
	public $requireparticipantinitials         = false;
	public $allowparticipantheadshots          = true;
	public $taxonomies                         = array('post_tag', 'category');
	public $connection_notification            = null;
	public $insertkeytext                     = "Insert key event...";
}
$brand = new Assanka_WebchatBrand_Liveblogs(dirname(__FILE__));
Assanka_WebchatBrand::registerBrand($brand);
