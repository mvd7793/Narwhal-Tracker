<?php
	class RidersController extends AppController {
		var $name = 'Riders';
		
		function index() {
            $this->loadModel('User');
            $user = $this->User->find('first', array('conditions' => array('User.id' => $this->Auth->user('id'))));
            
            $group = $user['Group']['name'];
            
            if($group == 'riders') {
                $this->redirect(array('controller' => 'riders', 'action' => 'summary'));
            } else if($group == 'marketing' || $group == 'information') {
                $this->redirect(array('controller' => 'rider_summaries', 'action' => 'team'));
            } else {
                $this->redirect(array('controller' => 'rider_summaries', 'action' => 'index'));
            }
		}
		
		function view($id = null) {
            if(!isset($id)) {
                $this->redirect(array('action' => 'index'));
            }
			$this->Rider->r_id = $id;
			$this->set('rider', $this->Rider->read());
		}
		
        function summary() {
            $this->loadModel('User');
            $user = $this->User->find('first', array('conditions' => array('User.id' => $this->Auth->user('id'))));
            if($user['Group']['name'] == 'riders') {
                $riders = $this->Rider->find('all', array(
                    'conditions' => array('r_user_id' => $this->Auth->user('id')),
                    'order' => array('Rider.r_year DESC')
                ));
                
                $this->loadModel("RiderSummary");
                
                $team_totals = array();
                
                foreach($riders as $rider) {
                    //get the riders' team totals for 
                    $total = $this->RiderSummary->find('all', array(
                        'fields' => array(
                            'SUM(RiderSummary.don_total) as total'
                        ),
                        'conditions' => array(
                            'r_year' => $rider['Rider']['r_year']
                        )
                    ));
                    $team_totals[$rider['Rider']['r_year']] = isset($total[0][0]['total']) ? $total[0][0]['total'] : 0;
                }
                $this->set("team_totals", $team_totals);
                $this->set('riders', $riders);
                $this->layout = 'rider';
            } else {
                $this->redirect(array('controller' => 'rider_summaries', 'action' => 'index'));
            }
        }
        
		function add() {
			if(!empty($this->data)) {
                if(isset($this->data['Rider']['r_user_username']) && $this->data['Rider']['r_user_id'] == -1) {
                    $password = isset($this->data['Rider']['r_user_password']) ? $this->data['Rider']['r_user_password'] : "SanFran2011";
                    $this->loadModel('User');
                    $new_user = array('User' => array(
                        'username' => $this->data['Rider']['r_user_username'],
                        'password' => $password,
                        'group_id' => $this->rider_group_id()
                    ));
                    $new_user = $this->Auth->hashPasswords($new_user);
                    if($this->User->save($new_user)) {
                        $this->data['Rider']['r_user_id'] = $this->User->id;
                    }
                } else if($this->data['Rider']['r_user_id'] == -1) {
                    $this->data['Rider']['r_user_id'] = 0;
                }
                
				if($this->Rider->save($this->data)) {
					$this->Session->setFlash('New rider has been created');
					$this->redirect(array('action' => 'index'));
				} else {
                    $this->Session->setFlash('An error occurred. Please try again.');
                }
			}
			$this->set('valid_years', $this->valid_years());
            $this->set('user_list', $this->user_list());
		}
		
		function edit($id = null) {
			$this->Rider->id = $id;
			if(empty($this->data)) {
				$this->data = $this->Rider->read();
			} else {
				if($this->Rider->save($this->data)) {
					$this->Session->setFlash('Changes were saved');
					$this->redirect(array('action' => 'view', $id));
				}
			}
			$this->set('valid_years', $this->valid_years());
            $this->set('user_list', $this->user_list());
		}
	
        function delete($id = null) {
            if(empty($this->data)) {
                if(!isset($id)) {
                    $this->redirect(array('action' => 'index'));
                }
                $this->data = $this->Rider->read(null, $id);
            } else {
                $id = $this->data['Rider']['r_id'];
                $rider = $this->Rider->read(null, $id);
                
                if($rider['Rider']['r_user_id'] > 0) {
                    if(!$this->User->delete($rider['Rider']['r_user_id'])) {
                        $this->Session->setFlash("Rider's user record was not deleted. Please try again");
                        $this->redirect(array('action' => 'index'));
                    }
                }
                
                if($this->Rider->delete($id)) {
                    $this->Session->setFlash("Rider was deleted");
                    $this->redirect(array('action' => 'index'));
                } else {
                    $this->Session->setFlash("Rider was not deleted. Please try again");
                    $this->redirect(array('action' => 'index'));
                }
            }
        }
        
        private function rider_group_id() {
            $this->loadModel('Group');
            $rider_group = $this->Group->find('first', array('conditions' => array('name' => 'riders')));
            
            return $rider_group['Group']['id'];
        }
        
        private function user_list() {
            $this->loadModel('Group');
            $rider_group = $this->Group->find('first', array('conditions' => array('name' => 'riders')));
            
            $this->loadModel('User');
            $users = $this->User->find('all', array('conditions' => array('group_id' => $this->rider_group_id())));
            
            $user_select = array('' => '');
            
            $user_list = array();
            
            foreach($users as $user) {
                $user_list[$user['User']['id']] = $user['User']['username'];
            }
            
            $user_select = array(
                '-1' => '',
                '0' => 'Do not create a user for this rider',
                'Select an existing user' => $user_list
            );
            
            return $user_select;
        }
        
        private function valid_years() {
            $output = array();
            $current_year = date("Y");
            $years = range($current_year + 1,2007);
            foreach ($years as $year) {
                $output[$year] = $year;
            }
            return $output;
        }
	}
?>