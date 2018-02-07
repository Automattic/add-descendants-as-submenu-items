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

					// Add the checkbox
					var checkboxid = ADASIParams.checkboxprefix + itemID;
					$(item).find('.menu-item-actions .link-to-original').after('<p><label><input type="checkbox" id="' + checkboxid + '" name="' + checkboxid + '" value="1" /> ' + ADASIParams.checkboxdesc + '</label></p>');

					if ( response.checked ) {
						$('#' + checkboxid).prop('checked', true);
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
	$('.menu-item.pending').on('mouseover', function(){
		adasi_add_checkbox( this );
	});
});