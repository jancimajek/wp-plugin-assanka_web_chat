var PUSHER_DISABLE_FLASH = true;
//if (location.search.indexOf('sockjs=1') != -1 || (navigator.userAgent.indexOf('MSIE') != -1 && location.search.indexOf('sockjs=0') == -1)) {
if (location.search.indexOf('sockjs=0') != -1) {
	PUSHER_DISABLE_FLASH = false;
}