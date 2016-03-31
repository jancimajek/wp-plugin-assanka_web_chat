jQuery(document).ready(function ($) {
	// Let the user click a button to add a row for a new Participant
	$('.addrowfornewparticipant').click( function() {
		$(this).hide();
		newRow = $('.participants-metabox-container .new-row-template').clone().removeClass('new-row-template').appendTo($('.participants-metabox-container table')).fadeIn();
		parseAllFieldNamesInRow(newRow);
	});

	// Display notice when there are unsaved changes.
	$( ".participants-metabox-container tbody").on( "change", "tr:not(.new-row-template) input", function() {
		$('.saveorpublish-notice').fadeIn();
	});

	// Intelligently show or hide the addrowfornewparticipant button.
	$( ".participants-metabox-container tbody").on( "keyup", "tr:not(.new-row-template)", function() {
		if (allRowsHaveDisplaynames()) {
			$('.addrowfornewparticipant').fadeIn();
		} else {
			$('.addrowfornewparticipant').fadeOut();
		}

		// If the display name and initials are deleted, treat it like a delete
		if ($('input[data-name="delete_user_id"]',this).is(':checked') === false) {
			if ($.trim($('input[data-name="display_name"]',this).val()).length === 0 && $.trim($('input[data-name="initials"]',this).val()).length === 0) {
				$('input[data-name="delete_user_id"]',this).click();
			}
		}
	});

	// If no participants have been added yet, show the new-participant row.
	if ($('.participants-metabox-container tbody tr:not(.new-row-template)').length === 0) {
		$('.addrowfornewparticipant').click();
	} else {

		// There are participants, so set their input-name indexes.
		$(".participants-metabox-container tbody tr:not(.new-row-template)").filter(function() {
			parseAllFieldNamesInRow(this);
		});
	}

	$('.participants-metabox-container .delete-participant input').click(function() {
		$(this).closest('tr').toggleClass('delete-participant');
		return true;
	});

	// Email "validation" (really just user feedback)
	$( ".participants-metabox-container tbody").on( "keyup", 'input[data-name="email"]', function() {
		var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
		if ($.trim(this.value).length > 0 && !regex.test(this.value)){
			$(this).closest("td").addClass('invalid');
		} else {
			$(this).closest("td").removeClass('invalid');
		}
	});
	$('tr:not(.new-row) input[data-name="email"]').keyup();
	$('.saveorpublish-notice').hide();

	/**
	 * Headshots
	 */
	var targetInput;
	$( ".participants-metabox-container tbody").on( "click", ".upload-image-button", function() {
		targetInput = $('input[data-name="headshot"]',$(this).closest('td'));
		tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
		return false;
	});

	$( ".participants-metabox-container tbody").on( "click", ".upload-image-button-remove", function() {
		targetInput = $('input[data-name="headshot"]',$(this).closest('td'));
		targetInput.val("");
		$(this).hide();
		$('.headshot-image',$(this).closest('td')).css('background','none').hide();
		$('.upload-image-button',$(this).closest('td')).css('display','inline-block');
		return false;
	});

	// Store original window.send_to_editor method so "Insert into post" still works
	var original_send_to_editor = window.send_to_editor;
	window.send_to_editor = function(html) {
		if (targetInput){
			//Add wrapping 'span' to ensure jQuery('img',html) returns an element
			html = "<span>" + html + "</span>";
			src  = $('img',html).attr('src');

			$('.headshot-image',$(targetInput).closest('td')).css('background','url('+src+')').css('display','inline-block');
			$('.upload-image-button',$(targetInput).closest('td')).hide();
			$('.upload-image-button-remove',$(targetInput).closest('td')).css('display','inline-block');

			targetInput.val(src);
			targetInput = undefined;
			tb_remove();
		} else {
			original_send_to_editor(html);
		}
	}

	/**
	 * Methods
	 */

	// Make sure all input fields in a participant row have the right input-name indexes.
	function parseAllFieldNamesInRow(row){
		var index = $(".participants-metabox-container tbody tr:not(.new-row-template)").index(row);
		$('input',row).filter(function() {
			this.name = 'participants['+ index + '][' + $(this).data('name') + ']';
		});
	}

	// Detect whether all participant rows have display-name values.
	function allRowsHaveDisplaynames(){
		var allDisplaynameFields = $(".participants-metabox-container tbody tr:not(.new-row-template) .display-name input");
		var numEmptyFields = allDisplaynameFields.filter(function() {
			return $.trim(this.value).length === 0;
		});
		return (numEmptyFields.length === 0);
	}
});
