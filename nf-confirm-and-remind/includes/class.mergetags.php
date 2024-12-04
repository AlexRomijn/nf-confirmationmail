<?php
class NFconfirm_merge_tags extends NF_Abstracts_MergeTags {

  protected $id = 'nfconfirm_merge_tags';
  
  protected $sub_seq;
  protected $sub_id;
  protected $form_id;
  
  public function __construct()   {
    parent::__construct();
    
    /* Group name translatabele string */
    $this->title = __( 'Confirmation & Reminder', 'nf-confirm-mail' );
    
    /* Register tag */
    $this->merge_tags = array(
        'confirmed_user' => array(
          'id' => 'confirmed',
          'tag' => '{user:confirmed}', // The tag to be replaced
          'label' => __( 'Confirmed user', 'nf-confirm-mail' ), // Tag label
          'callback' => 'confirmed_user' // Callback function
      	),
        'confirm_link' => array(
          'id' => 'confirmurl',
          'tag' => '{user:confirmurl}', // The tag to be replaced
          'label' => __( 'Confirm url', 'nf-confirm-mail' ), // Tag label
          'callback' => 'confirm_url' // Callback function
      	),
        'confirm_link_only' => array(
          'id' => 'confirmurlonly',
          'tag' => '{user:confirmurlonly}', // The tag to be replaced
          'label' => __( 'Confirm url (url only)', 'nf-confirm-mail' ), // Tag label
          'callback' => 'confirm_url_only' // Callback function
      	),			
    ); 
	
	add_action( 'ninja_forms_save_sub', array( $this, 'setSub' ) );
	
  }

    /**
     * @return mixed
     */
	
    public function getSubId()  {
        return $this->sub_id;
    }
	
	public function getFormId() {
		return $this->form_id;	
	}

    /**
     * @param mixed $sub_seq
     */
    public function setSub( $sub_id ) {
        $sub = Ninja_Forms()->form()->sub( $sub_id )->get();
		$this->sub_id = $sub_id;
		$this->form_id = $sub->get_extra_value( '_form_id' );
    }

	
  /**
   * The callback function for {confirmed_user} merge tag.
   * @return string
   */
  public function confirmed_user()  {
	global $subseq;
    return "<a href=\"".$subseq["link"]."\">".$subseq["to"]."</a>";
  }
  
  /**
   * The callback function for {confirm_url} merge tag.
   * @return string
   */
  public function confirm_url()  {
	global $subseq;
	
	$subid = $this->getSubId();
	
	if ($subseq['subid']=='') {
		$subseq['form_id'] = $this->getFormId();
		$subseq['subid'] = $subid;	
	}
	$uniqid = get_post_meta($subseq['subid'],'_confirmation_uniqid',true);
	if ($uniqid=='') {
		$uniqid = substr(md5(time() . $this->getFormId() . $subid),0,15);
		update_post_meta($subid,'_confirmation_uniqid',$uniqid);
	}
	$url = get_site_url()."/?nf_confirm=1&nf_f=".$subseq['form_id']."&nf_c=";
	$url = $url . $uniqid;
    return "<a href=\"".$url."\">".$url."</a>";
  }  
  
  /**
   * The callback function for {confirm_url_only} merge tag.
   * @return string
   */
  public function confirm_url_only($args)  {
	global $subseq;
	
	$subid = $this->getSubId();
	
	if ($subseq['subid']=='') {
		$subseq['form_id'] = $this->getFormId();
		$subseq['subid'] = $subid;	
	}
	$uniqid = get_post_meta($subseq['subid'],'_confirmation_uniqid',true);
	if ($uniqid=='') {
		$uniqid = substr(md5(time() . $this->getFormId() . $subid),0,15);
		update_post_meta($subid,'_confirmation_uniqid',$uniqid);
	}
	$url = get_site_url()."/?nf_confirm=1&nf_f=".$subseq['form_id']."&nf_c=";
	$url = $url . $uniqid;
    return $url;
  }    
}
?>