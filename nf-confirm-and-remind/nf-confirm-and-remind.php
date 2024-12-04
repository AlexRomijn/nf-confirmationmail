<?php
/*
Plugin Name: Ninja Forms - Confirmation & Reminder
Plugin URI: http://www.treehugger.nl
Description: Ninja Forms addon which provides a confirmationmail (unique link) action with reminder
Version: 1.3
Author: Alex Romijn
Author URI: http://www.treehugger.nl
License: #
tags: ninja forms, form builder, confirmation mail, reminder mail, send confirmation, unique link
*/

// don't load directly
if (!defined('ABSPATH')) die('-1');

/**
 * Main class for setting up new Action
 *
 *
 * @copyright  2017 Treehugger
 */ 
final class NF_confirm_mail {
	
	private $default_check_limit=100;

	/**
	 * Class constructor
	 *
	 */ 
    function __construct() {
        // Register CSS and JS
		add_action( 'admin_enqueue_scripts', array( $this, 'loadCssAndJs' ) );//ninja_forms_enqueue_scripts
		add_action( 'init', array( $this, 'initialize' ) );

		// cron
		add_action('wp', array($this,'nfcm_cron_activation'));
		register_deactivation_hook (__FILE__, array($this,'nfcm_cron_deactivate'));
		add_action('nf-confirmmail-reminder', array($this,'nfcm_cron_reminder_check')); 
		add_action('nf-confirmmail-submission-cleaning', array($this,'nfcm_cron_submission_cleaning'));

		// setup addon
		add_action( 'ninja_forms_loaded', array( $this, 'setup_plugin' ) );
		
		// register action
		add_action('ninja_forms_register_actions', array($this,'register_actions'));

		/*
		**	Hooks to place field in settings where people can fill in API key
		*/
        add_filter( 'ninja_forms_plugin_settings',                array( $this, 'plugin_settings'        ), 10, 1 );
        add_filter( 'ninja_forms_plugin_settings_groups',         array( $this, 'plugin_settings_groups' ), 10, 1 );
        add_filter( 'ninja_forms_check_setting_confirmreminder',  array( $this, 'check_confirmreminder'  ), 10, 1 );
        add_filter( 'ninja_forms_update_setting_confirmreminder', array( $this, 'update_confirmreminder' ), 10, 1 );
        add_action( 'ninja_forms_save_setting_confirmreminder',   array( $this, 'save_confirmreminder'   ), 10, 1 );

	}

	/**
	 * Initialize of class
	 *
	 * @param none
	 */ 	
	function initialize() {

		// ajax
		
		add_action( 'wp_ajax_send_reminder_mail', array($this,'send_reminder_mail') );
		
		// handle confirmation
		if ($_GET['nf_confirm']==1 && is_numeric($_GET['nf_confirm']) && is_numeric($_GET['nf_f']) && isset($_GET['nf_c'])) {
			$nfc = $_GET['nf_c'];
			$formid= $_GET['nf_f'];
			
			$args = array(
				'post_type' => 'nf_sub',
				'meta_query' => array(
					array(
						'key' => '_confirmation_status',
						'value' => 0
					),
					array(
						'key' => '_confirmation_uniqid',
						'value' => $nfc
					),				
				),
			);

			
			$q = new WP_Query($args);
			
			if ( $q->have_posts() ) {
				while ( $q->have_posts() ) {
					$q->the_post();
					$subid = get_the_ID();

					$sub = Ninja_Forms()->form()->sub( $subid )->get();
					$formid =  $sub->get_extra_value('_form_id');	
					$actionsettings = $this->get_action_settings($formid);	
					update_post_meta($subid,'_confirmation_status',1);
					
					// hook::after confirmed succcess,
					do_action('nf_confirmmail_confirmed_after_success', $formid, $actionsettings, $sub);
					do_action('nf_confirmmail_confirmed_after_success_'.$formid, $formid, $actionsettings, $sub);
					
					// after confirmation, do delete
					if ((int)$actionsettings['confirmation_delete'] == 1) {
						$sub->delete();
						do_action('nf_confirmmail_delete_after_confirmed',$formid);
					}
					
					// if notify is on, send notification
					if ((int)$actionsettings['notify_enable'] == 1) {
							$headers = array();
							$to = $actionsettings['notify_mail'];
							global $subseq;
							$subseq=array(
								"to" => $to,
								"link" => get_edit_post_link($subid)
							);
							$mailtxt = apply_filters('ninja_forms_merge_tags',$actionsettings['notify_message']);
							$subject = $actionsettings['notify_subject'];
					
							$headers[] = 'Content-Type: text/' . $actionsettings[ 'notify_email_format' ];
							$headers[] = 'charset=UTF-8';
					
							$headers[] = "From: ".$actionsettings[ 'from_name' ]." <".$actionsettings[ 'from_address' ].">";
							$headers[] = "Reply-to: ".$actionsettings[ 'from_name' ]." <".$actionsettings[ 'from_address' ].">";
					
							// SEND notification mail
							add_filter( 'wp_mail_content_type', array($this,'set_html_mail_content_type') );
							wp_mail($to,$subject,$mailtxt,$headers);
							remove_filter( 'wp_mail_content_type', array($this,'set_html_mail_content_type') );
					
					}
					
					// redirect
					$_this=$this;
					$redirecturl = apply_filters('ninja_forms_merge_tags',$actionsettings['redirectaftersuccess']);
					$redirecturl = preg_replace_callback('/{(.+?)}/',function($matches) use($_this,$sub) {
						$val = $sub->get_field_value($_this->get_field_name("{".$matches[1]."}"));
						return $val;
					},$redirecturl);					
					wp_redirect($redirecturl); 	
				}
				wp_reset_postdata();
				exit();
			} else {

				$args = array(
					'post_type' => 'nf_sub',
					'meta_query' => array(
						array(
							'key' => '_confirmation_uniqid',
							'value' => $nfc
						),									
					),
				);
	
				$q2 = new WP_Query($args);
				if ( $q2->have_posts() ) {
					// already confirmed
					while ( $q2->have_posts() ) {
						$q2->the_post();
						$actionsettings = $this->get_action_settings($formid);
						$redirecturl = apply_filters('ninja_forms_merge_tags',$actionsettings['redirectafteralreadyconfirmed']);
						wp_redirect($redirecturl); 
						exit();
					}
					wp_reset_postdata();					
				} else {
					$actionsettings = $this->get_action_settings($formid);
					$redirecturl = apply_filters('ninja_forms_merge_tags',$actionsettings['redirectaftererror']);
					wp_redirect($redirecturl);
					exit();
				}
				

			}
	
		} 
		
	}
	
	/**
	 * setup the plugin
	 *
	 * @param none
	 * 
	 * @return none
	 */ 
	function setup_plugin() {

		// change table head from submissions
		add_filter('manage_nf_sub_posts_columns', array($this,'submissions_table_head'),110,1);
		add_action('manage_nf_sub_posts_custom_column', array($this,'submissions_custom_columns'), 5, 2);
		
		// add settings group
		add_filter( 'ninja_forms_field_settings_groups', function($groups) {
			$groups['redirect'] = array( 'id' => 'redirect', 'label' => __('Redirect settings','nf-confirm-mail'), 'display' => FALSE, 'priority' => 120 );
			$groups['reminder'] = array( 'id' => 'reminder', 'label' => __('Reminder settings','nf-confirm-mail'), 'display' => FALSE, 'priority' => 120 );
			$groups['confirmation'] = array( 'id' => 'confirmation', 'label' => __('After confirmation settings','nf-confirm-mail'), 'display' => FALSE, 'priority' => 120 );
			$groups['notification'] = array( 'id' => 'notification', 'label' => __('Notification settings','nf-confirm-mail'), 'display' => FALSE, 'priority' => 120 );			
			return $groups;
		}, 10, 1 ); 
		
		// add merge tags
		require_once(realpath(plugin_dir_path(__FILE__))."/includes/".'class.mergetags.php');
		Ninja_Forms()->merge_tags[ 'nfconfirm_merge_tags' ] = new NFconfirm_merge_tags();

	}
	
	/**
	 * Get action setting from form
	 *
	 * @param $form_id
	 * 
	 * @return $settings array()
	 */ 
	function get_action_settings($form_id) {
		$actions = Ninja_Forms()->form( $form_id )->get_actions();
		
		foreach($actions as $action) {
			$type = $action->get_setting( 'type' );
			if ($type=='Confirmmailwithlink') {
				$settings=$action->get_settings();
			}
		}
		return $settings;
	}

	/**
	 * Get field name from tag
	 *
	 * @param $tag
	 * 
	 * @return field_name
	 */ 
	function get_field_name($tag) {
		$tag = str_replace("{","",$tag);
		$tag = str_replace("}","",$tag);
		$fields = explode(":",$tag);
		//$f = explode("_",$fields[1]);
		return $fields[1];
	}

	/**
	 * Activate WP-Cron entry for reminder on activation
	 *
	 * @param none
	 * 
	 * @return none
	 */ 
	function nfcm_cron_activation() {
		$event = wp_get_schedule('nf-confirmmail-reminder');
		
		// remove old cron from previous plugin version
		if ($event=='daily') { 
			$timestamp = wp_next_scheduled ('nf-confirmmail-reminder');
			wp_unschedule_event ($timestamp, 'nf-confirmmail-reminder');
		}
		
		// set cron for next check
		if( !wp_next_scheduled( 'nf-confirmmail-submission-cleaning' ) ) {  
		   wp_schedule_event( time(), 'hourly', 'nf-confirmmail-submission-cleaning' );  
		}
		if( !wp_next_scheduled( 'nf-confirmmail-reminder' ) ) {  
		   wp_schedule_event( time(), 'hourly', 'nf-confirmmail-reminder' );  
		}		
	}

	/**
	 * Deactivate WP-Cron entry for reminder on deactivation
	 *
	 * @param none
	 * 
	 * @return none
	 */ 
	function nfcm_cron_deactivate() {	
		$timestamp = wp_next_scheduled ('nf-confirmmail-reminder');
		wp_unschedule_event ($timestamp, 'nf-confirmmail-reminder');
		$timestamp = wp_next_scheduled ('nf-confirmmail-submission-cleaning');
		wp_unschedule_event ($timestamp, 'nf-confirmmail-submission-cleaning');
	} 
	
	/**
	 * Send a reminder mail
	 *
	 * @param none
	 * 
	 * @return none
	 */ 
	function send_reminder_mail () {
		$id = $_POST['reminderid'];
		$msg = $this->send_reminder($id);
		echo $msg;
		die();	
	}
	
	/**
	 * Function to check if reminder mail has to be send
	 *
	 * @param none
	 * 
	 * @return none
	 */ 
	function nfcm_cron_reminder_check() {
		
		// get check limit value
		$checklimit =  Ninja_Forms()->get_setting( 'check-limit');
		
		$args = array(
			'post_type' => 'nf_sub',
			'order' => 'ASC',
			'posts_per_page' => ($checklimit==''?$this->default_check_limit:$checklimit), // don't overload the system, so max. 100 per time
			'meta_query' => array(
				array(
					'key' => '_confirmation_status',
					'value' => 0
				),
				array(
					'key' => '_reminder_status',
					'value' => 0
				),			
			),
		);
		
		$q = new WP_Query($args);
		
		if ( $q->have_posts() ) {
			while ( $q->have_posts() ) {
				$q->the_post();
				$remindertime = get_post_meta(get_the_ID(),'_reminder_status_time', true);
				$remaining = $remindertime - time();
				$days_remaining = floor($remaining / 86400);
				$hours_remaining = floor(($remaining % 86400) / 3600);
				if ($days_remaining<=0 && $hours_remaining<=0) {
					$this->send_reminder(get_the_ID());
				}
			}
			wp_reset_postdata();
		}
		die();
	}	
	
	/**
	 * Function to check if submissions with confirmation must be deleted within x days
	 *
	 * @param none
	 * 
	 * @return none
	 */ 	
	function nfcm_cron_submission_cleaning() {
		
		// get check limit value
		$checklimit =  Ninja_Forms()->get_setting( 'check-limit');
		
		$forms = Ninja_Forms()->form()->get_forms();
		
		foreach($forms as $form) {
			$id = $form->get_id();
			$actions = Ninja_Forms()->form($id)->get_actions();
			
			foreach($actions as $action) {
				
				if ($action->get_setting('type')=='Confirmmailwithlink') {

					// clean up submissions when enabled
					if ((int)$action->get_settings('confirmation_delete_when_not_confirmed')!=0) {
						
						$afterdays = (int)$action->get_settings('confirmation_delete_period');
						
						$args = array(
							'post_type' => 'nf_sub',
							'posts_per_page' => ($checklimit==''?$this->default_check_limit:$checklimit), // don't overload the system, so max. 100 per time
							'date_query' => array(
								'before' => date('Y-m-d', strtotime('-'.$afterdays.' days')) 
							),
						);
						
						$q = new WP_Query($args);
						
						if ( $q->have_posts() ) {
							while ( $q->have_posts() ) {
								$q->the_post();
								$sub = Ninja_Forms()->form()->sub( get_the_ID() )->get();
								$sub->delete();
							}
							wp_reset_postdata();
						}
					}
				}
			}
		}
		
		die();
	}

	/**
	 * Send a reminder
	 *
	 * @param $id id from the submission
	 * 
	 * @return none
	 */ 
	function send_reminder($id) {
			global $sub;
			
			$sub = Ninja_Forms()->form()->get_sub( $id );
			
			$form_id = $sub->get_extra_value( '_form_id' );
			$seq_num = $sub->get_extra_value( '_seq_num' );
			
			$settings = $this->get_action_settings($form_id);
			
			$to = $sub->get_field_value( $this->get_field_name($settings['to']) );			

			// ON SUBMIT, GET UNIQUE ID 
			$uniqid = get_post_meta($id,'_confirmation_uniqid',true);

			$mailtxt = $settings['email_message_reminder'];		
			$subject =  $settings['email_subject_reminder'];	

			// REPLACE URL IN MESSAGE
			
			global $subseq;
		
			$subseq=array(
				"subid" => $id,
				"form_id" => $form_id
			);
			$mailtxt = apply_filters('ninja_forms_merge_tags',$mailtxt);
			$subject = apply_filters('ninja_forms_merge_tags',$subject);

			// REPLACE FIELDS
			$_this=$this;
			
			// fix for submission fields
			$mailtxt = preg_replace_callback('/{(.+?)}/',function($matches) use($_this,$sub) {
				$val = $sub->get_field_value($_this->get_field_name("{".$matches[1]."}"));
				return $val;
			},$mailtxt);			

			$subject = preg_replace_callback('/{(.+?)}/',function($matches) use($_this,$sub) {
				$val = $sub->get_field_value($_this->get_field_name("{".$matches[1]."}"));
				return $val;
			},$subject);

			// HEADERS
			$headers = array();
	
			$headers[] = 'Content-Type: text/' . $settings[ 'email_format' ];
			$headers[] = 'charset=UTF-8';
	
			$headers[] = "From: ".$settings[ 'from_name' ]." <".$settings[ 'from_address' ].">";
			$headers[] = "Reply-to: ".$settings[ 'from_name' ]." <".$settings[ 'from_address' ].">";
			if ($settings[ 'cc' ]!='') $headers[] = "CC: ".$settings[ 'cc' ]."";
			if ($settings[ 'bcc' ]!='') $headers[] = "BCC: ".$settings[ 'bcc' ]."";

			// send reminder mail
			add_filter( 'wp_mail_content_type', array($this,'set_html_mail_content_type') );
			wp_mail($to,$subject,$mailtxt,$headers);
			remove_filter( 'wp_mail_content_type', array($this,'set_html_mail_content_type') );
			
			// set status to 1, reminder has been send
			update_post_meta($id,'_reminder_status',1);
			
			$msg = __("Reminder has been send",'nf-confirm-mail');
		return $msg;
	}
	
	/**
	 * Function for filter to change e-mail to text/html content
	 *
	 * @param none
	 * 
	 * @return string text/html
	 */ 
	function set_html_mail_content_type() {
		return 'text/html';
	}

	/**
	 * Register Ninja Forms custom action
	 *
	 * @param $actions
	 * 
	 * @return $actions 
	 */ 
	function register_actions( $actions ) {
	  require_once(realpath(plugin_dir_path(__FILE__))."/includes/"."nf-action-confirmmail.php");
	  $actions['Confirmmailwithlink'] = new NF_Action_ConfirmMail();
	  return $actions;
	}

	/**
	 * Alter submissions table header array with custom columns to show if submission has been confirmed
	 * and if reminder is send or show how many time till reminder is send
	 *
	 * @param $columns
	 * 
	 * @return $columns 
	 */	
	function submissions_table_head( $columns ) {
		$columns['confirmed'] =  __('Confirmed','nf-confirm-mail');
		$columns['reminder'] =  __('Reminder','nf-confirm-mail');
		

		return $columns;							
	}	

	/**
	 * Display output for custom columns, show the status of reminder or confirmation
	 *
	 * @param $column_name, $post_id
	 * 
	 * @return none
	 */	
	function submissions_custom_columns($column_name, $post_id){
		$status = get_post_meta($post_id,'_confirmation_status', true);
		$reminder = get_post_meta($post_id,'_reminder_status', true);
		$remindertime = get_post_meta($post_id,'_reminder_status_time', true);
		$uniqid = get_post_meta($post_id,'_confirmation_uniqid', true);
		

		if($column_name === 'confirmed'){
			if ($status==0) {
				echo '<span class="dashicons dashicons-no" style="font-size: 30px; color: #ff0000"></span>';	
			} elseif ($status==1) {
				echo '<span class="dashicons dashicons-yes" style="font-size: 30px;  color: #00ff00"></span>';
			} else {
				echo '<span class="dashicons dashicons-minus" style="font-size: 30px;"></span>';
			}
		} elseif ($column_name === 'reminder') {
			if ($status==0) {
				echo '<span data-id="'.$post_id.'" class="nfcmsendreminder dashicons dashicons-email-alt" style="font-size: 30px;padding-right: 20px; cursor: pointer; "></span>';
			}
			$remaining = $remindertime - time();
			$days_remaining = floor($remaining / 86400);
			$hours_remaining = floor(($remaining % 86400) / 3600);
			if ($days_remaining<=0) $days_remaining=0;
			if ($hours_remaining<=0) $hours_remaining=0;
			
			if ($reminder==0 && $status==0) {
				echo '<span class="dashicons dashicons-clock" style="font-size: 30px; color: #ff0000"></span>';	
				echo '<small style="padding: 5px 15px;">'.$days_remaining.'d '.$hours_remaining.'h</small>';
			} elseif ($reminder==1 && $status==0) {
				echo '<span class="dashicons dashicons-backup" style="font-size: 30px; color: #00ff00"></span>';
			} else {
				echo '<span class="dashicons dashicons-minus" style="font-size: 30px;"></span>';
			}
		}
	}

  /**
     * Add settings for the plugin
     *
     * this will add the settings box in the settings page. Change limit per cycle for checking reminder
     *
     * @param array $settings
     * @return array $settings
     */
    public function plugin_settings( $settings ) {
        $settings[ 'nf-confirm-and-remind' ] = array(
            'check-limit' => array(
                'id'    => 'check-limit',
                'type'  => 'textbox',
                'label'  => __( 'Limit per cycle', 'nf-confirm-mail' ),
                'desc'  => __( 'Adjust the limit per cycle of the reminder check. Reminder check checks every hour', 'nf-confirm-mail' ),
            ),
        );
        return $settings;
    }

    /**
     * Add plugin settings groups
     *
     * This functions will add a group in the settings page as metabox
     *
     * @param array $groups
     * @return array $groups
     */
    public function plugin_settings_groups( $groups ) {
        $groups[ 'nf-confirm-and-remind' ] = array(
            'id' => 'nf-confirm-and-remind',
            'label' => __( 'NF Confirm & Reminder settings', 'nf-confirm-mail' ),
        );
        return $groups;
    }
	
    /**
     * Check setting for errors
     *
     * Note: This check is done on the moment when the Ninja Forms settings submenu page is loaded.
     *
     * @param array $setting
     * @return array $setting
     */
    public function check_confirmreminder( $setting ) {
        if( $has_errors ) {
            $setting['errors'][] = __('The value you have entered appears to be invalid.', 'nf-confirm-mail');
        }
        return $setting;
    }
	
    /**
     * Update setting Value
     *
     * Before the setting is saved, the value will be updated.
     *
     * @param $setting_value
     * @return setting_value
     */
    public function update_confirmreminder( $setting_value ) {
        $setting_value = trim( $setting_value );
        return $setting_value;
    }
	
    /**
     * Save Setting (NF confirm & reminder key)
     *
     * After the setting is saved, this will be used to integrate the api
     *
     * @param $setting_value
     * @return void
     */
    public function save_confirmreminder( $setting_value ) {
        if( strpos( $setting_value, '_' ) ){
            $parts = explode( '_', $setting_value );

            foreach( $parts as $key => $value ){
                Ninja_Forms()->update_setting( 'nf_confirmreminder_' . $key, $value );
			}
        }
    }	

	/**
	 * Load custom CSS and/or JS for this plugin
	 *
	 * @param none
	 * 
	 * @return none
	 */	
    public function loadCssAndJs() {
	 	wp_enqueue_script( 'NF_confirm_admin_js', plugins_url('assets/admin.js', __FILE__), array('jquery') );
    }

}

/**
 * Init this class
 *
 */	
new NF_confirm_mail();

?>