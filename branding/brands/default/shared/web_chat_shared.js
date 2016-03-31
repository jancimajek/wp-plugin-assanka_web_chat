WebchatShared = (function($) {


	function addScript(baseurl, params) {
		var fullurl, script, key;

		script = document.createElement("SCRIPT");
		querystring = "";
		if (typeof params != "undefined") {
			for (key in params) {
				querystring += ((querystring == "")?"?":"&");
				querystring += encodeURIComponent(key)+"="+encodeURIComponent(params[key]);
			}
		}
		fullurl = baseurl+querystring;

		script.src = fullurl;
		document.getElementsByTagName('HEAD')[0].appendChild(script);
	};

	function convertTweet(embeddedtweetelement) {
		var tweeturl = $(embeddedtweetelement).text();

		$(embeddedtweetelement).attr("data-converted", 1);

		var callbackname;
		do {
			callbackname = "embeddedtweetcallback"+Math.floor(Math.random() * 100000000000);
		} while (typeof window[callbackname] != "undefined");

		window[callbackname] = function(resp) {

			$(embeddedtweetelement).html(resp.html);
			addScript('http://platform.twitter.com/widgets.js');
		};

		addScript('https://api.twitter.com/1/statuses/oembed.json', {
			"url":tweeturl,
			"omit_script":1,
			"callback":callbackname
		});
	}

	function convertFTVideo(embeddedftvideoelement) {
		var width, height, videoid, replacementhtml;

		$(embeddedftvideoelement).attr("data-converted", 1);

		// Extract width, height, videoID from element classes
		width = embeddedftvideoelement.className.match(/ftvideowidth([0-9]+)/)[1];
		height = embeddedftvideoelement.className.match(/ftvideoheight([0-9]+)/)[1];
		videoid = embeddedftvideoelement.className.match(/ftvideovideoid([0-9]+)/)[1];

		// Build HTML to embed
		replacementhtml = "";
		replacementhtml += "<object width='"+width+"' height='"+height+"'>";
		replacementhtml += "<param name='wmode' value='transparent' />";
		replacementhtml += "<param name='movie' value='http://c.brightcove.com/services/viewer/federated_f9?isVid=1&isUI=1' />";
		replacementhtml += "<param name='flashVars' value='wmode=transparent&@videoPlayer="+videoid+"&playerID=590314128001&playerKey=AQ%2E%2E,AAAACxbljZk%2E,eD0zYozylZ3KmYvlyzd8myNVJz2Gttzx&domain=embed&dynamicStreaming=true' />";
		replacementhtml += "<param name='base' value='http://admin.brightcove.com' />";
		replacementhtml += "<param name='seamlesstabbing' value='false' />";
		replacementhtml += "<param name='allowFullScreen' value='true' />";
		replacementhtml += "<param name='swLiveConnect' value='true' />";
		replacementhtml += "<param name='allowScriptAccess' value='always' />";
		replacementhtml += "<embed src='http://c.brightcove.com/services/viewer/federated_f9?isVid=1&isUI=1' flashVars='wmode=transparent&@videoPlayer="+videoid+"&playerID=590314128001&playerKey=AQ%2E%2E,AAAACxbljZk%2E,eD0zYozylZ3KmYvlyzd8myNVJz2Gttzx&domain=embed&dynamicStreaming=true' base='http://admin.brightcove.com' width='"+width+"' height='"+height+"' seamlesstabbing='false' type='application/x-shockwave-flash' allowFullScreen='true' allowScriptAccess='always' swLiveConnect='true' wmode='transparent'>"
		replacementhtml += "</embed>";
		replacementhtml += "</object>";

		$(embeddedftvideoelement).html(replacementhtml);
	}


	function convertYoutubeVideo(embeddedyoutubevideoelement) {
		var encodedreplacementhtml, replacementhtml;

		$(embeddedyoutubevideoelement).attr("data-converted", 1);

		encodedreplacementhtml = embeddedyoutubevideoelement.className.match(/youtubeembedcode([ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789\+\/=]+)/);
		if (encodedreplacementhtml && encodedreplacementhtml.length >= 2 && encodedreplacementhtml[1]) {
			encodedreplacementhtml = encodedreplacementhtml[1];
		} else {
			return;
		}
		replacementhtml = base64_decode(encodedreplacementhtml);

		$(embeddedyoutubevideoelement).html(replacementhtml);
	}


	function convertEmbeds(classname, callback) {
		$("p."+classname).each(function() {
			var converted;

			converted = ($(this).attr("data-converted") && ($(this).attr("data-converted") == 1));
			if (converted) {
				return;
			}

			callback.apply(window, [this]);
		});
	}

	function convertEmbeddedVideos() {
		convertEmbeds("embeddedtweet", convertTweet);

		// @todo Following two lines should be removed along with the respective functions they call once FT Aphavile wrapper is updated & BrightcoveFT.version > 2.0.1 on all blogs.
		convertEmbeds("embeddedftvideo", convertFTVideo);
		convertEmbeds("embeddedyoutubevideo", convertYoutubeVideo);

		// Brightcove is a CDN that takes care of all the videos at videos.ft.com.
		// To get videos to load, you need to call their 'createExperience' function.
		// That function is located in a js file that's in the FT Wrapper code.
		if (typeof BrightcoveFT !== "undefined" && typeof BrightcoveFT.Init !== "undefined" && typeof BrightcoveFT.Init.createExperience !== "undefined") {
			jQuery('.video-container-ftvideo .BrightcoveExperience').each(function() {

				// Once BrightcoveFT.Init.createExperience is executed, the
				// brightcove object becomes available. As additional videos are
				// initialised, they're added to brightcove.experiences.
				if (typeof brightcove === "undefined" || typeof brightcove.experiences === "undefined" || typeof brightcove.experiences[this.id] === "undefined") {
					BrightcoveFT.Init.createExperience(this.id);
				}
			});
		}
	}


	function base64_decode (data) {
		// http://kevin.vanzonneveld.net
		// +   original by: Tyler Akins (http://rumkin.com)
		// +   improved by: Thunder.m
		// +      input by: Aman Gupta
		// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		// +   bugfixed by: Onno Marsman
		// +   bugfixed by: Pellentesque Malesuada
		// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		// +      input by: Brett Zamir (http://brett-zamir.me)
		// +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		// *     example 1: base64_decode('S2V2aW4gdmFuIFpvbm5ldmVsZA==');
		// *     returns 1: 'Kevin van Zonneveld'
		// mozilla has this native
		// - but breaks in 2.0.0.12!
		//if (typeof this.window['atob'] == 'function') {
		//    return atob(data);
		//}
		var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
		var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
		ac = 0,
		dec = "",
		tmp_arr = [];

		if (!data) {
			return data;
		}

		data += '';

		do { // unpack four hexets into three octets using index points in b64
			h1 = b64.indexOf(data.charAt(i++));
			h2 = b64.indexOf(data.charAt(i++));
			h3 = b64.indexOf(data.charAt(i++));
			h4 = b64.indexOf(data.charAt(i++));

			bits = h1 << 18 | h2 << 12 | h3 << 6 | h4;

			o1 = bits >> 16 & 0xff;
			o2 = bits >> 8 & 0xff;
			o3 = bits & 0xff;

			if (h3 == 64) {
				tmp_arr[ac++] = String.fromCharCode(o1);
			} else if (h4 == 64) {
				tmp_arr[ac++] = String.fromCharCode(o1, o2);
			} else {
				tmp_arr[ac++] = String.fromCharCode(o1, o2, o3);
			}
		} while (i < data.length);

		dec = tmp_arr.join('');

		return dec;
	}

	// Convert embedded videos on page load, or now, whichever is sooner
	convertEmbeddedVideos();
	if (!$.isReady) {
		$(function() {
			convertEmbeddedVideos();
		});
	}

	return {
		"convertEmbeddedVideos":convertEmbeddedVideos
	}

}($));
