<?php 

class Model {
	
	private function connect() {
		try {
			$this->db = new PDO( "mysql:host=mysql.picprnt.com;dbname=REDACTED", 'REDACTED', 'REDACTED' );
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(PDOException $e) {
			echo 'ERROR: ' . $e->getMessage();
		}
	}
	
	function subscribe($stripe_token, $ig_account, $access_token, $ig_id, $full_name, $cc_name, $cc_type, $cc_number, $ccv, $expiration, $address1, $address2, $city, $state, $zip, $prnts, $promo) {
		require_once('/home/picprnt_admin/picprnt.com/application/model/api/lib/Stripe.php');
		// Set your secret key: remember to change this to your live secret key in production
		// See your keys here https://dashboard.stripe.com/account
		Stripe::setApiKey("REDACTED");
		
		// Get the credit card details submitted by the form
		$token = $stripe_token;

		if($prnts == 4) {

      if($promo == 'picprntfree') {
         try {
          $charge = Stripe_Charge::create(array(
            "amount" => 0.00, // amount in cents, again
            "currency" => "usd",
            "card" => $token,
            "description" => $ig_id)
          );
          } catch(Stripe_CardError $e) {
            // The card has been declined
            return false;
          }
      } else {
         try {
          $charge = Stripe_Charge::create(array(
            "amount" => 395, // amount in cents, again
            "currency" => "usd",
            "card" => $token,
            "description" => $ig_id)
          );
          } catch(Stripe_CardError $e) {
            // The card has been declined
            return false;
          }
      }



		} else {
      if($prnts == 5) {
        $plan = '5prnts';
      } elseif($prnts == 10) {
        $plan = '10prnts';
      } elseif($prnts == 20) {
        $plan = '20prnts';
      }

      try {
        $customer = Stripe_Customer::create(array(
            "card" => $token,
            "plan" => $plan,
            "email" => "dean.m.larbi@gmail.com")
        );
      } catch (Stripe_CardError $e) {
        //echo $e->jsonBody['error']['message'];
        return false;
      }
		} //end if else prnts = 4
		
		try {
		$sth = $this->db->prepare('INSERT INTO main_users ( ig_account, access_token, ig_id, full_name, prnts, address1, address2, city, state, zip ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )');
		$success = $sth->execute(array($ig_account, $access_token, $ig_id, $full_name, $prnts, $address1, $address2, $city, $state, $zip));
		} catch(PDOException $e) {
			echo 'ERROR: ' . $e->getMessage();
		}
		return $success;
	}

	function isEventAuth($event_name, $email, $password) {

    try {
      $sth = $this->db->prepare('SELECT * FROM events WHERE event_name = ?');
      $success = $sth->execute(array($event_name));
      $result = $sth->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
      echo 'ERROR: ' . $e->getMessage();
    }

    if($result[0]['event_email'] == $email && $result[0]['event_password'] == $password) {
      session_start();
      $_SESSION['event_logged_in'] = 1;
      return true;
    } else {
      return false;
    }
	}
	
	function events_subscribe($stripe_token, $event_name, $event_date, $event_end, $event_email, $event_password, $cc_name, $cc_type, $cc_number, $ccv, $expiration, $address1, $address2, $city, $state, $zip, $prnts) {
       require_once('/home/picprnt_admin/picprnt.com/application/model/api/lib/Stripe.php');
       Stripe::setApiKey("REDACTED");
       $token = $stripe_token;
       if($prnts == 30) {
        $amount = 3000;
       } elseif($prnts == 50) {
        $amount = 4000;
       } elseif($prnts == 100) {
        $amount = 7500;
       }

       try {
        $charge = Stripe_Charge::create(array(
        "amount" => $amount, // amount in cents, again
        "currency" => "usd",
        "card" => $token,
        "description" => $ig_id)
      );
      } catch(Stripe_CardError $e) {
        // The card has been declined
        return false;
      }

      $event_date = (date("Y-m-d H:i:s", $event_date/1000));
      $event_end = (date("Y-m-d H:i:s", $event_end/1000));
      try {
  		$sth = $this->db->prepare('INSERT INTO events ( event_name, event_date, event_end, prnts, event_email, event_password, address1, address2, city, state, zip ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )');
  		$success = $sth->execute(array( $event_name, $event_date, $event_end, $prnts, $event_email, $event_password, $address1, $address2, $city, $state, $zip ));
  		} catch(PDOException $e) {
  			echo 'ERROR: ' . $e->getMessage();
  		}
  		$this->event_login();
  		return $success;
  	} 	
  	
  	private function event_login() {
  		session_start();
  		$_SESSION['logged_in'] = true;
  	}
  	
  	private function event_logout() {
  		session_destroy();
  	}

  	function user_login($data) {
  	  session_start();

      $_SESSION['user'] = $data;

    }

    function first_login($token) {
      	  session_start();
          $_SESSION['user']->access_token = $token;
          $_SESSION['prnts'] = $userprnts;
        }

    function user_logout() {
      session_destroy();
     }

     function cancelUser($cancelToken, $ig_id) {
     	try {
     		$sth = $this->db->prepare('UPDATE main_users SET cancelled = 1 WHERE access_token = ? AND ig_id = ?');
     		$success = $sth->execute(array( $cancelToken, $ig_id ));
     	} catch(PDOException $e) {
     		echo 'ERROR: ' . $e->getMessage();
     	}
     	return $success;
     }
     
   function getUserPrnts($userid) {
		  try {
        $sth = $this->db->prepare('SELECT * FROM main_prnts WHERE ig_id = ?');
        $success = $sth->execute(array($userid));
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
      } catch(PDOException $e) {
        echo 'ERROR: ' . $e->getMessage();
      }
      return $result;
	}
	
	function getEventPrnts($event_name) {
		try {
			$sth = $this->db->prepare('SELECT * FROM event_prnts WHERE event_name = ?');
			$success = $sth->execute(array($event_name));
			$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch(PDOException $e) {
			echo 'ERROR: ' . $e->getMessage();
		}
		return $result;
	}
	
	function getUserAddress($ig_id, $access_token) {
		try {
			$sth = $this->db->prepare('SELECT address1, address2, city, state, zip FROM main_users WHERE ig_id = ? AND access_token = ? AND cancelled = 0');
			$success = $sth->execute(array($ig_id, $access_token));
			$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch(PDOException $e) {
			echo 'ERROR: ' . $e->getMessage();
		}
		return $result;
	}
	
	function getEventAddress($event_name) {
		try {
			$sth = $this->db->prepare('SELECT address1, address2, city, state, zip FROM events WHERE event_name = ?');
			$success = $sth->execute(array($event_name));
			$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch(PDOException $e) {
			echo 'ERROR: ' . $e->getMessage();
		}
		return $result;
	}

	function getActiveUsers() {
    try {
      $sth = $this->db->prepare('SELECT * FROM main_users WHERE prnts > 0');
      $success = $sth->execute();
      $result = $sth->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
      echo 'ERROR: ' . $e->getMessage();
    }
    return $result;
	}

	function getEventUsers() {
      try {
        $sth = $this->db->prepare('SELECT * FROM event_users WHERE event_date >= CURDATE() ');
        $success = $sth->execute();
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
      } catch(PDOException $e) {
        echo 'ERROR: ' . $e->getMessage();
      }
      return $result;
  	}
	
	function getEventNameList() {
		try {
			$sth = $this->db->prepare('SELECT event_name FROM events');
			$success = $sth->execute();
			$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch(PDOException $e) {
			echo 'ERROR: ' . $e->getMessage();
		}
		return $result;
	}

	function getEventByName($event_name) {
	  try {
      $sth = $this->db->prepare('SELECT * FROM events WHERE event_name = ?');
      $sth->bindParam(1, $event_name, PDO::PARAM_STR);
      $success = $sth->execute();
      $result = $sth->fetchAll(PDO::FETCH_ASSOC)
      ;
    } catch(PDOException $e) {
      echo 'ERROR: ' . $e->getMessage();
    }
    return $result;
	}

  function minusPrnt($i, $ig_id) {
    try {
      $sth = $this->db->prepare('UPDATE main_users SET prnts = prnts - ? WHERE ig_id = ?');
      $success = $sth->execute(array( $i, $ig_id ));
    } catch(PDOException $e) {
      echo 'ERROR: ' . $e->getMessage();
    }
    return $success;
  }
  
  function changeAddress( $address1, $address2, $city, $state, $zip, $ig_id, $access_token ) {
  	try {
  		$sth = $this->db->prepare('UPDATE main_users SET address1 = ?, address2 = ?, city = ?, state = ?, zip = ? WHERE ig_id = ? AND access_token = ?');
  		$success = $sth->execute(array( $address1, $address2, $city, $state, $zip, $ig_id, $access_token ));
  		
  	} catch(PDOException $e) {
  		echo 'ERROR: ' . $e->getMessage();
  	}
  	return $success;
  }

  function changeEventAddress( $address1, $address2, $city, $state, $zip, $event_name ) {
    	try {
    		$sth = $this->db->prepare('UPDATE events SET address1 = ?, address2 = ?, city = ?, state = ?, zip = ? WHERE event_name = ?');
    		$success = $sth->execute(array( $address1, $address2, $city, $state, $zip, $event_name ));

    	} catch(PDOException $e) {
    		echo 'ERROR: ' . $e->getMessage();
    	}
    	return $success;
    }
  
  function setRemoved($image_id, $ig_id) {
  	try {
  		$sth = $this->db->prepare('UPDATE main_prnts SET removed = 1 WHERE ig_id = ? AND image_id = ?');
  		$success = $sth->execute(array($ig_id, $image_id));
  	} catch(PDOException $e) {
  		echo 'ERROR: ' . $e->getMessage();
  	}
  	return $success;
  }
  
  function setNotRemoved($image_id, $ig_id) {
  	try {
  		$sth = $this->db->prepare('UPDATE main_prnts SET removed = 0 WHERE ig_id = ? AND image_id = ?');
  		$success = $sth->execute(array($ig_id, $image_id));
  	} catch(PDOException $e) {
  		echo 'ERROR: ' . $e->getMessage();
  	}
  	return $success;
  }

  function setEventRemoved($image_id) {
    	try {
    		$sth = $this->db->prepare('UPDATE event_prnts SET removed = 1 WHERE image_id = ?');
    		$success = $sth->execute(array($image_id));
    	} catch(PDOException $e) {
    		echo 'ERROR: ' . $e->getMessage();
    	}
    	return $success;
    }

  function setEventPrntNotRemoved($image_id) {
    	try {
    		$sth = $this->db->prepare('UPDATE event_prnts SET removed = 0 WHERE image_id = ?');
    		$success = $sth->execute(array($image_id));
    	} catch(PDOException $e) {
    		echo 'ERROR: ' . $e->getMessage();
    	}
    	return $success;
    }

  function addPrnt($i, $ig_id, $access_token) {
  	try {
  		$sth = $this->db->prepare('UPDATE main_users SET prnts = prnts + ? WHERE ig_id = ? AND access_token = ?');
  		$success = $sth->execute(array( $i, $ig_id, $access_token ));
  	} catch(PDOException $e) {
  		echo 'ERROR: ' . $e->getMessage();
  	}
  	return $success;
  }

  function addEventPrnt($i, $event_name) {
    	try {
    		$sth = $this->db->prepare('UPDATE events SET prnts = prnts + ? WHERE event_name = ?');
    		$success = $sth->execute(array( $i, $event_name ));
    	} catch(PDOException $e) {
    		echo 'ERROR: ' . $e->getMessage();
    	}
    	return $success;
    }
  
  function removePrnt($i, $ig_id, $access_token) {
  	try {
  		$sth = $this->db->prepare('UPDATE main_users SET prnts = prnts - ? WHERE ig_id = ? AND access_token = ?');
  		$success = $sth->execute(array( $i, $ig_id, $access_token ));
  	} catch(PDOException $e) {
  		echo 'ERROR: ' . $e->getMessage();
  	}
  	return $success;
  }

  function removeEventPrnt($i, $event_name) {
    	try {
    		$sth = $this->db->prepare('UPDATE events SET prnts = prnts - ? WHERE event_name = ?');
    		$success = $sth->execute(array( $i, $event_name ));
    	} catch(PDOException $e) {
    		echo 'ERROR: ' . $e->getMessage();
    	}
    	return $success;
    }

	function getRemainingPrntsById($ig_id) {
  	  try {
        $sth = $this->db->prepare('SELECT prnts FROM main_users WHERE ig_id = ?');
        $sth->bindParam(1, $ig_id, PDO::PARAM_STR);
        $success = $sth->execute();
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
      } catch(PDOException $e) {
        echo 'ERROR: ' . $e->getMessage();
      }
      return $result;
  	}
  	
  	function getRemainingEventPrnts($event_name) {
  		try {
  			$sth = $this->db->prepare('SELECT prnts FROM events WHERE event_name = ?');
  			$sth->bindParam(1, $event_name, PDO::PARAM_STR);
  			$success = $sth->execute();
  			$result = $sth->fetchAll(PDO::FETCH_ASSOC)
  			;
  		} catch(PDOException $e) {
  			echo 'ERROR: ' . $e->getMessage();
  		}
  		return $result;
  	}

	function subscribe_event_user($event, $igdata) {
    $event_name = $event[0]['event_name'];
    $event_date = $event[0]['event_date'];
    $event_end = $event[0]['event_end'];
    $ig_id = $igdata->user->id;
    $ig_account = $igdata->user->username;
    $access_token = $igdata->access_token;

	  try {
      $sth = $this->db->prepare('INSERT IGNORE INTO event_users ( event_name, event_date, event_end, ig_id, ig_account, access_token ) VALUES ( ?, ?, ?, ?, ?, ? )');
      $success = $sth->execute(array( $event_name, $event_date, $event_end, $ig_id, $ig_account, $access_token ));
    } catch(PDOException $e) {
      echo 'ERROR: ' . $e->getMessage();
    }
    return $success;
	}

  function boolUserExists($user) {
      try {
        $sth = $this->db->prepare('SELECT * FROM main_users WHERE ig_id = ? AND cancelled != 1');
        $success = $sth->execute(array($user));
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
      } catch(PDOException $e) {
        echo 'ERROR: ' . $e->getMessage();
      }
      if(empty($result)) {
        return false;
      }
      return true;
  }
  
  function verifyUserAccess($ig_id, $access_token) {
  	try {
  		$sth = $this->db->prepare('SELECT ig_id, access_token FROM main_users WHERE ig_id = ?');
  		$success = $sth->execute(array($ig_id));
  		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
  	} catch(PDOException $e) {
  		echo 'ERROR: ' . $e->getMessage();
  	}
  	
  	if($access_token == $result[0]['access_token']) {
		return true;
  	} else {
		return false;
  	}  	
  	
  }

	function addToPrnts($prntData) {
	 // print_r($prntData);
	  $ig_id = $prntData[0];
	  try {
      $sth = $this->db->prepare('INSERT INTO main_prnts ( ig_id, image, created_time, caption, image_id ) VALUES ( ?, ?, ?, ?, ? )');
      $success = $sth->execute($prntData);
      $this->minusPrnt(1, $ig_id);
    } catch(PDOException $e) {
      echo 'ERROR: ' . $e->getMessage();
    }
    return $success;
	}

	function addToEventPrnts($prntData) {
  	 // print_r($prntData);
  	  $ig_id = $prntData[0];
  	  try {
        $sth = $this->db->prepare('INSERT INTO event_prnts ( ig_id, image, created_time, caption, image_id, event_name ) VALUES ( ?, ?, ?, ?, ?, ? )');
        $success = $sth->execute($prntData);
      } catch(PDOException $e) {
        echo 'ERROR: ' . $e->getMessage();
      }
      return $success;
  	}

	private function getAllUserPosts($user, $token) {
		//https://api.instagram.com/v1/users/[USER ID]/media/recent?access_token=143843096.5b9e1e6.5bc0db2b39d34b5cb99c163caaef1c6b
		
	}
	
	private function filterPostsForPrnts($posts) {
		//for each posts['tags'], if there is a #prnt tag then return that post.
		return $prntArray;
	}
	
	public function init() {
		$this->connect();
	}
	
}
