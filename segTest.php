#!/usr/bin/env php
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

//-------------------------------------------------
// Segments, Segments, wherefore art thou, Segments?

// Data structure representing a segment
// logic can be 'and', 'or', 'not'.  Always accompanied by exactly one 'filter' item, which is an array.
//  each element of 'data' comprises:
//      exactly three parts (all strings) -
//          metadata-fieldname      any valid SparkPost metadata field name
//          logical-comparison      one of == != < > <= >=
//          comparison-value        a literal value

$testSegment1 = [
    'logic' => 'and',
    'filter' => [
        [ 'metadata-fieldname' => 'gender', 'logical-comparison' => '==', 'comparison-value' => 'female' ],
        //[ 'metadata-fieldname' => 'city', 'logical-comparison' => '==', 'comparison-value' => 'New York'],
        //[ 'metadata-fieldname' => 'interests', 'logical-comparison' => '==', 'comparison-value' => 'programming'],
    ]
];

$testSegment2 = [
    'logic' => 'and',
    'filter' => [
        [ 'metadata-fieldname' => 'gender', 'logical-comparison' => '==', 'comparison-value' => 'male' ],
        [ 'metadata-fieldname' => 'city', 'logical-comparison' => '==', 'comparison-value' => 'Boston'],
    ]
];

$testSegment3 = [
    'logic' => 'and',
    'filter' => [
        [ 'metadata-fieldname' => 'gender', 'logical-comparison' => '==', 'comparison-value' => 'female' ],
        [ 'metadata-fieldname' => 'country', 'logical-comparison' => '==', 'comparison-value' => 'Australia'],
        [ 'metadata-fieldname' => 'city', 'logical-comparison' => '==', 'comparison-value' => 'Sydney'],
    ]
];

$testSegment4 = [
    'logic' => 'and',
    'filter' => [
        [ 'metadata-fieldname' => 'gender', 'logical-comparison' => '==', 'comparison-value' => 'male' ],
        [ 'metadata-fieldname' => 'interests', 'logical-comparison' => '==', 'comparison-value' => 'exercise'],
    ]
];

//
// Here's an example of how to call the segmentEngine function
//
$logFileName = '/tmp/applog.txt';
$myList = [];                                   // will have a recipient-list array written into
//Test lists:  1969-demo-data-list 20691-demo-data-list
$ok = segmentEngine('demo.sparkpostelite.com', '90b219e929e2f11d25460ca2699086dbd45af626', '1969-demo-data-list', $testSegment1, $myList);
echo('Test 1: female, New York, programming : result='.$ok.' count='.count($myList)."\n");

echo(json_encode(["recipients" =>$myList]));

$ok = segmentEngine('demo.sparkpostelite.com', '90b219e929e2f11d25460ca2699086dbd45af626', '1969-demo-data-list', $testSegment2, $myList);
echo('Test 2: male, Boston : result='.$ok.' count='.count($myList)."\n");

$ok = segmentEngine('demo.sparkpostelite.com', '90b219e929e2f11d25460ca2699086dbd45af626', '1969-demo-data-list', $testSegment3, $myList);
echo('Test 3: Female, Australia, Sydney : result='.$ok.' count='.count($myList)."\n");

$ok = segmentEngine('demo.sparkpostelite.com', '90b219e929e2f11d25460ca2699086dbd45af626', '1969-demo-data-list', $testSegment4, $myList);
echo('Test 4: male, exercise : result='.$ok.' count='.count($myList)."\n");