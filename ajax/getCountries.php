<?php

// two-dimensional array with table headers and keys
$fieldArray = array(
  array("Name", "name"),
  array("Alpha Code 2", "alpha2Code"),
  array("Alpha Code 3", "alpha3Code"),
  array("Flag Image", "flag"),
  array("Region", "region"),
  array("Subregion", "subregion"),
  array("Population", "population"),
  array("Languages", "languages", "name")
);

// build our URL using the names of the data fields we want
// while we're iterating over $fieldArray we can assemble an array of table headers
$fields = "";
$headers = array();
foreach ($fieldArray as $field) {
  $fields .= $field[1].";";
  array_push($headers, $field[0]);
}

// Use cURL to complete a get request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://restcountries.eu/rest/v2/name/".$_POST['search']."?fields=".$fields);
// Set so curl_exec returns the result instead of outputting it.
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Get the response and close the channel.
$response = curl_exec($ch);
curl_close($ch);

// parse the json string into an associative array
$data = json_decode($response, true);

// figure out if we want an ascending or descending sort order
$sort = $_POST["sort"];
$direction;
if ($sort == "name") {
  $direction = "asc";
} else {
  $direction = "desc";
}
// create an anonymous sorting function to pass to usort
function build_sorter($key, $dir) {
  return function ($a, $b) use ($key, $dir) {
    if ($dir == "asc") {
      return ($a[$key] < $b[$key]) ? -1 : 1;
    } else {
      return ($a[$key] > $b[$key]) ? -1 : 1;
    }
  };
}
// sort entries by name or population
usort($data, build_sorter($sort, $direction));


// associative array to hold our region and subregion counts
$regions = array();
// assemble the body of our table as a 2-dimensional array
$body = array();
$numRows = sizeof($data) < 50 ? sizeof($data) : 50; // limit to 50 entries
for ($i=0; $i<$numRows; $i++) {

  $row = array();
  $currentRegion = ""; // used to associate subregions with regions
  foreach ($fieldArray as $field) { // iterate over our stored keys

    $cell = "";
    if (array_key_exists(2, $field)) { // iterate over array of languages
      $size = sizeof($data[$i][$field[1]]);
      for ($j=0; $j<$size; $j++) {
        $cell .= $data[$i][$field[1]][$j][$field[2]].", ";
      }
      $cell = rtrim($cell, ", ");
    }
    else {
      $cell = $data[$i][$field[1]];
      // update region and subregion counts
      if ($cell != "") {
        if ($field[1] == "region") {
          $currentRegion = $cell;
          // count_keys($regions, $cell, NULL);
        }
        else if ($field[1] == "subregion") {
          if ($currentRegion) {
            count_keys($regions, $currentRegion, $cell);
            $currentRegion = "";
          }
          else {
            error_log("Found a record with a subregion and no region.");
          }
        }
      }
    }
    array_push($row, $cell);
  }
  array_push($body, $row);
}

// start or increment a count of nested key instances
function count_keys(&$assArr, $key1, $key2) {
  if (!array_key_exists($key1, $assArr) || !array_key_exists($key2, $assArr[$key1])) {
    $assArr[$key1][$key2] = 1;
  }
  else {
    $assArr[$key1][$key2]++;
  }
}

// send an associative array representing our results table to the client
$results = array(
  "headers" => $headers,
  "body" => $body,
  "numCountries" => sizeof($data), // this is the number of countries returned by the API, not capped at 50
  "regions" => $regions
);
print_r(json_encode($results));

?>
