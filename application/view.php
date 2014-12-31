<?php


class View {
	
	static function render($view, $args = null, $ajaxy = false) {		
			
		$argsarray = (array)$args;
		if($argsarray !== null){
			extract($argsarray);			
		}
				
		if($ajaxy == false) {
			require("application/includes/head.php");
			require("application/includes/header.php");			
		}
			
		require('application/views/' . $view . '.php');	

		if($ajaxy == false) {
			require("application/includes/foot.php");
		}
		
		
	}
		
}
