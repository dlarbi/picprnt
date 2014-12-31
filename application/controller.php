<?php 

class Controller {
	
	private $routes; 
	
	function __construct(array $path) {
		$this->path = $path;
		$this->utilities = new Utilities();
		
		// The main reason we define routes is because if an action_function_name is
		// in this list, it may be accessed by URL /function_name
		$this->routes = array(
			'',
			'/',
			'about',
			'home',
			'subscribe',
			'user_home',
			'user_logout',
			'add_prnt',
			'remove_prnt',
			'remove_from_queue',
			'add_to_queue',
			'cancel',
			'confirm_cancel',
			'change_address',
			'events',
			'events_subscribe',
			'events_about',
			'event_login',
			'event_login_verify',
			'event_home',
			'change_event_address',
			'event_add_to_queue',
			'event_remove_from_queue',
			'event_add_prnt',
			'event_remove_prnt'
						
			);
	}
	
	private function callMethods() {

		// This function calls methods from the Controller class depending on the URL path

		// We search the $routes listed in the constructor for the current path
		// if it isn't found, it may be the name of an event which we handle differently
		if(in_array($this->path[0], $this->routes) == false) {

      // ***EVENTS IS NOT CURRENTLY IN USE
			// In this case the route doesn't exist, we search for the first path element in our table of created events.
			// so, if the event is found we call the action_event_page() to let users check-in
			// and view event details.
			if($this->utilities->urlMatchesEventName($this->path[0]) == true) {
				$this->action_event_page();
			} else {				
				$this->action_home();
				return;
			}
		} else {
			if(empty($this->path)) {
				$this->action_home(); 
				return;
			}
			
			if($this->path[0] == admin) {
				
			}		
					
			foreach($this->path as $route) {
				$methodname = 'action_'.$route;
				$this->$methodname();			
			}	
		}	
	}

	// this may one day be a callback URL for the ig realtime api
	private function action_receive_user_post() {
		$model = new Model();
		$model->init();		
	}
	
	// not being used. part of admin panel functions
	private function action_post_finder() {
		$model = new Model();
		$model->init();
		$prntImageArray = $model->getUserPrntImages($user);
		var_dump($prntImageArray);		
	}
	
	private function action_confirm_cancel() {
		session_destroy();
		$cancelToken = $_GET['access_token'];
		$ig_id = $_GET['ig_id'];
		$model = new Model();
		$model->init();
		$model->cancelUser($cancelToken, $ig_id);
		header('Location: /home');
	}
	
	// simply loads the about page, also renders IG login button
	private function action_about() {
		$instagram = new Instagram(array(
				'apiKey'      => 'd493cc50ef7e4a7296d2d1c7ca15f19e',
				'apiSecret'   => '840dbf9dfc36488991f99c6e9ae7fd61',
				'apiCallback' => 'https://www.picprnt.com/'
		));
		$loginUrl   = $instagram->getLoginUrl();
		View::render('about', $loginUrl);
	}

	private function action_events_about() {
		View::render('events_about');
	}
	
	private function action_products() {
		View::render('products');
	}
	
	private function action_cancel() {
		View::render('cancel');
	}
	
	private function action_event_login() {
		$eventName = $_GET['event'];
		View::render('event_login');
	}
	
	private function action_event_login_verify() {
		
	}
	
	private function action_event_home() {
		$model = new Model();
		$model->init();
		if(!empty($_POST) && $model->isEventAuth($_POST['event_name'], $_POST['email'], $_POST['password']) == true) {
			$event_name = $_POST['event_name'];
			$eventPrnts = $model->getEventPrnts($event_name);
			$remainingPrnts = $model->getRemainingEventPrnts($event_name);
			$eventAddress = $model->getEventAddress($event_name);
			$userData = array('event_name' => $event_name, 'prnts' => $eventPrnts, 'remaining_prnts' => $remainingPrnts, 'event_address' => $eventAddress);
			View::render('event_home', $userData);
		} else {
			View::render('event_try_again');
		}
	}
	
	private function action_change_address() {
		$datastring = $_POST['data'];
		parse_str($datastring, $datas);		
		$access_token = $datas['access_token'];
		$address1 = $datas['address1'];
		$address2 = $datas['address2'];
		$city = $datas['city'];
		$state = $datas['state'];
		$zip = $datas['zip'];
		
		$model = new Model();
		$model->init();
		$model->changeAddress($address1, $address2, $city, $state, $zip, $_SESSION['user']->user->id, $access_token);
	}

	private function action_change_event_address() {
  		$datastring = $_POST['data'];
      parse_str($datastring, $datas);
      $event_name = $datas['event_name'];
      $address1 = $datas['address1'];
      $address2 = $datas['address2'];
      $city = $datas['city'];
      $state = $datas['state'];
      $zip = $datas['zip'];

      $model = new Model();
      $model->init();
      $model->changeEventAddress($address1, $address2, $city, $state, $zip, $event_name);
  }
	
	// Because /home is our callback for authentication, this
	// function has branches for situations like a GET variable
	// when users check-in for an event
	private function action_home() {
		$instagram = new Instagram(array(
				'apiKey'      => 'd493cc50ef7e4a7296d2d1c7ca15f19e',
				'apiSecret'   => '840dbf9dfc36488991f99c6e9ae7fd61',
				'apiCallback' => 'https://www.picprnt.com/'
		));
		
		$code = $_GET['code'];
		$event = $_GET['state'];

		if (true === isset($code)) {
		  if(true === isset($event)) {
		      $instagram = new Instagram(array(
              'apiKey'      => 'd493cc50ef7e4a7296d2d1c7ca15f19e',
              'apiSecret'   => '840dbf9dfc36488991f99c6e9ae7fd61',
              'apiCallback' => 'https://www.picprnt.com/?state=' . $event
          ));

		    $data = $instagram->getOAuthToken($code);
		    $subscriptionData = array($event, $data);
		    $model = new Model();
		    $model->init();
		    $eventData = $model->getEventByName($event);
		    $boolUserSubscribed = $model->subscribe_event_user($eventData, $data);
		    if($boolUserSubscribed == true) {
		      View::render('event_checkedin', $subscriptionData, false);
		    } else {
		      View::render('event_checkedin_failure', $subscriptionData, false);
		    }

		  } else {
		    $data = $instagram->getOAuthToken($code);
		    $model = new Model();
		    $model->init();
        $model->user_login($data);
		    // if the user has been authenticated before, we update their access_token and
		    // then redirect them to their homepage to view their prnts.  Otherwise signup
		    if($model->boolUserExists($data->user->id) == true) {

		      header('Location: /user_home');
		      session_write_close();
		    } else {
		      View::render('user_signup', $data);
		    }
		  }
		}	else {
			$loginUrl   = $instagram->getLoginUrl();			
			View::render('home', $loginUrl);
		}
	}

  private function action_user_home() {
  session_start();
    $model = new Model();
    $model->init();
    $userprnts = $model->getUserPrnts($_SESSION["user"]->user->id);
    $remaining_prnts = $model->getRemainingPrntsById($_SESSION["user"]->user->id);
    $user_address = $model->getUserAddress($_SESSION["user"]->user->id, $_SESSION["user"]->access_token);
    $_SESSION['user_address'] = $user_address;
    $_SESSION['prnts'] = $userprnts;
    $_SESSION['remaining_prnts'] = $remaining_prnts;
    if(isset($_SESSION["user"]->access_token)) {
      $loggedInUserData = $_SESSION;
      View::render('user_home', $loggedInUserData);
    } else {
      header('Location: /home');
    }
  }

  private function action_user_logout() {
    session_destroy();
    header('Location: /home');
  }

  private function action_add_prnt() {
  	$data = $_POST['data'];
  	$ig_id = $data['ig_id'];
  	$access_token = $data['access_token'];
  	  	
  	$model = new Model();
  	$model->init();
  	$model->addPrnt(1, $ig_id, $access_token);
  }

   private function action_event_add_prnt() {
    	$data = $_POST['data'];
    	$event_name = $data['event_name'];

    	$model = new Model();
    	$model->init();

      if($_SESSION['event_logged_in'] == 1) {
        $model->addEventPrnt(1, $event_name);
        echo 'removed';
      } else {
        echo '404';
      }
    }
  
  private function action_remove_prnt() {
  	$data = $_POST['data'];
  	$ig_id = $data['ig_id'];
  	$access_token = $data['access_token'];
  
  	$model = new Model();
  	$model->init();
  	 if($_SESSION['event_logged_in'] == 1) {
      $model->removeEventPrnt(1, $ig_id, $access_token);
      echo 'removed';
    } else {
      echo '404';
    }
  }

  private function action_event_remove_prnt() {
    	$data = $_POST['data'];
    	$event_name = $data['event_name'];
    	$model = new Model();
    	$model->init();
    	$model->removeEventPrnt(1, $event_name);
    }
  
  private function action_remove_from_queue() {
  		$data = $_POST['data'];
  		$image_id = $data;
		$ig_id = $_SESSION['user']->user->id; 
		$access_token = $_SESSION['user']->access_token;
		
		$model = new Model();
		$model->init();
		if($model->verifyUserAccess($ig_id, $access_token) == true) {
			$model->setRemoved($image_id, $ig_id);
			echo 'removed';
		} else {
			echo '404';
		}			
  }

  private function action_event_remove_from_queue() {
    		$data = $_POST['data'];
    		$image_id = $data;
        $model = new Model();
        $model->init();
        if($_SESSION['event_logged_in'] == 1) {
          $model->setEventRemoved($image_id);
          echo 'removed';
        } else {
          echo '404';
        }
    }
  
  private function action_add_to_queue() {
  	$data = $_POST['data'];
  	$image_id = $data;
  	$ig_id = $_SESSION['user']->user->id;
  	$access_token = $_SESSION['user']->access_token;
  
  	$model = new Model();
  	$model->init();
  	if($model->verifyUserAccess($ig_id, $access_token) == true) {
  		$model->setNotRemoved($image_id, $ig_id);
  		echo 'added';
  	} else {
  		echo '404';
  	}
  }

  private function action_event_add_to_queue() {
    	$data = $_POST['data'];
    	$image_id = $data;

    	$model = new Model();
    	$model->init();
    	if($_SESSION['event_logged_in'] == 1) {
    		$model->setEventPrntNotRemoved($image_id);
    		echo 'added';
    	} else {
    		echo '404';
    	}
    }
  
	private function action_events() {
	  View::render('events');
	}

	private function action_event_page() {
	  $model = new Model();
	  $model->init();
	  $instagram = new Instagram(array(
        'apiKey'      => 'd493cc50ef7e4a7296d2d1c7ca15f19e',
        'apiSecret'   => '840dbf9dfc36488991f99c6e9ae7fd61',
        'apiCallback' => 'https://www.picprnt.com/?state=' . $this->path[0]
    ));
	  $loginUrl   = $instagram->getLoginUrl();
	  $eventData = array($model->getEventByName($this->path[0]), $loginUrl);
	  //var_dump($eventData);
		View::render('event_page', $eventData, false);
	}
	
	private function action_prints() {
		View::render('prints');
	}
	
	private function action_subscribe() {
		$model = new Model();
		$model->init();
		
		$datastring = $_POST['data'];
		parse_str($datastring, $datas);
		
		$access_token = $datas['access_token'];
		$stripe_token = $datas['stripeToken'];
		$ig_account = $datas['ig_account'];
		$ig_id = $datas['ig_id'];
		$full_name = $datas['full_name'];
		$cc_name = $datas['cc_name'];
		$cc_type = $datas['cc_type'];
		$cc_number = $datas['cc_number'];
		$expiration = $datas['expiration'];
		$ccv = $datas['ccv'];
		$address1 = $datas['address1'];
		$address2 = $datas['address2'];
		$city = $datas['city'];
		$state = $datas['state'];
		$zip = $datas['zip'];
		$prnts = $datas['prnts'];
		$promo = $datas['promo'];
		
		if($model->subscribe($stripe_token, $ig_account, $access_token, $ig_id, $full_name, $cc_name, $cc_type, $cc_number, $ccv, $expiration, $address1, $address2, $city, $state, $zip, $prnts, $promo) == false) {
			View::render('failure', null, true); // null means no args, true means ajaxy
		} else {
			View::render('success', null, true); // null means no args, true means ajaxy
		}
	}

	private function action_events_subscribe() {
  		$model = new Model();
  		$model->init();

  		$datastring = $_POST['data'];
  		parse_str($datastring, $data);

  		$event_name = $data['event_name'];
  		$stripe_token = $data['stripeToken'];

  		$event_date = $data['event_date'];
  		$event_end = $data['event_end'];
  		$event_email = $data['event_email'];
  		$event_password = $data['event_password'];
  		$cc_name = $data['cc_name'];
  		$cc_type = $data['cc_type'];
  		$cc_number = $data['cc_number'];
  		$expiration = $data['expiration'];
  		$ccv = $data['ccv'];
  		$address1 = $data['address1'];
  		$address2 = $data['address2'];
  		$city = $data['city'];
  		$state = $data['state'];
  		$zip = $data['zip'];
  		$prnts = $data['prnts'];

  		if($model->events_subscribe($stripe_token, $event_name, $event_date, $event_end, $event_email, $event_password, $cc_name, $cc_type, $cc_number, $ccv, $expiration, $address1, $address2, $city, $state, $zip, $prnts) == false) {
  			View::render('events_failure', null, true); // null means no args, true means ajaxy
  		} else {
  			View::render('events_success', $data, true); // null means no args, true means ajaxy
  		}
  	}

  private function action_personal_address() {
    View::render('personal_address', null, false);
  }
	
	public function init() {
		$this->callMethods();
	}
	
}
