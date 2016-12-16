<?php
//Copyright (c) 2016 SparkPost
//
//Licensed under the Apache License, Version 2.0 (the "License");
//you may not use this file except in compliance with the License.
//You may obtain a copy of the License at
//
//    http://www.apache.org/licenses/LICENSE-2.0
//
//Unless required by applicable law or agreed to in writing, software
//distributed under the License is distributed on an "AS IS" BASIS,
//WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//See the License for the specific language governing permissions and
//limitations under the License.
//
// Author: Steve Tuck (December 2016)

require 'segmentEngine.php';

//
//Main code body
//
$apiroot	= $_POST["apiroot"];
$apikey     = $_POST["apikey"];
$recipients = $_POST["recipients"];
$filter     = $_POST["filterArray"];
//$datatype	= $_POST["type"];

// Debugging: log relevant variables into a specific logfile
$logFileName = '/tmp/applog.txt';
app_log('apiroot='.$apiroot.' apkey='.$apikey.' recipients='.print_r($recipients,true).' filter='.print_r($filter, true));
$myList = [];                                   // will have a recipient-list array written into
$thisUrl = parse_url($apiroot);                 // split up into scheme, host and path parts.  We only need the host part.

$ok = segmentEngine($thisUrl['host'], $apikey, $recipients, $filter, $myList);
echo(json_encode(["recipients" =>$myList]));
?>
