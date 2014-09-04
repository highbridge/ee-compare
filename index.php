<?php

/******************************************************************************
 *
 * WARNING: Use this tool with care!
 * 
 * The contents of DATABASE_1 and DATABASE_2 will be
 * COMPLETELY ERASED whenever new files are uploaded.
 *
 *****************************************************************************/

/******************************************************************************
 * 
 * A configuration file is required with definitions for the following constants:
 *
 * USERNAME
 * PASSWORD
 * MYSQL_USER
 * MYSQL_PASS
 * MYSQL_HOST
 * DATABASE_1
 * DATABASE_2
 *
 *****************************************************************************/

session_start();
require "controller.php";
$controller = new Controller();

// Get our action from the URL, or default to login when it's not set
$action = ($_GET['action']) ? $_GET['action'] : 'login';
$controller->action($action);

?>
