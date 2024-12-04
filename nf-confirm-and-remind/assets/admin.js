jQuery( document ).ready( function( $ ) {
	
	
	// send reminder mail
	jQuery('.nfcmsendreminder').click(function(e) {
		var rid=jQuery(this).attr('data-id');
        jQuery.post(ajaxurl, {action: "send_reminder_mail", reminderid: rid}, function(data) {
			alert(data);
			location.reload();
		});
    });
	
});