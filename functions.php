<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';

//date_default_timezone_set('America/Toronto');

if(!function_exists('current_user')) {
  ini_set('session.gc_maxlifetime', 259200);
  session_set_cookie_params(259200);
  session_start();

  function current_user() {
    $user = new User($_SESSION);
    return $user;
  }
}
