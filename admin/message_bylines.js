jQuery(document).ready(function ($) {
	function updateExampleImage() {
		$('#normal-sortables .example-images div').hide();
		if ($('#webchat_show_message_authornames').prop('checked')) {
			$('label.show_message_headshots').css("opacity","1");
			if ($('#webchat_show_message_headshots').prop('checked')) {
				$('#normal-sortables .authorname-and-headshot').show();
			} else {
				$('#normal-sortables .no-headshot').show();
			}
		} else {
			$('label.show_message_headshots').css("opacity","0.5");
			$('#normal-sortables .no-authorname').show();
		}
	}
	$('#webchatMessageBylines input[type=checkbox]').on( "click", updateExampleImage );
	updateExampleImage();
});
