jQuery(document).ready(function($) {
	// ID tracker field
	var IDtracker = $('<input>').attr({
		type: 'hidden',
		id: ADASIParams.idtracker,
		name: ADASIParams.idtracker,
		value: ''
	}).prependTo('#update-nav-menu');

	function adasi_add_checkbox( item ) {
		// Skip items that are already modified or are known to not be hierarchical
		if ( $(item).is('.adasi-checked, .menu-item-custom, .menu-item-post_tag') ) {
			return;
		}

		// Don't check this item again
		$(item).addClass('adasi-checked');

		var itemID = parseInt( $(item).attr('id').replace('menu-item-', ''), 10 );

		// Gotta figure it out in PHP, so use an AJAX call
		jQuery.post(
			ajaxurl,
			{
				action: ADASIParams.ajaxaction,
				id: itemID
			},
			function( response ){
				if ( response && response.add ) {
					// Track IDs to check the POST for
					IDtracker.val( IDtracker.val() + ',' + itemID );

                    // Get ids for checkboxes
					var checkboxid = ADASIParams.checkboxprefix + itemID;
					var checkboxskipid = ADASIParams.checkboxskipprefix + itemID;
					var checkboxshallowid = ADASIParams.checkboxshallowprefix + itemID;

					// Add the checkboxes
					$(item).find('.menu-item-actions .link-to-original + *').eq(0)
                        .before('<p><label><input type="checkbox" id="' + checkboxid + '" name="' + checkboxid + '" value="1" /> ' + ADASIParams.checkboxdesc + '</label></p>')
                        .before('<p><label style="margin-left: 30px;"><input type="checkbox" id="' + checkboxskipid + '" name="' + checkboxskipid + '" value="1" /> ' + ADASIParams.checkboxskipdesc + '</label></p>')
                        .before('<p><label style="margin-left: 30px;"><input type="checkbox" id="' + checkboxshallowid + '" name="' + checkboxshallowid + '" value="1" /> ' + ADASIParams.checkboxshallowdesc + '</label></p>');

                    var $label = $('#' + checkboxid).parents('li.menu-item').eq(0).find('.item-title');
                    var $notification = $('<emph style="font-size: 80%; font-style: italic; margin-left: 15px;"></emph>').appendTo($label);
                    if ( response.checked ) {
						$('#' + checkboxid).prop('checked', true);
					}
					if ( response.shallow ) {
						$('#' + checkboxshallowid).prop('checked', true);
					}
					if ( response.skip ) {
						$('#' + checkboxskipid).prop('checked', true);
					}
					if ( response.checked ) {
                        $notification.html('+&nbsp;descendants');
						if ( response.shallow ) {
	                        $notification.html('+&nbsp;children');
	                    }
						if ( response.skip ) {
							$notification.text($notification.text() + ', item hidden');
						}
                    }

				}
			},
			'json'
		);
	}

	// Try adding checkboxes to all existing menu items
	$('.menu-item').each(function(){
		adasi_add_checkbox( this );
	});

	// When hovering over a menu item added using Javascript, try adding a checkbox to it.
	// Props DD32 for mouseover hack to get around return false; inside other click bound event.
	$('.menu-item.pending').live('mouseover', function(){
		adasi_add_checkbox( this );
	});
});