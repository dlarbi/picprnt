<?php 

class Utilities {
	
	function currentPathToArray($query) {
		$currentpath = $query;
		$spliturlaroundgetvalues = explode('?', $currentpath, 2);
		$urlwithoutgetvalues = $spliturlaroundgetvalues[0];
		$getvalues = $spliturlaroundgetvalues[1];
		$pathargs = array_values(array_filter(explode('/', $urlwithoutgetvalues)));
			
		return $pathargs;
	}
	
	function urlMatchesEventName($path) {
		$model = new Model();
		$model->init();
		$eventNameList = $model->getEventNameList();
    
		foreach($eventNameList as $event) {

			if(in_array($path, $event) == true ) {
				return true;
			}
		}
		return false;
	}

	function convert_datetime($str) {
      list($date, $time) = explode(' ', $str);
      list($year, $month, $day) = explode('-', $date);
      list($hour, $minute, $second) = explode(':', $time);
      $timestamp = mktime($hour, $minute, $second, $month, $day, $year);
      return $timestamp;
  }
	
}
