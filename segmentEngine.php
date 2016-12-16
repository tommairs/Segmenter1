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

// SparkPost PHP adapter dependencies:  see https://github.com/SparkPost/php-sparkpost
require 'vendor/autoload.php';
use SparkPost\SparkPost;
use GuzzleHttp\Client;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;

// Application level access logging, with date/time stamp
function app_log($s)
{
    global $logFileName;
    error_log(date('Y-m-d H:i:sO').'|'. __FILE__.'|'.$s."\n", 3, $logFileName);
}

// Evaluate boolean condition
// Parameters:
//          $f  = field contents
//          $lc = logical-comparison      one of == != < > <= >=
//          $cv = comparison-value        a literal value
function evaluate($f, $lc, $cv)
{
    // Based on team review  - all STRING value comparisons are now case-insensitive
    if(!is_numeric($f)) {
    $f = strtolower($f);
    $cv = strtolower($cv);
    }

    switch ($lc) {
            case '==':
                return ($f == $cv);
                break;
            case '!=':
                return ($f != $cv);
                break;
            case '<':
                return ($f < $cv);
                break;
            case '>':
                return ($f > $cv);
                break;
            case '<=':
                return ($f <= $cv);
                break;
            case '>=':
                return ($f >= $cv);
                break;
            default:
                app_log('unknown comparison operator '.$lc);
                return(false);
        }
}


// Returns truth value of whether this recipient will be in the list, based on conditional logic
// Parameters:
//      $recipMeta              Single recipient's metadata
//      $s                      segmentation logic - comprising
//      $s['filter'] = data to filter on
//      $s['logic'] = top-level boolean operator to apply to that data = 'and', 'or'
//
// If errors are found, they are logged in the app_log and the function returns false
//
function recipInList($recipMeta, $s)
{
//DEBUG: app_log('segment logic='.print_r($s,true));
    if (array_key_exists('filter', $s)) {
        foreach ($s['filter'] as $v) {
            // Directly evaluate this attribute
            // Perform strict array index existence checking as later PHP versions require this
            if (array_key_exists('metadata-fieldname', $v)) {
                if (!array_key_exists('logical-comparison', $v)) {
                    app_log('missing comparison operator');
                    return(false);
                }
                if (!array_key_exists('comparison-value', $v)) {
                    app_log('missing comparison-value');
                    return(false);
                }
                // Get the operands and the logical comparison operator
                $fn = $v['metadata-fieldname'];
                $lc = $v['logical-comparison'];
                $cv = $v['comparison-value'];

                // If this recipient does not have the metadata field, then log a warning, and exclude them from the list
                if(!array_key_exists($fn, $recipMeta)) {
                    app_log('Recipient metadata field '.$fn.' missing');
                    return(false);
                }
                $recipValue = $recipMeta[$fn];
                $bool = evaluate($recipValue, $lc, $cv);
            } else {
                if (array_key_exists('logic', $v)) {
                    // Indirectly evaluate the current value as a nested logic object
                    $bool = recipInList($recip, $v);
                } else {
                    app_log('missing "logic" or "metadata-fieldname/logical comparison/comparison-value" key in '.print_r($v, true));
                    return(false);
                }
            }
            // Now we know the true / false boolean value of this single filter entry .. check we have a logical operator and apply it
            if (!array_key_exists('logic', $s)) {
                app_log('missing "and", "or" operator in '.print_r($s, true));
                return(false);
            }
            switch ($s['logic']) {
                case 'and':         // Lazy AND evaluation .. if we find a false value, then no need to look further
                    if (!$bool) {
                        return(false);
                    }
                    break;
                case 'or':          // Lazy OR evaluation .. if we find a true value, then no need to look further
                    if ($bool) {
                        return (true);
                    }
                    break;
                default:
                    app_log('unknown operator '.$s['logic'].'should be "and", "or"');
                    return(false);
            }
        }
        // Searched all of the preceding logical operations - so the final boolean result tells what to return
        return($bool);

    } else {
        app_log('missing "filter" key in '.print_r($s, true));
        return false;
    }
}


// Parameters:
//      $SparkyRecipListName    Valid SparkPost recipient list name for the current acccount
//      $logic                  Logic defining the segment (see below)
//      &$segmentedRecipList    CALL BY REFERENCE (pass in a blank string, get a segmented list out)
// Returns:
//      true                    Success
//      false                   Error   (the &$segmentedRecipList string will contain text related to the error)
//
function segmentEngine($host, $apiKey, $SparkyRecipListName, $segmentLogic, &$segmentedRecipList)
{
    // Open a php-sparkpost API connection
    if(is_null($host)) {
        $host = 'api.sparkpost.com';            // Default to SparkPost.com
    }
    $httpAdapter = new GuzzleAdapter(new Client());
    $sparky = new SparkPost($httpAdapter, ['key'=>$apiKey, 'timeout'=>0, 'host' => $host]);

    // Get the recipient list from SparkPost
    $recipMethod = 'recipient-lists/'.$SparkyRecipListName;
    $startTime = microtime(true);
    $promise = $sparky->request('GET', $recipMethod, ['show_recipients' => 'true']);
    try {
        $response = $promise->wait();
        $endTime = microtime(true);
        $time = $endTime - $startTime;
        $status = $response->getStatusCode();
        $recips = $response->getBody()['results'];
        app_log('API call '.$host.'/'.$recipMethod.' returned status = '.$status.' in '.round($time,3).' seconds, recipients = '.$recips['total_accepted_recipients']);

        // Now walk the entire recipient list, checking whether each recip should be in the specified segment
        $segmentedRecipList = [];
        $startTime = microtime(true);
        foreach($recips['recipients'] as $r) {
            $meta = $r['metadata'];
            $t = recipInList($meta, $segmentLogic);
            if($t) {
                array_push($segmentedRecipList, $r);                // Include this recipient in the segment output
            }
        }

        $endTime = microtime(true);
        $time = $endTime - $startTime;
        app_log('segment calculated in '.round($time,3).' seconds, resulting recipients = '.count($segmentedRecipList));
        return true;

    } catch (\Exception $e) {
        app_log('Request rejected by '. $host.' code = '.$e->getCode().' body = '.print_r($e->getMessage(), true));
        return false;
    }
}