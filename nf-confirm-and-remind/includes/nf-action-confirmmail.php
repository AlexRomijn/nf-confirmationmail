<?php if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Class NF_Action_ConfirmMail
 */
class NF_Action_ConfirmMail extends NF_Abstracts_Action {
   /**
     * @var string
     */
    protected $_name  = 'Confirmmailwithlink';

    /**
     * @var array
     */
    protected $_tags = array();

    /**
     * @var string
     */
    protected $_timing = 'late';

    /**
     * @var int
     */
    protected $_priority = '20';

    /**
     * Constructor
     */
    public function __construct()    {
        parent::__construct();

		// show the name of the action
        $this->_nicename = __( 'Confirmation & Reminder', 'nf-confirm-mail' );

		// import settings configuration
        $settings = require_once(realpath(plugin_dir_path(__FILE__))."/"."ActionConfirmmailSettings.php");
        $this->_settings = array_merge( $this->_settings, $settings );
		
    }

    /*
    * PUBLIC METHODS
    */
	
  	/**
     * Function to process the action, and send confirmation mail
     *
     * @param $action_settings, $form_id, $data
     * @return $data
     */
    public function process( $action_settings, $form_id, $data )    {
		
		$mailtxt = $action_settings['email_message'];
		$subject = $action_settings['email_subject'];
		$to = $action_settings['to'];
		$time2remind =  $action_settings['time2remind'];
		$redirectto = $action_settings['redirect_to'];
	//	$url = get_site_url()."/?nf_confirm=1&nf_f=".$form_id."&nf_c=";
		
		$subid = $data['actions']['save']['sub_id'];
		
		// is there a submission saved (because this is needed to handle the confirmation
		if ($subid!='') {

			// ON SUBMIT, CREATE UNIQUE ID 
			
			$uniqid = substr(md5(time() . $form_id . $subid),0,15);
			
			// UPDATE SUBMISSION META WITH UNIQID
			$isUniqid = get_post_meta($subseq['subid'],'_confirmation_uniqid',true);
			if (isUniqid=='') {
				update_post_meta($subid,'_confirmation_uniqid',$uniqid);
			} else {
				$uniqid = $isUniqid;
			}
			update_post_meta($subid,'_confirmation_status',0); // 0 = created & send, not confirmed

			// SAVE EMAIL FOR REMINDER
			update_post_meta($subid,'_confirmation_reminder_email',$to);
			
			// FIRST SEND, SET REMINDER META
			$daysinsec = $time2remind * 86400; // 3600 sec = 1 hour * 24 = 1 day = 86400
			$time2sendreminder = time() + $daysinsec;
			
			update_post_meta($subid,'_reminder_status',0); // 0 = created & send, no reminder send
			update_post_meta($subid,'_reminder_status_time',$time2sendreminder); // 0 = created & send, no reminder send
			
			// REPLACE URL IN MESSAGE
			$url = $url . $uniqid;
			//$mailtxt = str_replace("{user:confirmurlonly}",$url,$mailtxt);		
			//$mailtxt = str_replace("{user:confirmurl}","<a href=\"".$url."\">".$url."</a>",$mailtxt);		
			
			$headers = array();
	
			$headers[] = 'Content-Type: text/' . $action_settings[ 'email_format' ];
			$headers[] = 'charset=UTF-8';
	
			$headers[] = "From: ".$action_settings[ 'from_name' ]." <".$action_settings[ 'from_address' ].">";
			$headers[] = "Reply-to: ".$action_settings[ 'from_name' ]." <".$action_settings[ 'from_address' ].">";
			if ($action_settings[ 'cc' ]!='') $headers[] = "CC: ".$action_settings[ 'cc' ]."";
			if ($action_settings[ 'bcc' ]!='') $headers[] = "BCC: ".$action_settings[ 'bcc' ]."";
	
			// SEND confirmation mail
			add_filter( 'wp_mail_content_type', array($this,'set_html_mail_content_type') );
			wp_mail($to,$subject,$mailtxt,$headers);
			remove_filter( 'wp_mail_content_type', array($this,'set_html_mail_content_type') );
		}
		
        return $data;
    }
	
  	/**
     * Function to change content type of email
     *
     * @param none
     * @return string
     */
	function set_html_mail_content_type() {
		return 'text/html';
	}	
}

?>