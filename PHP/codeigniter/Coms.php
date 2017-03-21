<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Coms extends CI_Controller {
	public function __construct()
    {
		parent::__construct();
		$this->isLogged();
		$this->load->model('coms_model');
		$this->load->model('documents_model');
		$this->load->model('company_model');
		$this->load->model("developer_model");
		$this->load->model('patient_model');
		$this->load->model('provider_directory_model');
		$this->load->model('phpmailer_model');
    }

    private function isLogged()
    {
    	$entity = $this->session->userdata('entity');
    	$isLogged = $this->session->userdata('is_logged_entity');
    	if(isset($entity) && isset($isLogged) && ($entity == 'patient' || $entity == 'therapist' || $entity == 'supervisor' || $entity == 'admin' || $entity == 'developer' || $entity == 'system_developer') ){
    	}else{
    		$this->session->set_flashdata('message','You do not have permission to access this area.');
    		$this->session->set_flashdata('messagetype','error');
    		redirect('login');
    	}
    }

	public function sendPm($toDraft = false)
	{
		// user_ids
		$_POST["pm_to"] = explode(',', $this->input->post("pm_to"));
		$_POST["document"] = '';
		$_POST["subject"] = $this->input->post("pm_subject");
		$_POST["message"] =$this->input->post("pm_message");
		$_POST["message-id"] = ''; 
		
		$this->new_sendPM();
	}

	public function sendNotificationEmail($scope, $from_id, $from_role, $to_id, $to_role, $time, $to_email, $CC = null) {


            return $this->coms_model->sendNotificationEmail($scope, $from_id, $from_role, $to_id, $to_role, $time, $to_email, $CC = null);
	}

	/*
	 * mark pm as read
	 * */
	public function markAsRead() {
		$data['status'] = 0;
		/*Get time stamp*/
		$datestring = "%Y-%m-%d %h:%i:%s";
		$time = time();
		$time_read = mdate($datestring, $time);
		$pm_id = $this->input->post('pmId');
		if($this->input->post('ajax') == '1') {
			if($this->coms_model->markAsRead($pm_id, $time_read)== true) {
				$data['status'] = 1;
			}
		}
		echo json_encode($data);
		exit();
	}

	public function getCountUnreadMessages (){

		$data['unread_count'] = $this->coms_model->getCountUnreadMessages($this->session->userdata("cmn_user_id"));
		echo json_encode($data);
	}

	/**
	* @param void
	* @return json array(documents)
	*/
	function getAllDocumentsByMessage(){
		$documents = $this->documents_model->getAllDocumentsByMessage($this->input->post("pmId"), $this->session->userdata("my_id"));
		echo json_encode($documents);
	}
	
	/**
	* @param ($_POST)
	*/
	function getMore(){
		$from = $this->input->post("iDisplayStart");
		$length = $this->input->post("iDisplayLength");
		$search = $this->input->post("sSearch");
		$tab = $this->input->post("tabName");
		$TDsort = intval($this->input->post("iSortCol_0"));
		if(!preg_match("/^(inbox|sent_box|trash|draft)$/", $tab))
			return json_encode(array());
		$sortBy = $this->input->post("sSortDir_0");

		if($TDsort == 1)
			$TDsort = "full_name"; 
		if($TDsort == 2)
			$TDsort = "subject";
		if($TDsort == 3)
			$TDsort = "date";
		if($TDsort == 4)
			$TDsort = "time";
		if($TDsort == 5)
			$TDsort = "is_read";

		$pm_inbox = $this->coms_model->new_getAllPmsToMe($tab, $from, $length, $search, $TDsort, $sortBy)->result_array();
		$count = $this->coms_model->selectFoundRows();
		
		$data["sEcho"]  = $this->input->post("sEcho");
		$data["iTotalRecords"] = $count;
		$data["iTotalDisplayRecords"] = $count;
		$data["aaData"] = Array();
		for($i = 0; $i < count($pm_inbox); $i++)
		{
			$senderId = isset($pm_inbox[$i]['sender_user_id']) ? $pm_inbox[$i]['sender_user_id']: "";
			$public_tocken = isset($pm_inbox[$i]['public_tocken']) ? $pm_inbox[$i]['public_tocken']: "";

			if( $pm_inbox[$i]['public_name'] != "" )
				$pm_inbox[$i]['full_name'] = $pm_inbox[$i]['public_name'];

			$data["aaData"][] = array(
				"<input name='pmid".$pm_inbox[$i]['id']."' value='".$pm_inbox[$i]['id']."'  type='checkbox'>",
				$pm_inbox[$i]['full_name'],
				$pm_inbox[$i]['subject'],
				$pm_inbox[$i]['date'],
				$pm_inbox[$i]['time'],
				$pm_inbox[$i]['status'],
				'<table style="width:100%; display: none;">
					<tr class="message_'.$pm_inbox[$i]['id'].'" data-sender-id="'.$senderId.'" data-public-tocken="'. $public_tocken .'">
						<td>
							<div class="pull-left">'.$pm_inbox[$i]['message'].'</div>
							<div class="files">&nbsp;</div>
						</td>
						<td>
							<a class="btn btn-primary btn-sm read-unread-message pull-right reply-button" data-mess-id="'.$pm_inbox[$i]['id'].'">Reply</a>
						</td>
					</tr>
				</table>',
				$pm_inbox[$i]['is_replied']
			);
		}
		echo json_encode($data);
	}

	// Sending message to trash 
	function new_sendToTrash(){
		// If not ajax go away
		if(!$this->input->is_ajax_request())
			redirect("/");

		$messages = Array();

		foreach ($this->input->post() as $key => $value)
			if(preg_match("/^pmid/", $key))
				$messages[] = $value;

		if($this->coms_model->new_sendToTrash($this->input->post('moved'), $messages))
			echo json_encode(array("status" => 1, "message" => "All selected messages were sent to trash."));
		else
			echo json_encode(array("status" => 0, "message" => "Messages not deleted."));
	}

	// Restore message
	function new_restoreMessage(){
		// If not ajax go away
		if(!$this->input->is_ajax_request())
			redirect("/");

		$data['status'] = 0;

		foreach ($this->input->post() as $key => $message_id) {
			// We need only messages
			if(preg_match("/^pmid/", $key)){

				// Getting type message
				$message = $this->coms_model->getMessageById($message_id);

				// Get what type message
				if($message[0]['is_draft'])
					$type = "draft";
				else if($this->session->userdata('my_id') == $message[0]['from_id'] && strtolower($this->session->userdata('entity')) == strtolower($message[0]['from_role']))
					$type = "sent_box";
				else
					$type = "inbox";
				// Get column name by type message
				$column = $this->coms_model->getComlumnByFrom($type, FALSE);
				
				// Updating table
				if($this->coms_model->new_restoreMessage($column, $message_id))
					$data['status'] = 1;
			}
		}
		if($data['status'])
			$data["message"] = "All selected messages were moved to (inbox).";
		else
			$data["message"] = "Messages not removed from trash.";
		echo json_encode($data);
	}

	// Sending message to trash 
	function new_deleteMessage(){
		// If not ajax go away
		if(!$this->input->is_ajax_request())
			redirect("/");

		$data['status'] = 0;
		foreach ($this->input->post() as $key => $message_id) {
			// We need only messages
			if(preg_match("/^pmid/", $key)){

				// Getting type message
				$message = $this->coms_model->getMessageById($message_id);

				// Get what type message
				if($message[0]['is_draft'])
					$type = "draft";
				else if($this->session->userdata('my_id') == $message[0]['from_id'] && strtolower($this->session->userdata('entity')) == strtolower($message[0]['from_role']))
					$type = "sent_box";
				else
					$type = "inbox";

				// Get column name by type message
				$column = $this->coms_model->getComlumnByFrom($type, TRUE);
				// Updating table
				if($this->coms_model->new_deleteMessage($column, $message_id))
					$data['status'] = 1;
			}
		}
		if($data['status'])
			$data["message"] = "All selected messages where permanently deleted.";
		else
			$data["message"] = "Messages not deleted.";
		echo json_encode($data);
	}

	/**
	* AJAX Send PM
	* @param bool $to_draft
	*
	*/
	function new_sendPM($to_draft = false){

		// If not ajax go away
		if(!$this->input->is_ajax_request())
			redirect("/");

		// Initialize variables 
		$to_draft = intval($to_draft);
		$user_ids = $this->input->post("pm_to");
		$document_ids = $this->input->post("document");
		$flags = Array();
		$emails = Array();
		$subject = $this->input->post("subject");
		$message = $this->input->post("message");
		$message_id = $this->input->post("message-id");
		$public_tocken = $this->input->post("public_tocken");

                if($public_tocken) {

                    // get user by public_tocken
                    $publick_messages = $this->provider_directory_model->getMessagesByToken($public_tocken);
                    if($publick_messages) {

                        // send notify to client
                        $subject = "Adaptive Telehealth Notification";
                        $link = base_url("/index.php/provider-directory/messages") . "/" . $public_tocken;
                        $msg = "Dear " . $publick_messages[0]["client_first_name"] . " " . $publick_messages[0]["client_last_name"] . ",<br><br>";
                        $msg .= "New Message: You received a new private message, please log in to your account at Adaptive Telehealth to see this message.<br>";
                        $msg .= "Your login: " . $publick_messages[0]["client_email"] . "<br>";
                        $msg .= "<a href='" . $link . "'>" . $link . "</a>";
                        $msg .= "<br><br>";
                        $msg .= "Respectfully<br>";
                        $msg .= "The staff.";
                        $data = array(
                            "subject" => $subject,
                            "message" => $msg
                        );
                        $this->phpmailer_model->sendNotificationEmail($data, null, 'therapist', null, 'patient', now(), $publick_messages[0]["client_email"]);

                    }
                }
		
		// validation
		$this->form_validation->set_rules("subject", "", "required");
		$this->form_validation->set_rules("message", "", "required");
		$this->form_validation->set_rules("pm_to", "", "required");
		$responce['status'] = $this->form_validation->run();

		if ($responce['status'] === FALSE)
		{ 
			$responce['message'] = validation_errors();
			echo json_encode($responce);
			die;
		}
		
		// Getting data from POST
		foreach ($user_ids as $key => $value) {
			
			// Getting flags (send to all or something else)

			if($value == "all_patient") { // supervisor/admin
				$flags[] = $value;
				unset($user_ids[$key]);
			}
			elseif($value == "all_therapists") { // supervisor/admin
				$flags[] = $value;
				unset($user_ids[$key]);
			}
			elseif($value == "all_staff") { // system_developer/developer
				$flags[] = $value;
				unset($user_ids[$key]);
			}
			elseif($value == "all_collaborator") { // supervisor/admin/system_developer/developer
				$flags[] = $value;
				unset($user_ids[$key]);
			}
			elseif($value == "all_carecoordinator") { // supervisor/admin/system_developer/developer
				$flags[] = $value;
				unset($user_ids[$key]);
			}
		}	

		$this->setUsersFromFlags( $user_ids, $flags );

		
		
		// Get All emails
		$user = $this->developer_model->getUserDataByIds(implode(",", $user_ids));

		// What is follow_up_id?
		$parent_id = "";

		for($i = 0; $i <= count( $user ); $i++)
		{
			if(!isset($user[$i]))
			{
				continue;
			}
			// Send PM
			$post = array(
				// Form
				"from_user_id" =>  $this->session->userdata("cmn_user_id"),
				// "from_id" => $this->session->userdata('my_id'),
				// "from_role" => $entity,
				
				// // To
				"to_user_id" =>  ( $user[$i]['id'] == "" ? NULL : $user[$i]['id'] ) ,
				// "to_id" => $user[$i]['self_id'],
				// "to_role" => strtolower($user[$i]['role']),

				// Message
				"subject" 		=> $subject,
				"message" 		=> $message,
				// system
				"is_draft" 		=> $to_draft,
				"message_scope" => "pm",
				// Need for CC
				"parent_id" 	=> $parent_id,
				"public_tocken" => $public_tocken
			);
			// send notification
			$this->coms_model->systemNotification(array(
				"user_id" => $this->session->userdata("my_id"),

				"user_role" => $this->session->userdata("entity"),

				"alert_id" => ( $user[$i]['self_id'] == "" ? NULL : $user[$i]['self_id'] ),

				"alert_role" => strtolower($user[$i]['role']),

				"message" => "You have new message.",
				
				"created_at" => date("Y-m-d H:i:s")
			));

			$parent_id = $this->coms_model->new_addPrivateMessage($post);
			if($parent_id !== FALSE)
			{
				// Shared documents
				if(!empty($document_ids)){
					foreach ($document_ids as $key => $document_id) {
						
						if(strtolower($user[$i]['role']) != 'patient' && strtolower($user[$i]['role']) != "collaborator" && strtolower($user[$i]['role']) != "carecoordinator")
						{
							// Staff
							if(!$this->documents_model->is_document_shared_muse($document_id, $user[$i]["self_id"]))
								$this->documents_model->share_document_muse($document_id, $user[$i]["self_id"], $to_draft);
							$this->documents_model->shareInMessage($parent_id, $document_id, $to_draft);
						}
						else
						{
							// Clients
							if(!$this->documents_model->is_document_shared($document_id, $user[$i]["self_id"])) 
								$this->documents_model->share_document($document_id, $user[$i]["self_id"], $to_draft);
							$this->documents_model->shareInMessage($parent_id, $document_id, $to_draft);
						}
					}
				}
			}
			$emailNotification = $this->sendNotificationEmail('pm', $this->session->userdata("my_id"), $this->session->userdata("entity"), $user[$i]["self_id"], $user[$i]["role"], date("Y-m-d h:i:s"), $user[$i]["email"]);
		}

		// if draft message mark as sended
		if(!empty($message_id)) {

			// Updating table
			$this->coms_model->new_deleteMessage("sender_is_deleted", $message_id);
		}

		if($to_draft)
			$responce['message'] = "The draft was saved.";
		else
			$responce['message'] = "The data was saved. ".$emailNotification;
		echo json_encode($responce);
	}

	function new_sendToDraft() {
		$this->new_sendPM(true);
	}
	function new_sendToReply() {
		$this->coms_model->markAsReplied($this->input->post('message_id'));
		$this->new_sendPM(false);
	}
	/**
	* Click on button Read/Unread
	*/
	function markAsReadUnread(){
		// If not ajax go away
		if(!$this->input->is_ajax_request())
			redirect("/");

		$datestring = "%Y-%m-%d %h:%i:%s";
		$time = time();
		$time_read = mdate($datestring, $time);


		foreach ($this->input->post() as $key => $message_id) {
			if(preg_match("/^pmid/", $key)){
				if($this->input->post('markAs') === 'unread')
					$data['status'] = $this->coms_model->markAsUnRead($message_id);
				else if($this->input->post('markAs') === 'read')
					$data['status'] = $this->coms_model->markAsRead($message_id, mdate("%Y-%m-%d %h:%i:%s", time()));
			}
		}
		echo json_encode($data);

	}

	function getMessageById(){
		$message = $this->coms_model->getMessageById($this->input->post("message-id"));
		$message = $message[0];
		$message['documents'] = $this->documents_model->getAllDocumentsByMessage($this->input->post("message-id"), $this->session->userdata("my_id"));
		echo json_encode($message);
	}
	
	private function setUsersFromFlags ( &$users, $flags) {

		if(!empty($flags))
		{
			if($this->session->userdata("entity") == "therapist")
				$all_contacts = $this->coms_model->getNewTherapistPmContacts($this->session->userdata("cmn_user_id"));
			else if($this->session->userdata("entity") == "supervisor")
				$all_contacts = $this->coms_model->getNewSupevisorPmContacts($this->session->userdata('cmn_user_id'));
			else if($this->session->userdata("entity") == "admin")
				$all_contacts = $this->coms_model->getNewAdminPmContacts($this->session->userdata('cmn_user_id'));
			else if($this->session->userdata("entity") == "developer")
				$all_contacts = $this->coms_model->getNewDeveloperPmContacts($this->session->userdata('cmn_user_id'));				
			else if($this->session->userdata("entity") == "system_developer")
				$all_contacts = $this->coms_model->getNewSysDeveloperPmContacts($this->session->userdata('cmn_user_id'));

			foreach ($flags as $flag) {
			
				for($i = 0; $i < count($all_contacts); $i++) {

					if($flag == "all_patient" && $all_contacts[$i]['role_name'] == "Patient")
						$users[] = $all_contacts[$i]['user_id'];
					else if($flag == "all_therapists" && $all_contacts[$i]['role_id'] == 3)
						$users[] = $all_contacts[$i]['user_id'];
					else if($flag == "all_collaborator" && $all_contacts[$i]['role_name'] == "Collaborator")
						$users[] = $all_contacts[$i]['user_id'];
					else if($flag == "all_carecoordinator" && $all_contacts[$i]['role_name'] == "Care Coordinator")
						$users[] = $all_contacts[$i]['user_id'];
					else if($flag == "all_staff" && in_array($all_contacts[$i]['role_id'], array(1,2,3)))
						$users[] = $all_contacts[$i]['user_id'];
				}
			}
		}
	}
}