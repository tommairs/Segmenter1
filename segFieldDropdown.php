<?php 
{ini_set('memory_limit','256M');


function buildSegmentFieldList ($string)
{
	global $substitutionfnd, $ntsubstitutionfnd, $substitutionItemList, $substitutionTable, $total, $systemadded, $numeric;
	foreach ($string as $key => $value)
	{
		//$loose = "/{{((?!}}).)*" .$key . "((?!{{).)*}}/";
		echo $value;
		$found = array_key_exists($key, $substitutionItemList);
		echo $found;
		if (!$found)
		{
			$total++;
    		if (is_numeric($key))
    		{
    			if ($key>50) 
    			{
    				$numeric++;
    				$substitutionItemList[$key] = "False";
    			} 
    			else 
    			{
    				$systemadded++;
    				$substitutionItemList[$key] = "Index";
    			}
    		}
    		else
    		{
    			$substitutionfnd++; $substitutionItemList[$key] = "Yes";
    			echo '<option value="' . $key . '">' . $key . '</option>';
    		}
    	}	
    	if ($key != NULL)
    	{
    		buildSegmentFieldList($value);
    	}
    }
}	

$apikey     = $_POST["apikey"];
$apiroot	= $_POST["apiroot"];
$recipients = $_POST["recipients"];
//$datatype	= $_POST["type"];
//
//Main code body
//			
if ($recipients != "Select a Recipient")
{
	$curl     = curl_init();
	$url = $apiroot . "/recipient-lists/" . $recipients . "?show_recipients=true";
	echo $url;
	curl_setopt_array($curl, array(
	CURLOPT_URL => $url,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 60,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => "GET",
	CURLOPT_HTTPHEADER => array("authorization: $apikey","cache-control: no-cache","content-type: application/json")));
    
	$dataResponse = curl_exec($curl);
 
	$err = curl_error($curl);
	curl_close($curl);
	if ($err) 
	{
   	 echo "cURL Error #:" . $err;
	}
	// Get the User Substitution Data
    
	$arrayofRecipients = json_decode($dataResponse, true);
	
	//$one_entry = $arrayofRecipients['results']['recipients'][0];
	$one_entry = $arrayofRecipients['results']['recipients'][0]['substitution_data'];

	if ($one_entry === NULL) //recipient list doesn't have substitution data;
	{
	    $rec_sub = "";
	}
	else
	{
		$rec_sub  = json_encode($one_entry);
	}
}
else
{
	$rec_sub = "";
}
if (strlen($rec_sub) > 4)
{
	$subEntry = $rec_sub;
}
// Create an empty array object.  Will fail at the begining of the loop
if (strlen($rec_sub) < 4)
{
	$subEntry = json_decode ("{}");
	$rec_sub = json_encode($subEntry);
	echo "<h4>**No Recipient Data Found**</h4>";
}

$just_sub = json_decode($rec_sub);
//echo "<pre>";
//var_dump($just_sub);
//echo "</pre>";
//some of these can get big, so let's clean them out
unset($rec_sub); unset($makeArray); unset($one_entry);
    
    
$response = curl_exec($curl);
$encodedResponse = json_decode($response, true);
$errorFromAPI    = $encodedResponse["errors"];
$storedRawTemplate = $encodedResponse["results"]["content"]["html"];
$err = curl_error($curl);
curl_close($curl);

$substitutionfnd = 0; $numeric = 0; $systemadded = 0; $total = 0; 

echo "<option selected='selected' value='Filter Not Entered'>Select a Field</option>";
buildSegmentFieldList ($just_sub);

}?>
