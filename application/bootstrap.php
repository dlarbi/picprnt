<?php
session_start();
require("application/model/api/instagram.class.php");
require("application/utilities.php");
require("application/controller.php");
require("application/model.php");
require("application/view.php");


$utilities = new Utilities();
$pathargs = $utilities->currentPathToArray($_SERVER['REQUEST_URI']);

$controller = new Controller($pathargs);
$controller->init();

?>
