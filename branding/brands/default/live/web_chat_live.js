var InfernoConfig = InfernoConfig || {};

var md5=(function(){function e(e,t){var o=e[0],u=e[1],a=e[2],f=e[3];o=n(o,u,a,f,t[0],7,-680876936);f=n(f,o,u,a,t[1],
12,-389564586);a=n(a,f,o,u,t[2],17,606105819);u=n(u,a,f,o,t[3],22,-1044525330);o=n(o,u,a,f,t[4],7,-176418897);f=n(f,o,u,a,t[5],
12,1200080426);a=n(a,f,o,u,t[6],17,-1473231341);u=n(u,a,f,o,t[7],22,-45705983);o=n(o,u,a,f,t[8],7,1770035416);f=n(f,o,u,a,t[9],
12,-1958414417);a=n(a,f,o,u,t[10],17,-42063);u=n(u,a,f,o,t[11],22,-1990404162);o=n(o,u,a,f,t[12],7,1804603682);f=n(f,o,u,a,t[13],
12,-40341101);a=n(a,f,o,u,t[14],17,-1502002290);u=n(u,a,f,o,t[15],22,1236535329);o=r(o,u,a,f,t[1],5,-165796510);f=r(f,o,u,a,t[6],
9,-1069501632);a=r(a,f,o,u,t[11],14,643717713);u=r(u,a,f,o,t[0],20,-373897302);o=r(o,u,a,f,t[5],5,-701558691);f=r(f,o,u,a,t[10],
9,38016083);a=r(a,f,o,u,t[15],14,-660478335);u=r(u,a,f,o,t[4],20,-405537848);o=r(o,u,a,f,t[9],5,568446438);f=r(f,o,u,a,t[14],
9,-1019803690);a=r(a,f,o,u,t[3],14,-187363961);u=r(u,a,f,o,t[8],20,1163531501);o=r(o,u,a,f,t[13],5,-1444681467);f=r(f,o,u,a,t[2],
9,-51403784);a=r(a,f,o,u,t[7],14,1735328473);u=r(u,a,f,o,t[12],20,-1926607734);o=i(o,u,a,f,t[5],4,-378558);f=i(f,o,u,a,t[8],
11,-2022574463);a=i(a,f,o,u,t[11],16,1839030562);u=i(u,a,f,o,t[14],23,-35309556);o=i(o,u,a,f,t[1],4,-1530992060);f=i(f,o,u,a,t[4],
11,1272893353);a=i(a,f,o,u,t[7],16,-155497632);u=i(u,a,f,o,t[10],23,-1094730640);o=i(o,u,a,f,t[13],4,681279174);f=i(f,o,u,a,t[0],
11,-358537222);a=i(a,f,o,u,t[3],16,-722521979);u=i(u,a,f,o,t[6],23,76029189);o=i(o,u,a,f,t[9],4,-640364487);f=i(f,o,u,a,t[12],
11,-421815835);a=i(a,f,o,u,t[15],16,530742520);u=i(u,a,f,o,t[2],23,-995338651);o=s(o,u,a,f,t[0],6,-198630844);f=s(f,o,u,a,t[7],
10,1126891415);a=s(a,f,o,u,t[14],15,-1416354905);u=s(u,a,f,o,t[5],21,-57434055);o=s(o,u,a,f,t[12],6,1700485571);f=s(f,o,u,a,t[3],
10,-1894986606);a=s(a,f,o,u,t[10],15,-1051523);u=s(u,a,f,o,t[1],21,-2054922799);o=s(o,u,a,f,t[8],6,1873313359);f=s(f,o,u,a,t[15],
10,-30611744);a=s(a,f,o,u,t[6],15,-1560198380);u=s(u,a,f,o,t[13],21,1309151649);o=s(o,u,a,f,t[4],6,-145523070);f=s(f,o,u,a,t[11],
10,-1120210379);a=s(a,f,o,u,t[2],15,718787259);u=s(u,a,f,o,t[9],21,-343485551);e[0]=m(o,e[0]);e[1]=m(u,e[1]);e[2]=m(a,e[2]);e[3]=m(f,e[3])}
function t(e,t,n,r,i,s){t=m(m(t,e),m(r,s));return m(t<<i|t>>>32-i,n)}function n(e,n,r,i,s,o,u){return t(n&r|~n&i,e,n,s,o,u)}
function r(e,n,r,i,s,o,u){return t(n&i|r&~i,e,n,s,o,u)}function i(e,n,r,i,s,o,u){return t(n^r^i,e,n,s,o,u)}
function s(e,n,r,i,s,o,u){return t(r^(n|~i),e,n,s,o,u)}function o(t){var n=t.length,r=[1732584193,-271733879,-1732584194,271733878],i;
for(i=64;i<=t.length;i+=64){e(r,u(t.substring(i-64,i)))}t=t.substring(i-64);var s=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
for(i=0;i<t.length;i++)s[i>>2]|=t.charCodeAt(i)<<(i%4<<3);s[i>>2]|=128<<(i%4<<3);if(i>55){e(r,s);for(i=0;i<16;i++)s[i]=0}s[14]=n*8;e(r,s);return r}
function u(e){var t=[],n;for(n=0;n<64;n+=4){t[n>>2]=e.charCodeAt(n)+(e.charCodeAt(n+1)<<8)+(e.charCodeAt(n+2)<<16)+(e.charCodeAt(n+3)<<24)}return t}
function c(e){var t="",n=0;for(;n<4;n++)t+=a[e>>n*8+4&15]+a[e>>n*8&15];return t}
function h(e){for(var t=0;t<e.length;t++)e[t]=c(e[t]);return e.join("")}
function d(e){return h(o(unescape(encodeURIComponent(e))))}
function m(e,t){return e+t&4294967295}var a="0123456789abcdef".split("");return d})();

(function($) {

	// Cache DOM elements
	var chatsession, newmessagefield, chatwin, commentspanel, btnsend, content_order;

	var cfg       = webchat_config;
	var servertimeoffset = 0;
	var	blockgrace    = 5;
	var lastpusherevent = null;
	var polltimer    = null;
	var receivetimer   = null;
	var pollchannel   = '';

	// Default content order is descending, like a normal blog post.
	if (typeof cfg.content_order != 'undefined' && cfg.content_order) {
		content_order = cfg.content_order;
	} else {
		content_order = 'descending';
	}

	var scroller = (function() {

		var windows = {
			comments: {
				scrollpending:false,
				obj:null,
				itemtag:'li',
				timestampattr:'sortindex'
			},
			chat: {
				scrollpending:false,
				obj:null,
				itemtag:'div.msg',
				timestampattr:'data-timestamp'
			}
		},
		scrollblockers = {},          // Whether to react to onscroll events - only if empty
		scrolldetection = false,      // Whether to react to onscroll events
		scrolltimer = null,           // Timer reference for scroll action
		animating = false,            // Whether a scroll animation is in progress
		detecttimer = null;

		// Before making a change to the content of an auto-scrolling window, preContentChange should be called to check the current scroll position. If the scroll position is near the bottom of the window, then the window will be scheduled to be scrolled after the content is added (regardless of the length of the new content).
		function preContentChange (win) {
			var w = windows[win].obj;
			if (!w) return;
			if (cfg.fixed_height) {
				var pos = w.get(0).scrollTop+w.height();
				var max = w.get(0).scrollHeight;
				if (pos > (max-80)) windows[win].scrollpending = true;
			} else {

				/**
				 * Detect whether or not the user is currently reading content that's "above the fold".
				 * That is, they're reading from the beginning of the content,
				 * and the bottom of the content is off the bottom of the screen.
				 */
				var screenBottom, lowestMessage = $('.chat-panel .chat .normal').last();
				if (lowestMessage.offset() !== null) {
					screenBottom = $(window).height();
					lowestMessage.relativeTop = lowestMessage.offset().top - $(window).scrollTop();
					lowestMessage.relativeBottom = lowestMessage.relativeTop + lowestMessage.height() - screenBottom;

					if (lowestMessage.relativeBottom < 0) {

						// The bottom of the lowest message is in view, so assume the reader has caught up.
						// If they've caught up, then auto-scroll so they can see the latest message.
						// Only auto scroll if the window's not scrolled to the top.
						if ($(window).scrollTop() > 100) {
							windows[win].scrollpending = true;
						}
					}
				}
			}
			disableScrollDetection('contentchange');
		}

		// After a content change to an auto-scrolling window, scroll the window to the bottom if a scroll is pending
		function postContentChange(win) {
			if (windows[win].scrollpending) scroll(win);
			enableScrollDetection('contentchange');
		}

		// Shortcut to add content to a window and then (if appropriate) scroll it
		function appendAndScroll(win, content) {
			addContentAndScroll(win, content);
		}

		function prependAndScroll(win, content) {
			addContentAndScroll(win, content, true);
		}

		function addContentAndScroll(win, content, atstart) {

			preContentChange(win);
			content = $(content);

			addParticipantUI(content);

			var obj = windows[win].obj;
			if (typeof atstart != "undefined" && atstart) {
				obj.prepend(content);
			} else {
				obj.append(content);
			}

			postContentChange(win);

			if ($(content).find('img.picture').length) {
				preContentChange(win);
				$(content).find('img.picture').bind('load', function() { postContentChange(win); });
			}

		}

		// Scroll a window, and prevent onscroll events from firing during the scroll
		function scroll(win) {
			if (typeof windows[win].obj.get(0) == "undefined") return;
			disableScrollDetection('autoscroll');
			if (content_order == 'descending') {

				// Decending-order: Messages are inserted to the top of the feed. (e.g. Liveblogs)
				windows[win].obj.get(0).scrollTop = 0;
			} else {

				// Ascending order: Messages are inserted to the bottom of the feed,
				// and need to be auto-scrolled. (e.g. Markets Live, Live Q&A)
				if (cfg.fixed_height) {

					// Scroll the fixed-height container.
					windows[win].obj.get(0).scrollTop = 9999999;
				} else {
					$("html, body").animate({ scrollTop: $(document).height()-$(window).height() }, 150);
				}
			}
			windows[win].scrollpending = false;
			enableScrollDetection('autoscroll');
		}

		function disableScrollDetection(key) {
			scrollblockers[key] = 1;
			if (detecttimer) {
				clearTimeout(detecttimer);
			}
			scrolldetection = false;
		}

		function enableScrollDetection(key) {
			delete scrollblockers[key];
			var numblockers = 0;
			if (detecttimer) {
				clearTimeout(detecttimer);
			}
			for (var i in scrollblockers) {
				numblockers++;
			}
			if (!numblockers) {
				detecttimer = setTimeout(function() { scrolldetection = true; }, 10);
			}
		}

		// Fired when a user manually scrolls either the Markets Live or Inferno windows, which should cause the other window to scroll to the same time index. Also triggered by setting scrollTop in JavaScript, but autoscroll is set to false when manipulating scrollTop so that it does not cause cascading scrolls.
		function onUserScroll(e) {
			if (!chatsession.find('.scroll-sync input:checkbox').get(0).checked || !scrolldetection) return false;
			if (scrolltimer) clearTimeout(scrolltimer);
			scrolltimer = setTimeout(function() {

				// If a scroll animation is currently in progress, stop it
				if (animating) {
					windows.chat.obj.stop();
					windows.comments.obj.stop();
					animating = false;
				}

				// Define which window has been scrolled, and set up a reference to the other one as well
				var scrolledWindow = windows[($(e.target).hasClass('chat')) ? 'chat' : 'comments'];
				var otherWindow = windows[($(e.target).hasClass('chat')) ? 'comments' : 'chat'];

				// Determine the offset from the top of the page to the vertical mid point of the chat windows, where the two windows should syncronise on the same timestamp
				var midPointOffset = (windows.chat.obj.outerHeight()/2) + windows.chat.obj.offset().top;

				// Determine which element, within the window that has been scrolled, is now over the mid point
				var midPointElement = null;
				scrolledWindow.obj.find(scrolledWindow.itemtag).each(function() {
					if ($(this).offset().top > midPointOffset) return false;
					midPointElement = $(this);
				});

				// Get the time index of the element in the middle of the window
				var ts = midPointElement.attr(scrolledWindow.timestampattr);

				// Find the element in the other window that has a time index closest to the one in the middle of the scrolled window. Calculate how far into the content of the window the element occurs
				var scrollos = 0;
				otherWindow.obj.find(otherWindow.itemtag).each(function() {
					if ($(this).attr(otherWindow.timestampattr) > ts) return false;
					scrollos += $(this).outerHeight();
				});

				// Deduct half the visible height of the chat window so that when treated as a scroll offset, it places the target element in the middle of the window (the two windows can have different heights due to the possible presence of the participant's interface, so always use the main ML chat box for height-of-window measurements, so that they line up horizontally)
				scrollos -= (windows.chat.obj.outerHeight()/2);

				// Limit the offset to a minimum of zero
				scrollos = Math.max(scrollos, 0);

				// Limit the offset to a maximum of the height of the content minus the visible height of the window
				scrollos = Math.min(scrollos,(otherWindow.obj.get(0).scrollHeight-otherWindow.obj.height()));

				// Round off the offset to the nearest pixel
				scrollos = Math.round(scrollos);

				// If the resulting offset is significantly different to where the window is scrolled to already, scroll it into the target position
				if (otherWindow.obj.get(0).scrollTop < (scrollos-50) || otherWindow.obj.get(0).scrollTop > (scrollos+50)) {
					disableScrollDetection('syncscroll');
					otherWindow.obj.get(0).scrollTop = scrollos;
					enableScrollDetection('syncscroll');
				}
			}, 300);
		}

		function setWindowObj(name, obj) {
			windows[name].obj = $(obj);

            // Remove comment to enable scroll sync
			//windows[name].obj.scroll(onUserScroll);
		}

		return {
			preContentChange: preContentChange,
			postContentChange: postContentChange,
			disableScrollDetection:disableScrollDetection,
			enableScrollDetection:enableScrollDetection,
			setWindowObj:setWindowObj,
			appendAndScroll:appendAndScroll,
			prependAndScroll:prependAndScroll,
			scroll:scroll
		};
	})();

	function apiRequest(jqXHR, onfail) {
		if (typeof onfail == "undefined") {
			onfail = function(resp) {
				if (resp.responseText && resp.responseText.toLowerCase().indexOf("permission") != -1) {
					alert("Sorry. You do not have the correct permission level to continue.\n\nPlease make sure you're logged in.");
				}
			};
		}
		jqXHR.error(onfail);
	}

	function resizeInferno() {
		var win = $("#inferno-commentlist");
		if (!$('#ribbonad').height()) $('#ribbonad').remove();
		if (!win.length) return;
		var viewportheight = $(window).height();
		var nonchatheight = $('#inferno-commentlist').offset().top;
		var workingarea = Math.max(viewportheight-nonchatheight, 900-nonchatheight);
		var formopenercloser = 8;
		var useruiheight = formopenercloser + $("#inferno-input:visible").outerHeight();
		scroller.disableScrollDetection('resizeinferno');
		win.height(workingarea-useruiheight);
		scroller.enableScrollDetection('resizeinferno');
	}

	function resizeChat() {
		var win = chatsession.find('.chat-panel .wrapper');
		if (!win.length) return;
		var viewportheight = $(window).height();
		var chatpadding = 10;
		var nonchatheight = win.offset().top + chatpadding;
		var workingarea = Math.max(viewportheight-nonchatheight, 900-nonchatheight);
		var paruiheight = chatsession.find(".participantui:visible").outerHeight();
		scroller.disableScrollDetection('resizechat');
		win.height(workingarea-paruiheight);
		scroller.enableScrollDetection('resizechat');
		win.addClass( "fixed-height");
	}

	function block(e) {
		var msg = $(e.target).parents('.msg');
		var mid = msg.attr('data-mid');
		apiRequest($.post(cfg.baseurl, {action:'block',id:mid}));
		msg.find(".block").remove();
		newmessagefield.val(msg.attr("data-rawmessage"));
		msg.addClass("blocked").get(0).innerHTML += "<span class='blocknotice'>(blocked by me)</span>";
	}

	function receiveBlock(data) {
		var msg = $('#webchat-msg-'+data.msgblocked);
		doKeypoints({"mid":data.msgblocked, "event":"delete"});  //remove key point from list
		if (msg.length && !msg.hasClass("blocked")) {
			msg.addClass("blocked").append("<span class='blocknotice'>(blocked by "+data.blockedby+")</span>");
			msg.find('.block').remove();
		}
	}

	function blockTick() {
		scroller.disableScrollDetection('blocktick');
		chatsession.find('.chat .msg.prepub').each(function() {
			var msg = $(this);
			if (!msg.hasClass("blocked")) {
				if (msg.attr('data-timestamp') < (serverTime()+blockgrace)) {
					msg.removeClass("prepub");
				} else {
					msg.find(".block").html("Block (" + Math.ceil(msg.attr('data-timestamp')-serverTime()-blockgrace) + ")");
				}
			}
		});
		scroller.enableScrollDetection('blocktick');
		setTimeout(blockTick, 500);
	}

	function sendMsg() {
		var msg = newmessagefield.val();
		var keytextfield = $("input.key-text");
		var keytext = '';
		var keychecked = $(".key-event").prop('checked');
		
		// change to default value !!!
		if (keytextfield.val() != cfg.insertkeytext && keychecked) keytext = keytextfield.val();

		if (msg.length === 0) return false;
		if (msg.length > 4096) return alert("Sorry, your message is too long. Please restrict messages to four thousand characters.");

		// Strip all spans, as they may carry nasty MS Word formatting
		var tempstring = "";
		while (tempstring.toString() != msg.toString()) {
			tempstring = msg;
			msg = msg.replace(/<span(.*?)>(.*?)<\/span>/i, "$2");
		}
		var isblockquote;
		if (chatsession.find(".options .opt-quote").get(0).checked) {
			isblockquote = true;
			chatsession.find(".options .opt-quote").get(0).checked = false;
		} else {
			isblockquote = false;
		}

		btnsend.attr('disabled', 'disabled');
		newmessagefield.blur().attr('disabled', 'disabled');
		msg = encodeMessageForSendingToAPI(msg);
		apiRequest($.post(cfg.baseurl, {action:'sendmsg', msg:msg, keytext:keytext, isblockquote:(isblockquote?1:0)}, function(resp) {
			newmessagefield.removeAttr('disabled');
			btnsend.removeAttr('disabled');
			if (resp !== true && resp.length < 500) {
				alert(resp);
			} else {
				newmessagefield.val("");
				keytextfield.val(cfg.insertkeytext);
			}
			newmessagefield.focus();
		}));
		return false;
	}

	function encodeMessageForSendingToAPI(msg) {
		var output;

		// Encode as URI component to dodge wordpress addslashes to all input
		output = msg+"";
		output = encodeURIComponent(output);
		output = output.replace(/\'/g, '%27');

		return output;
	}

	// Add participant UI to messages in given element
	function addParticipantUI(el) {
		if (chatsession && chatsession.isparticipant) {
			$('.messageheader',el).prepend('<div class="participant-options"><a href="javascript:void(0)" class="participant-option-edit">Edit</a><a href="javascript:void(0)" class="participant-option-delete">Delete</a></div>');
			$('.messagebody',el).prepend('<span class="block">Block</span>');
		}
	}

	function addMsg(html, mid, datemodified) {
		var existingmessage;
		existingmessage = findMessageOnPageByID(mid);
		if (existingmessage.length) {
			if (typeof datemodified == "undefined" || existingmessage.attr("data-datemodified") < datemodified) {
				existingmessage.replaceWith(html);
				addParticipantUI(findMessageOnPageByID(mid));
			}
		} else {
			if (content_order == 'descending') {
				scroller.prependAndScroll('chat', html);
			} else {
				scroller.appendAndScroll('chat', html);
			}
		}

		WebchatShared.convertEmbeddedVideos();
	}

	function receiveMsg(data) {
		var authorkeyname, args;
		if (data.author) {
			if (!participantList.containsParticipant(data.author)) {
				participantList.addParticipant(data.authorcolour, data.authordisplayname, data.author, data.authornamestyle);
			}
		}

		addMsg(data.html, data.mid, data.datemodified);
	}

	function receiveDeleteMessage(data) {
		var msg;

		if (data.messageid) {
			msg = findMessageOnPageByID(data.messageid);
			msg.hide(350, function() {
				msg.remove();
			});
		}
	}

	function insertKeypoint(msgid, keytext) {
		var item_found = false;
		$("ul.key-list li").each(function( index ) {
			item_id = this.id.replace('msg-','');
			if ((msgid > item_id && content_order == "descending") || (msgid < item_id && content_order == "ascending")) {
				$(this).before("<li id='msg-"+msgid+"'><a href='#"+md5('message'+msgid)+"'>"+keytext+"</a></li>");
				item_found = true;
				return false;
			}
		});
		if (!item_found) $("ul.key-list").append("<li id='msg-"+msgid+"'><a href='#"+md5('message'+msgid)+"'>"+keytext+"</a></li>");
	}

	function doKeypoints(data) {

		//append key list if not exist
		if ( $("ul.key-list").length == 0 ) $(".webchat-introduction").append("<ul class='key-list'></ul>");

		keylist = $("ul.key-list");
		msgid = data.mid ? data.mid : data.messageid; //when delete the field has a different name
		msgitem = $("li#msg-"+msgid);

		if ( (data.event == 'delete') || (!data.keytext) ) { //delete item
			msgitem.remove();
		} else if (msgitem.length > 0)  { //edit item
			msgitem.children().text(data.keytext);
		} else { //insert item
				insertKeypoint (msgid, data.keytext);
		}

		if ( keylist.children().length == 0 ) keylist.remove();

	}

	function findMessageOnPageByID(messageid) {
		return $("#webchat-msg-"+messageid);
	}

	var participantList = (function() {
		var chatsession, cfg;

		function setChatSession(newchatsession) {
			chatsession = newchatsession;
		}

		function setChatConfig(newcfg) {
			cfg = newcfg;
		}

		function addParticipant(authorcolour, fullname, shortname, displaystyle) {
			var participanthtml, participantscontainer;

			participantscontainer = chatsession.find(".webchat-participants");

			if (!participantscontainer.length) {
				return;
			}

			noparticipants = chatsession.find(".no-participants").remove();

			if (chatsession.find(".participant-list").length === 0) {
				participantscontainer.append(" <ul class='participant-list'></ul>");
			} else {
				if (authorcolour === null) authorcolour = chatsession.find(".participant-list li").length + 1;
			}
			if (authorcolour === null) authorcolour = 1;
			participanthtml = getParticipantHTML(authorcolour, fullname, shortname, displaystyle);
			chatsession.find(".participant-list").append(participanthtml);
		}

		function getParticipantHTML(authorcolour, fullname, shortname, displaystyle) {
			var displayname;

			if (typeof displaystyle == "undefined") {
				displaystyle = "initials";
			}

			participanthtml = "";
			participanthtml += "<li id='"+htmlspecialchars(getParticipantElementID(shortname))+"'>";

			if (displaystyle == "initials") {
				displayname = shortname;
			} else {
				displayname = fullname;
			}

			participanthtml += "<span class='webchat-participant color-"+htmlspecialchars(authorcolour)+"'>"+htmlspecialchars(displayname)+"</span>";
			if (displaystyle == "initials") {
				participanthtml += htmlspecialchars(fullname);
			}
			participanthtml += "</li>";

			return participanthtml;
		}

		function containsParticipant(keyname) {
			var participantid = getParticipantElementID(keyname);
			return ($('#'+participantid).length !== 0);
		}

		function getParticipantElementID(keyname) {
			return "webchat-par-"+htmlspecialchars(keyname);
		}

		function htmlspecialchars(str) {
			var div = $("<div></div>");
			div.text(str);
			return div.html();
		}

		return {
			addParticipant:addParticipant,
			containsParticipant:containsParticipant,
			setChatSession:setChatSession,
			setChatConfig:setChatConfig
		};

	}());

	function endSession() {
		if (!confirm('End session now?')) return false;
		apiRequest($.post(cfg.baseurl, {action:'end'}, function(resp) {
			if (typeof resp.guid != 'undefined' && resp.guid){
				location.replace(resp.guid);
			} else {
				location.replace('/');
			}
		}));
	}

    function receiveEndSession(data) {
        window.pusher.unsubscribe(cfg.channel);
        clearTimeout(polltimer);
        var msgid = 'endchat';
        var msg = '<div class="msg sysmsg" id="webchat-msg-'+msgid+'"><div>This session has now closed';
        if (typeof data.guid != 'undefined' && data.guid) msg += ', and <a href="'+data.guid+'">is available here</a>.';
        msg += '.</div></div>';
        addMsg(msg, msgid);
        if (chatsession.hasClass('participant')) {
            chatsession.removeClass('participant');
            if (cfg.fixed_height){
                resizeChat();
            }
        }

        $(".webchat-inprogress").html("Closed").removeClass("webchat-inprogress").addClass("webchat-closed");
    }

    function receiveStartSession() {
        var msgid = 'startchat';
        var msg = '<div class="msg sysmsg" id="webchat-msg-'+msgid+'"><div>This session is now in progress.</div></div>';
        addMsg(msg, msgid);

        // Change lozenge to "In progress"
        $(".webchat-comingsoon").html("In progress").removeClass("webchat-comingsoon").addClass("webchat-inprogress");
        // Hide "Start session now" link and show "End session now" link
        $("#start-session-container").hide();
        $("#end-session-container").show();
    }

    function startSession() {
        if (!confirm('START SESSION NOW?\n\nThe session will be started immediately (there will be no delay). Once started, there is no way to go back to the "Coming soon" state.\n\nAre you sure you want to start the session now?')) return false;
        apiRequest($.post(cfg.baseurl, {action:'startSession'}, function(resp) {
            receiveStartSession();
        }));

        return false;
    }

	function postSaved(data) {
		var lozenge = $('h2.entry-title span');
		$('h2.entry-title').html(data.title).prepend(lozenge);
		$('.webchat-post-excerpt').html('<p>'+data.excerpt+'</p>');
	};

	function time(tothemillisecond) {
		var timenow = new Date();
		return (tothemillisecond) ? timenow.getTime() : Math.round(timenow.getTime()/1000);
	}

	function serverTime(tothemillisecond) {
		var timenow = new Date();
		var ret = (tothemillisecond) ? timenow.getTime() : Math.round(timenow.getTime()/1000);
		return ret + servertimeoffset;
	}

	function connectStatusMsg(msg, type) {
		var connectmsg;

		if (lastpusherevent == type) return;

		connectmsg = chatsession.find('#webchat-connectmsg');
		if (connectmsg.length == 0) {
			connectmsg = $("<div id='webchat-connectmsg' class='msg sysmsg'><div></div></div>");
		}

		connectmsg.removeClass(function(index, classname) {
			var classnamestoremove;

			classnamestoremove = (classname.match (/\webchat-connectmsg-\S+/g) || []).join(' ');
			return classnamestoremove;
		});
		connectmsg.addClass("webchat-connectmsg-"+type);

		if (typeof(msg) !== 'string') {
			connectmsg.addClass("webchat-connectmsg-nomessage");
		}

		if (content_order == 'descending') {
			connectmsg.prependTo(chatsession.find('.chat'));
		} else {
			connectmsg.appendTo(chatsession.find('.chat'));
		}

		connectmsg.find("div").html(msg);
		if (cfg.fixed_height) scroller.scroll('chat');
		lastpusherevent = type;
	}

	function pusherWorking() {
		clearTimeout(polltimer);
		clearTimeout(receivetimer);

		var timeout = Math.ceil(Math.random() * cfg.initial_polling_wait_time) + (parseInt(cfg.initial_polling_wait_time));
		receivetimer = setTimeout(pollTick, timeout * 1000);
	}

	function pollTick() {
		var channelNames = pollchannel;

		// If inferno is defined, add infernoChannelName to the api call
		if (typeof Inferno != "undefined") {
			channelNames = channelNames+','+ infernoChannelName;
		}

		apiRequest($.ajax({type:'GET', dataType:'json', url:cfg.baseurl, cache:true, data:{action:'poll', channels:channelNames, state:lastpusherevent}, timeout:4000, success:function(events) {
			for (var i=0, s=events.length; i<s; i++) {
				if (events[i].channel == pollchannel) {
					if (events[i].event == 'msg' || events[i].event == 'editmsg') receiveMsg(events[i].data);
					else if (events[i].event == 'block') receiveBlock(events[i].data);
					else if (events[i].event == 'end') receiveEndSession(events[i].data);
					else if (events[i].event == 'startSession') receiveStartSession(events[i].data);
				} else {
					if (events[i].event == 'inferno') infernoPushMessage(events[i].data);
				}
			}
		}}));

		polltimer = setTimeout(pollTick, cfg.poll_interval * 1000);
	}

	// Complete webchat setup
	function webchatSetup(){
			// Cache the webchat session DOM elements
			chatsession = $('#webchat-session');
			if (!chatsession.length) return;
			newmessagefield = chatsession.find('textarea.new-msg');
			chatwin = chatsession.find(".chat");
			commentspanel = chatsession.find('.comments-panel');
			btnsend = chatsession.find('.participantui input:submit');

			// key event textarea and checkbox, moved here as it wasn't working on testers Mac
			chatsession.on('click', '.key-event', function () {
			    $(this).closest('form').find('.key-text').toggle(this.checked);
			});	
			chatsession
				.on('focus', '.key-text', function () {
				    if (this.value === this.defaultValue) {
				        this.value = '';
				    }
				})
			  .on('blur', '.key-text', function () {
			        if (this.value === '') {
			            this.value = this.defaultValue;
			        }
				});

			// Show the current participants
			participantList.setChatSession(chatsession);
			participantList.setChatConfig(cfg);
			if (cfg.participants && cfg.participants.length) {
				var participantindex, participant;
				for (participantindex in cfg.participants) {
					participant = cfg.participants[participantindex];
					participantList.addParticipant(
						participant.colour,
						participant.display_name,
						participant.initials,
						cfg.authornamestyle
					);
				}
			} else {
				chatsession.find(".webchat-participants").append("<span class='no-participants'>No participants have joined this session yet.</span>");
			}


			/* Set up interactive message behaviours */
			if (cfg.alloweditanddeletepreviousmessages) {


				/* Handle click on edit/delete buttons */

				chatsession.delegate(".participant-option-edit", "click", function(e) {
					var clickedbutton = $(e.currentTarget);
					var msg = clickedbutton.closest(".msg");
					var editform;

					// Prevent two edit forms being added to the same message
					if (msg.find(".webchat-msg-editform").length) {
						return;
					}

					msg.find(".messagebody").hide();
					msg.toggleClass('show-participant-options');

					var keytext_data = msg.attr("data-keytext");

					editform = $("<div class='webchat-msg-editform clearfix'><form method='post' action=''><div class='options'><input type='submit' class='btn-cancel button' value='Cancel' /><div class='isblockquote'><label><input type='checkbox' name='isblockquote' value='1' "+((msg.attr("data-isblockquote") == 1)?"checked='checked'":"")+" /> Send as quote</label></div><label><input type='checkbox' class='check key-event' /> Key event</label><input type='submit' class='btn-save button' value='Save changes' /></div><textarea class='msgtext'></textarea><input class='key-text' value='"+cfg.insertkeytext+"'></input></form></div>");
					
					editform.find("textarea.msgtext").val(msg.attr("data-rawmessage"));
					if (keytext_data) {
						editform.find(".key-event").prop('checked', true);
						editform.find("input.key-text").val(keytext_data).css("display","block");
					}

					editform.find(".btn-cancel").click(function() {
						editform.remove();
						msg.find(".messagebody").show();
						msg.toggleClass('show-participant-options');
						return false;
					});

					editform.find("form").submit(function(e) {
						
						var keytext = '';
						var keychecked = editform.find(".key-event").prop('checked');
						var keytextfield = editform.find("input.key-text")			
						// change to default value !!!
						if (keytextfield.val() != cfg.insertkeytext && keychecked) keytext = keytextfield.val();
						
						apiRequest($.post(cfg.baseurl, {action:'editmsg', messageid:msg.attr("data-mid"), newtext:encodeMessageForSendingToAPI(editform.find("textarea.msgtext").val()), keytext:keytext, isblockquote:(editform.find(".isblockquote input").get(0).checked?1:0)}));

						return false;
					});
					msg.append(editform);
				});

				chatsession.delegate(".participant-option-delete", "click", function(e) {
					if (!confirm('Really delete message?')) {
						return;
					}

					var clickedbutton = $(e.currentTarget);
					var msg = clickedbutton.closest(".msg");

					// TODO:Could put an embargo period on the delete and allow undoing of it
					msg.fadeTo(0, 0.5);
					apiRequest($.post(cfg.baseurl, {action:'deletemsg', messageid:msg.attr("data-mid")}));
				});


				/* Show / hide buttons on mouse-enter/mouse-leave */

				function handleMsgHover(msgelement, direction) {
					var msg, action;

					msg = $(msgelement);
					if (msg.hasClass("prepub") || direction == "leave") {
						action = "remove";
					} else {
						action = "add";
					}

					if (!$.browser.msie) {
						msg[action+"Class"]("show-participant-options");
					}
				}
				chatsession.delegate(".msg", "mouseenter", function(e) {
					handleMsgHover(e.currentTarget, "enter");
				});
				chatsession.delegate(".msg", "mouseleave", function(e) {
					handleMsgHover(e.currentTarget, "leave");
				});
			}

			// Load the chat so far
			var contentdirection;
			contentdirection = ((content_order == "descending")?"reverse":"")+"chronological";
			apiRequest($.ajax({type:'GET', dataType:'json', url:cfg.baseurl, cache:true, data:{action:'catchup', 'direction':contentdirection}, success:function(chat_html) {

				// Add previous content
			
				chatwin.append(chat_html.msg);
				
				// Add previous keypoints to list if they exist
				if (chat_html.keypoints) $(".webchat-introduction").append(chat_html.keypoints);
				scroller.setWindowObj('chat', chatwin);
				WebchatShared.convertEmbeddedVideos();

				// Activate Inferno form opening and closing buttons
				commentspanel.delegate('.show-hide-toggle', 'click', function() {
					var actions = $(this).hasClass('hide') ? ['show', 'hide'] : ['hide', 'show'];
					commentspanel.find(".show-hide-toggle.show")[actions[0]]();
					commentspanel.find(".show-hide-toggle.hide")[actions[1]]();
					commentspanel.find("#inferno-input")[actions[1]]();
					if (cfg.fixed_height) {
						resizeInferno();
					}
				});

				if (cfg.fixed_height) {
					resizeChat();
					resizeInferno();
					scroller.scroll('chat');
					scroller.scroll('comments');
				}

				if (content_order == "ascending") {
					scroller.enableScrollDetection('resizechat');
				}

				chatsession.find('.loader').remove();
				chatsession.find('.comments-panel, .chat-panel, .footer').css('visibility','visible');

				// Get privileges and load pusher channel
				connectStatusMsg("Connecting to stream &hellip;", "connecting");
				apiRequest($.post(cfg.baseurl, {action:'getprivs'}, function(privs) {

					// Connect to pusher
					var pushertimeout = setTimeout(function() {
						connectStatusMsg(null, 'timeout');
						if (!polltimer) pollTick();
					}, 5000);

					pollchannel = privs.channel;
					if (typeof Pusher === 'function') {
						if (!window.pusher) window.pusher = new Pusher(cfg.pusherkey);
						var channel = window.pusher.subscribe(privs.channel);
						channel.bind('msg', receiveMsg);
						channel.bind('editmsg', receiveMsg);
						channel.bind('block', receiveBlock);
						channel.bind('delete', receiveDeleteMessage);
						channel.bind('end', receiveEndSession);
						channel.bind('startSession', receiveStartSession);
						channel.bind('msg', pusherWorking);
						channel.bind('postSaved', postSaved);

						channel.bind('msg', doKeypoints);
						channel.bind('editmsg', doKeypoints);
						channel.bind('delete', doKeypoints);

						window.pusher.connection.bind('connected', function() {
							if (typeof(cfg.connection_notification === "string")) {
								connectStatusMsg(cfg.connection_notification, 'connected');
							}
							clearTimeout(polltimer);
							clearTimeout(pushertimeout);
						});
						window.pusher.connection.bind('failed', function() {
							connectStatusMsg(null, 'failed');
							clearTimeout(pushertimeout);
							if (!polltimer) pollTick();
						});
						window.pusher.connection.bind('unavailable', function() {
							connectStatusMsg(null, 'unavailable');
							if (!polltimer) pollTick();
						});
					}

					// Set up participant features if applicable
					if (privs.isparticipant) {

						chatsession.isparticipant = true;

						// Display participant UI
						chatsession.addClass('participant');
						if ($.browser.msie) {
							$(".participantbrowserwarning").addClass("participantbrowserwarningie");
						}

						// Add participant UI to messages
						addParticipantUI(chatsession);

						// Server time sync
						$.post(cfg.baseurl, {action:'gettime', nc:time()}, function(response) {
							if (!isNaN(response)) servertimeoffset = response-time();
						});

						// Activate emoticons
						chatsession.find(".emoticons img").click(function() {
							var img = $(this).get(0);
							if (img.title) {
								newmessagefield.get(0).value += img.title;
							} else {
								newmessagefield.get(0).value += "{"+img.src.substring((img.src.lastIndexOf("/")+1), img.src.lastIndexOf("."))+"}";
							}
							newmessagefield.focus();
						});

						// Send message on keypress or button click, disable default form submit
						newmessagefield.keypress(function(e) {
							if (chatsession.find(".participantui .opt-send-on-enter").get(0).checked && e.which==13) sendMsg();
						});
						chatsession.find(".participantui .options input:submit").click(sendMsg);

						// Activate block links, update every second
						chatsession.delegate('.msg span.block', 'click', block);
						blockTick();

						chatsession.delegate('#link-end-session', 'click', endSession);
						chatsession.delegate('#link-start-session', 'click', startSession);
						if (cfg.fixed_height){
							resizeChat();
							scroller.scroll('chat');
						}
					}
				}));
			}}));

			$(window).resize(function() {
				if (cfg.fixed_height) {
					resizeChat();
					resizeInferno();
				}
			});
	}

	// Set up inferno event hooks and complete the rest of the setup after Inferno has loaded and domready has fired
	$(function() {
		// If Inferno is defined add setup and inferno hooks, else just complete webchat setup
		if (typeof Inferno != "undefined") {
			Inferno.addFunctionHook(function() {

				// Before Inferno changes the DOM, make the scroller aware that the content in the DOM is about to change
				Inferno.addFunctionHook(function(content, contid, itemid, mode, sortindex) {
					if (contid=='inferno-commentlist') {
						scroller.preContentChange('comments');
					}
				}, 'preDOMChange');

				// After Inferno changes the DOM, make the scroller aware that the content in the DOM has just changed
				Inferno.addFunctionHook(function(content, contid, itemid, mode, sortindex) {
					scroller.postContentChange('comments');
				}, 'postDOMChange');

				// Register the inferno window with the scroller
				scroller.setWindowObj('comments', $("#inferno-commentlist"));

				webchatSetup();

			}, 'load');
		} else {
			// Register the comments window with the scroller
			scroller.setWindowObj('comments', $("#inferno-commentlist"));

			webchatSetup();
		}
	});

	$(window).load(function() {
		if (window.location.hash && $(window.location.hash).length ) {
			$('html, body').animate({
			    scrollTop: $(window.location.hash).offset().top
			}, 300);
		}	
	});

})(jQuery);
