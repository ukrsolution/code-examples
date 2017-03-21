<div class="row content_header">
	<div class="col-lg-12">
		<span class="page-title">Messaging</span>
	</div>
</div>

<div class="row hidden">
	<div class="col-lg-12">
		<section class="section">
			<div class="conteiner">
				<div class="alert" id="alert_message"></div>
			</div>
		</section>
	</div>
</div>

<div class="row" id="messaging">
	<div class="col-lg-6 read-box">
		<section class="section">
			<div class="row">
				<div class="col-lg-12">
					<div class="panel-message">
						<div class="row">
							<div class="col-lg-2">
								<ul role="tablist" class="nav-message-menu">
									<li role="presentation" class="text-right col-xs-3 col-lg-12 active">
										<a href="#Inbox" id="Inbox-a" role="tab" aria-controls="inbox" aria-expanded="true" data-toggle="tab">Inbox</a>
									</li>
									<li role="presentation" class="text-right col-xs-3 col-lg-12">
										<a href="#Sent_Box" id="Sent_Box-a" role="tab" aria-controls="sent_box" aria-expanded="true" data-toggle="tab">Sent</a>
									</li>
									<li role="presentation" class="text-right col-xs-3 col-lg-12">
										<a href="#Draft" id="Draft-a" role="tab" aria-controls="draft" aria-expanded="true" data-toggle="tab">Draft</a>
									</li>
									<li role="presentation" class="text-right col-xs-3 col-lg-12">
										<a href="#Trash" id="Trash-a" role="tab" aria-controls="trash" aria-expanded="true" data-toggle="tab">Trash</a>
									</li>
								</ul>
							</div>
							<div class="col-lg-10">
								<div class="nav-message-box">
									<div class="tab-content">
										<div role="tabpanel" class="tab-pane fade active in" id="Inbox" area-labelledby="Inbox-a" data-for="inbox">
											<?php $this->load->view("messaging/tab_view", array('tab_name' => "inbox", "messages" => $pm_inbox));?>
										</div>
										<div role="tabpanel" class="tab-pane fade" id="Sent_Box" area-labelledby="Sent_Box-a" data-for="sent_box">
											<?php $this->load->view("messaging/tab_view", array('tab_name' => "sent box", "messages" => $pm_inbox));?>
										</div>
										<div role="tabpanel" class="tab-pane fade" id="Draft" area-labelledby="Draft-a" data-for="draft">
											<?php $this->load->view("messaging/tab_view", array('tab_name' => "draft", "messages" => $pm_inbox));?>
										</div>
										<div role="tabpanel" class="tab-pane fade" id="Trash" area-labelledby="Trash-a" data-for="trash">
											<?php $this->load->view("messaging/tab_view", array('tab_name' => "trash", "messages" => $pm_inbox));?>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
	</div>
	<div class="col-lg-6 message-box" style="display: none;">
		<div class="blocked" style="display:none;"></div>
		<section class="section send-message">
			<form id="form_message">
				<input type="hidden" name="message-id" />
				<div class="display_table message_component">
					<div class="display_table_cell first">
						<label>Send To:</label>	
					</div>
					<!-- Send to -->
					<div class="display_table_cell">
						<select name="pm_to[]" multiple="" data-placeholder="Click to add a Recipient">
							<?php /*---------------------------------------Staff---------------------------------------------------*/
							if (in_array($this->session->userdata('entity_role'), array(1,2,3,5,7))):
								// For developers & system developers
								if (in_array($this->session->userdata('entity_role'), array(5,7))):?>

									<!-- All developers-->
									<optgroup label='Developers'>
										<?php for($i = 0;$i < count($pm_members); $i++):
											// continue to myself
											if($pm_members[$i]['muse_team_id'] === NULL || $this->session->userdata('entity_role') == $pm_members[$i]['role_id'] && $this->session->userdata('my_id') == $pm_members[$i]['muse_team_id']){
												continue;
											}
											if($pm_members[$i]['role_id'] == 5):
											?>
											<option value="<?php echo $pm_members[$i]['user_id'];?>"><?php echo $pm_members[$i]['full_name']." ( ".$pm_members[$i]['role_name']." ) ";?></option>
										<?php endif;
										endfor; ?>
									</optgroup>
									<!-- All developers-->

									<!-- All system developers -->
									<optgroup label='System Developers'>
										<?php for($i = 0;$i < count($pm_members); $i++):
											// continue to myself
											if($pm_members[$i]['muse_team_id'] === NULL || $this->session->userdata('entity_role') == $pm_members[$i]['role_id'] && $this->session->userdata('my_id') == $pm_members[$i]['muse_team_id']){
												continue;
											}
											if($pm_members[$i]['role_id'] == 7):
											?>
											<option value="<?php echo $pm_members[$i]['user_id'];?>"><?php echo $pm_members[$i]['full_name']." ( ".$pm_members[$i]['role_name']." ) ";?></option>
										<?php endif;
										endfor; ?>
									</optgroup>
									<!-- All system developers -->
								<?php endif;?>

								<!-- All staff start-->
								<optgroup label='Staff'>
									<?php 
									$staffCounter = 0;
									$therapistCounter = 0;
									for($i = 0;$i < count($pm_members); $i++):
										// continue to myself
										if($pm_members[$i]['muse_team_id'] === NULL || $this->session->userdata('entity_role') == $pm_members[$i]['role_id'] && $this->session->userdata('my_id') == $pm_members[$i]['muse_team_id']){
											continue;
										}
										// continue if developer  and system developer
										if($pm_members[$i]['patient_id'] !== NULL || $pm_members[$i]['role_id'] == 5 || $pm_members[$i]['role_id'] == 7){
											continue;
										}
										$staffCounter++;
										if($pm_members[$i]['role_id'] == 3)
											$therapistCounter++;
										?>
										<option value="<?php echo $pm_members[$i]['user_id'];?>"><?php echo $pm_members[$i]['full_name']." ( ".$pm_members[$i]['role_name']." ) ";?></option>
									<?php endfor; ?>
								</optgroup>
								<!-- All staff start-->

								<!-- Care Coordinators -->
								<optgroup label='Care Coordinators'>
									<?php
									$CarecoordinatorCounter = 0;
									for($i = 0;$i < count($pm_members); $i++):
										if($pm_members[$i]['role_name'] == 'Care Coordinator'):
											$CarecoordinatorCounter++;?>

											<option value="<?php echo $pm_members[$i]['user_id'];?>"><?php echo $pm_members[$i]['full_name']." ( ".$pm_members[$i]['role_name']." ) ";?></option>

										<?php endif;
									endfor; ?>
								</optgroup>
								<!-- Care Coordinators -->

								<!-- Collaborators -->
								<optgroup label='Collaborators'>
									<?php $added_ids = array(); 
									$CollaboratorCounter = 0;
									for($i = 0;$i < count($pm_members); $i++):

										if($pm_members[$i]['role_name'] == 'Collaborator' && !in_array($pm_members[$i]['user_id'], $added_ids)): $CollaboratorCounter++?>
											<option value="<?php echo $pm_members[$i]['user_id'];?>"><?php echo $pm_members[$i]['full_name']." ( ".$pm_members[$i]['role_name']." ) ";?></option>

											<?php $added_ids[] = $pm_members[$i]['user_id']; ?>
										<?php endif;

									endfor;?>
								</optgroup>
								<!-- Collaborators -->

								<!-- All clients start-->
								<optgroup label='Clients'>
									<?php 
									$clientCounter = 0;
									for($i = 0;$i < count($pm_members); $i++):
										if($pm_members[$i]['role_name'] == 'Patient'):
										$clientCounter++;?>

											<option value="<?php echo $pm_members[$i]['user_id'];?>"><?php echo $pm_members[$i]['full_name']." ( ".$pm_members[$i]['role_name']." ) ";?></option>
									
									<?php endif;
									endfor; ?>
								
								</optgroup>
								<!-- All clients end -->

								<!-- All groups start -->
								<optgroup label='Groups'>
									<?php for($i = 0; $i < count($pm_groups); $i++):?>
										<option value="<?php echo $pm_groups[$i]->user_ids;?>"><?php echo $pm_groups[$i]->name;?></option>
									<?php endfor;?>
								</optgroup>
								<!-- All groups end-->
								
								<?php if(in_array($this->session->userdata("entity_role"), array(5,7))):?>
									<?php if($staffCounter > 0):?>
										<option value='all_staff'>All Staff</option>
									<?php endif;?>
									<?php if($therapistCounter > 0):?>
										<option value='all_therapists'>All Therapists</option>
									<?php endif;?>
									<?php if($CollaboratorCounter > 0):?>
										<option value='all_collaborator'>All Collaborator</option>
									<?php endif;?>
									<?php if($CarecoordinatorCounter > 0):?>
										<option value='all_carecoordinator'>All Care Coordinator</option>
									<?php endif;?>
									<?php if($clientCounter > 0):?>
										<option value='all_patient'>All Clients</option>
									<?php endif;?>

								<?php endif;
								if(in_array($this->session->userdata("entity_role"), array(1,2))):?>
	
									<?php if($therapistCounter > 0):?>
										<option value='all_therapists'>All Therapists</option>
									<?php endif;?>
	
								<?php  endif; 
								if(in_array($this->session->userdata("entity_role"), array(1,2,3))):?>
									
									<?php if($clientCounter > 0):?>
										<option value='all_patient'>All Clients</option>
									<?php endif;?>
	
								<?php endif;?>


							<?php /*---------------------------------------Patient/Care Coordinator/Collaborator---------------------------------------------------*/
							elseif($this->session->userdata('entity_role') == 4):
								for($i = 0;$i < count($pm_members); $i++):

									// continue to developer and system developer
									if($pm_members[$i]['role_id'] == 5 || $pm_members[$i]['role_id'] == 7 || $pm_members[$i]['patient_id'] == $this->session->userdata("my_id"))
										continue;?>

									<option value="<?php echo $pm_members[$i]['user_id']; ?>"><?php echo $pm_members[$i]['full_name']." ( ".$pm_members[$i]['role_name']." ) "; ?></option>
							
								<?php endfor;?>
							<?php endif;?>
						</select>
					</div>
				</div>
				<!-- Documents -->
				<div class="display_table message_component">
					<div class="display_table_cell first">
						<label>Documents:</label>
					</div>
					<div class="display_table_cell">
						<?php if(!empty($pm_documents)):?>
							<select multiple="" name="document[]" data-placeholder="Click to add a Document">
								<?php foreach($pm_documents as $document):?>
									<option value="<?php echo $document['document_id']?>"><?php echo $document['file_name_from_user']?></option>
								<?php endforeach;?>
							</select>
						<?php else:?>
							<div class="text-muted">You don't have documents.</div>
						<?php endif;?>
					</div>
				</div>
				<!-- Subject -->
				<div class="display_table message_component">
					<div class="display_table_cell first">
						<label>Subject:</label>
					</div>
					<div class="display_table_cell">
						<input name="subject" class="message-control" placeholder="Subject"/>
					</div>
				</div>
				<!-- Message -->
				<div class="message">
					<div class="row">
						<div class="col-lg-12">
							<label>Message:</label>
						</div>
					</div>
					<textarea name="message"></textarea>
				</div>
					<!-- Buttons -->
				<div class="display_table message_component">
					<div class="buttons display_table_cell">
							<button class="btn btn-success" id="send_message">SEND</button>
							<button class="btn btn-default" id="send_to_draft">SAVE TO DRAFTS</button>
							<a href="#" id='message_close'>or Close</a>
					</div>
					<div class="display_table_cell">
						<div class="wraper">
							<div id="sending_preloader" class="hidden">Sending<span class="dotted">.</span></div>
						</div>
					</div>
				</div>
			</form>
		</section>
	</div>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="confirm_modal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Confirmation</h4>
      </div>
      <div class="modal-body">
        <p></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-false>Close</button>
        <button type="button" class="btn btn-primary" data-true>Save changes</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="reply_modal">
  <div class="modal-dialog">
    <div class="modal-content">
	    <form action="">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Reply Message</h4>
			</div>
			<div class="modal-body">
		        <label for="reply_modal_select">Reply Message to:</label>
		        <select class="form-control" id="reply_modal_select" disabled="" required>
	        	<?php
	        	if(!empty($pm_members)){
		        	for($i = 0; $i < count($pm_members); $i++){?>
	    		    	<option value="<?php echo $pm_members[$i]['user_id'];?>"><?php echo $pm_members[$i]['full_name']."(".$pm_members[$i]['role_name'].")";?></option>
	        	<?php 
	        		}
	        	}
	        	?>
		        </select>
		        <label for="reply_modal_subject">Subject:</label>
		        <input type="text" class="form-control" id="reply_modal_subject" required>
		        <label for="reply_modal	_message">Message:</label>
		        <div class="text-area-block">
		        	<textarea class="form-control" id="reply_modal_message" rows="6" style="max-width: 100%; " required></textarea>
		        	<i class="fa fa-wifi textarea-corner" aria-hidden="true"></i>
		        </div>
		        <input type="hidden" name="sender_user_id" id="sender_id" required>
		    
	      </div>
	      <div class="modal-footer">
	        <button type="button" class="btn btn-default" data-false data-dismiss="modal">Close</button>
	        <button class="btn btn-primary" data-true>Send</button>
	      </div>
      </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<script src="<?php echo base_url(); ?>js/messages.js"></script>
