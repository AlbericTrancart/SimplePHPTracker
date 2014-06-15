<?php

//----------------------------------------------------------------------------
//Check the configuration below and give the URL of this file to your clients!
//----------------------------------------------------------------------------

//Link to the tracker core class
require_once 'Tracker/TrackerCore.class.php';

$config = new TrackerConfig(array(
    'sql_host'       => 'localhost',  //Enter your SQL database parameters
    'sql_db'         => 'tracker',
    'sql_user'       => 'root',
    'sql_password'   => '',
    'interval'       => 60,           //Time in seconds the client must wait before the next announce
    'load_balancing' => true,         //Optional: adds a 10% dispersion to the interval to avoi announce peeks
));

//Load config
$core = new TrackerCore($config);

//Store the $_GET data
$get = new TrackerConfig($_GET);

//Announce the result
echo $core->announce($get);
