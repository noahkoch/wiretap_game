<?php
ini_set('session.gc_maxlifetime', 259200);
session_set_cookie_params(259200);
session_start();

require 'vendor/autoload.php';
