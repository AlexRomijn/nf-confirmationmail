<?php if ( ! defined( 'ABSPATH' ) ) exit;
/*
	All the settings fields for the custom action
*/
return array(

    /*
     * To :: Where to send the confirmation mail to
     */
    'to' => array(
        'name' => 'to',
        'type' => 'textbox',
        'group' => 'primary',
        'label' => __( 'To', 'nf-confirm-mail' ),
        'placeholder' => __( 'Email address', 'nf-confirm-mail' ),
        'value' => '',
        'width' => 'one-half',
        'use_merge_tags' => TRUE,
    ),


    /*
     * Subject :: subject for the confirmation mail
     */

    'email_subject' => array(
        'name' => 'email_subject',
        'type' => 'textbox',
        'group' => 'primary',
        'label' => __( 'Subject', 'nf-confirm-mail' ),
        'placeholder' => __( 'Subject Text', 'nf-confirm-mail' ),
        'value' => '',
        'width' => 'full',
        'use_merge_tags' => TRUE,
    ),


    /*
     * Email Message: use {user:confirmurl} or {user:confirmurlonly} on the place where to place the confirmationlink
     */

    'email_message' => array(
        'name' => 'email_message',
        'type' => 'rte',
        'group' => 'primary',
        'label' => __( 'Email Message (use {user:confirmurl} or {user:confirmurlonly} in e-mail to place unique link)', 'nf-confirm-mail' ),
        'placeholder' => '',
        'value' => '',
        'width' => 'full',
        'use_merge_tags' => array(
            'exclude' => array(
                'post',
				'system',
				'user',
				'fields'
            ),		
		),
        'deps' => array(
            'email_format' => 'html'
        )
    ),

    /*
    |--------------------------------------------------------------------------
    | After confirmation / delete process
    |--------------------------------------------------------------------------
    */


    'confirmation_delete' => array(
        'name' => 'confirmation_delete',
        'type' => 'toggle',
        'group' => 'confirmation',
        'label' => __( 'Enable delete after confirmation', 'nf-confirm-mail' ),
        'value' => '0',
        'use_merge_tags' => TRUE,
		'width' => 'full',
    ),

    'confirmation_delete_when_not_confirmed' => array(
        'name' => 'confirmation_delete_when_not_confirmed',
        'type' => 'toggle',
        'group' => 'confirmation',
        'label' => __( 'Enable delete when not confirmed', 'nf-confirm-mail' ),
        'value' => '0',
        'use_merge_tags' => TRUE,
		'width' => 'full',
    ),

    'confirmation_delete_period' => array(
        'name' => 'confirmation_delete_period',
        'type' => 'textbox',
        'group' => 'confirmation',
        'label' => __( 'Delete non confirmed entries after ...days', 'nf-confirm-mail' ),
        'placeholder' => __( '', 'nf-confirm-mail' ),
        'value' => '7',
        'use_merge_tags' => TRUE,
		'width' => 'full',
        'deps' => array(
            'confirmation_delete_when_not_confirmed' => 1
        )		
    ),
	
    /*
    |--------------------------------------------------------------------------
    | After confirmation / notification Settings
    |--------------------------------------------------------------------------
    */

    /*
     * Notify enabled/disabled
     */


    'notify_enable' => array(
        'name' => 'notify_enable',
        'type' => 'toggle',
        'group' => 'notification',
        'label' => __( 'Enable notification', 'nf-confirm-mail' ),
        'value' => '0',
        'use_merge_tags' => TRUE,
		'width' => 'full',
    ),
	
    /*
     * Notify address
     */


    'notify_mail' => array(
        'name' => 'notify_mail',
        'type' => 'textbox',
        'group' => 'notification',
        'label' => __( 'Send notification to', 'nf-confirm-mail' ),
        'placeholder' => __( 'Email address to send notification to', 'nf-confirm-mail' ),
        'value' => '',
        'use_merge_tags' => TRUE,
		'width' => 'full',
        'deps' => array(
            'notify_enable' => 1
        )		
    ),

    /*
     * Subject: for the notification mail
     */
    'notify_subject' => array(
        'name' => 'notify_subject',
        'type' => 'textbox',
        'group' => 'notification',
        'label' => __( 'Subject', 'nf-confirm-mail' ),
        'placeholder' => __( 'Subject Text for notification mail', 'nf-confirm-mail' ),
        'value' => __( 'Notification', 'nf-confirm-mail' ),
        'width' => 'full',
        'use_merge_tags' => TRUE,
        'deps' => array(
            'notify_enable' => 1
        )			
    ),
	
    /*
     * Email Message: use {user:confirmurl} or {user:confirmurlonly} on the place where to place the confirmationlink
     */

    'notify_message' => array(
        'name' => 'notify_message',
        'type' => 'rte',
        'group' => 'notification',
        'label' => __( 'Notification text', 'nf-confirm-mail' ),
        'placeholder' => '',
        'value' => '',
        'width' => 'full',
        'use_merge_tags' => true,
        'deps' => array(
            'notify_enable' => 1
        )			
    ),	

    /*
     * Format the e-mail
     */

    'notify_email_format' => array(
        'name' => 'notify_email_format',
        'type' => 'select',
            'options' => array(
                array( 'label' => __( 'HTML', 'nf-confirm-mail' ), 'value' => 'html' ),
                array( 'label' => __( 'Plain Text', 'nf-confirm-mail' ), 'value' => 'plain' )
            ),
        'group' => 'notification',
        'label' => __( 'Format', 'nf-confirm-mail' ),
        'value' => 'html',
        'deps' => array(
            'notify_enable' => '1'
        ),		
        
    ),


    /*
    |--------------------------------------------------------------------------
    | Redirect Settings
    |--------------------------------------------------------------------------
    */
    /*
     * Redirect after success: the url to send user to when succesfully confirmed
     */
    'redirectaftersuccess' => array(
        'name' => 'redirectaftersuccess',
        'type' => 'textbox',
        'group' => 'redirect',
        'label' => __( 'Redirect (after success)', 'nf-confirm-mail' ),
        'placeholder' => __( '', 'nf-confirm-mail' ),
        'value' => '',
        'width' => 'full',
        'use_merge_tags' => TRUE,
    ),	

    /*
     * Redirect after already confirmed: the url to send user to when already confirmed
     */
    'redirectafteralreadyconfirmed' => array(
        'name' => 'redirectafteralreadyconfirmed',
        'type' => 'textbox',
        'group' => 'redirect',
        'label' => __( 'Redirect (after already confirmed)', 'nf-confirm-mail' ),
        'placeholder' => __( '', 'nf-confirm-mail' ),
        'value' => '',
        'width' => 'full',
        'use_merge_tags' => FALSE,
    ),	

    /*
     * Redirect after error: when an unexpected error occured, send user to this url
     */
    'redirectaftererror' => array(
        'name' => 'redirectaftererror',
        'type' => 'textbox',
        'group' => 'redirect',
        'label' => __( 'Redirect (after error)', 'nf-confirm-mail' ),
        'placeholder' => __( '', 'nf-confirm-mail' ),
        'value' => '',
        'width' => 'full',
        'use_merge_tags' => FALSE,
    ),	


    /*
    |--------------------------------------------------------------------------
    | Reminder Settings
    |--------------------------------------------------------------------------
    */


    /*
     * Time to send reminder in minutes (3600 sec = 1 hour = * 24 = 1 day)
     */
    'time2remind' => array(
        'name' => 'time2remind',
        'type' => 'textbox',
        'group' => 'reminder',
        'label' => __( 'Send reminder after .. days', 'nf-confirm-mail' ),
        'placeholder' => __( 'Time to wait for sending reminder (in days)', 'nf-confirm-mail' ),
        'value' => '7',
        'width' => 'one-half',
        'use_merge_tags' => FALSE,
    ),
		
    /*
     * Subject for the reminder mail
     */
    'email_subject_reminder' => array(
        'name' => 'email_subject_reminder',
        'type' => 'textbox',
        'group' => 'reminder',
        'label' => __( 'Subject', 'nf-confirm-mail' ),
        'placeholder' => __( 'Subject Text for reminder', 'nf-confirm-mail' ),
        'value' => '',
        'width' => 'full',
        'use_merge_tags' => TRUE,
    ),



    /*
     * Email Message from the reminder mail. Use {user:confirmurl} or {user:confirmurlonly} to put url to confirm in it
     */

    'email_message_reminder' => array(
        'name' => 'email_message_reminder',
        'type' => 'rte',
        'group' => 'reminder',
        'label' => __( 'Email Message Reminder (use {user:confirmurl} or {user:confirmurlonly} in e-mail to place unique link)', 'nf-confirm-mail' ),
        'placeholder' => '',
        'value' => '',
        'width' => 'full',
        'use_merge_tags' => true,
        'deps' => array(
            'email_format' => 'html'
        )
    ),

    /*
    |--------------------------------------------------------------------------
    | Advanced Settings
    |--------------------------------------------------------------------------
    */

    /*
     * From Name
     */

    'from_name' => array(
        'name' => 'from_name',
        'type' => 'textbox',
        'group' => 'advanced',
        'label' => __( 'From Name', 'nf-confirm-mail' ),
        'placeholder' => __( 'Name or fields', 'nf-confirm-mail' ),
        'value' => '',
        'width' => 'one-half',
        'use_merge_tags' => TRUE,
    ),

    /*
     * From Address
     */

    'from_address' => array(
        'name' => 'from_address',
        'type' => 'textbox',
        'group' => 'advanced',
        'label' => __( 'From Address', 'nf-confirm-mail' ),
        'placeholder' => __( 'One email address or field', 'nf-confirm-mail' ),
        'value' => '',
        'use_merge_tags' => TRUE,
    ),

    /*
     * Format the e-mail
     */

    'email_format' => array(
        'name' => 'email_format',
        'type' => 'select',
            'options' => array(
                array( 'label' => __( 'HTML', 'nf-confirm-mail' ), 'value' => 'html' ),
                array( 'label' => __( 'Plain Text', 'nf-confirm-mail' ), 'value' => 'plain' )
            ),
        'group' => 'advanced',
        'label' => __( 'Format', 'nf-confirm-mail' ),
        'value' => 'html',
        
    ),

    /*
     * Cc if needed
     */

    'cc' => array(
        'name' => 'cc',
        'type' => 'textbox',
        'group' => 'advanced',
        'label' => __( 'Cc', 'nf-confirm-mail' ),
        'placeholder' => '',
        'value' => '',
        'use_merge_tags' => TRUE,
    ),

    /*
     * Bcc if needed
     */

    'bcc' => array(
        'name' => 'bcc',
        'type' => 'textbox',
        'group' => 'advanced',
        'label' => __( 'Bcc', 'nf-confirm-mail' ),
        'placeholder' => '',
        'value' => '',
        'use_merge_tags' => TRUE,
    ),    
);