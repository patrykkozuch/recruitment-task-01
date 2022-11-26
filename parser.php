<?php

require 'vendor/autoload.php';

use DiDom\Document;

// key => HTML identifier
$dataToParse = [
    "trackingNumber" => "wo_number",
    "PONumber" => "po_number",
    "scheduledDate" => "scheduled_date",
    "customer" => "customer",
    "trade" => "trade",
    "NTE" => "nte",
    "storeID" => "location_name",
    "address" => "location_address",
    "phoneNumber" => "location_phone"
];

// Read data

$document = new Document('wo_for_parse.html', true);

try
{
    $unprocessedData = [];
    foreach ($dataToParse as $dataKey => $dataID)
    {
        $unprocessedData[$dataKey] = trim($document->find("#" . $dataID)[0]->text());
    }
} catch (\DiDom\Exceptions\InvalidSelectorException $e) {
    die("Incomplete file has been submitted.");
}

// Data processing
try
{
    $validData = [];

    // Tracking number
    $validData['trackingNumber'] = $unprocessedData['trackingNumber'];

    // PO Number
    $validData['PONumber'] = $unprocessedData['PONumber'];

    // Scheduled date
    $dateStrings = preg_split("/\n/", $unprocessedData['scheduledDate']);

    if (count($dateStrings) != 2)
        throw new InvalidArgumentException("Date info is missing.");

    $date = trim($dateStrings[0]);
    $time = trim($dateStrings[1]);

    $validData['scheduledDate'] = date("Y-m-d H:i", strtotime($date . ' ' . $time));

    // Customer
    $validData['customer'] = $unprocessedData['customer'];

    // Trade
    $validData['trade'] = $unprocessedData['trade'];

    // NTE
    $NTE = str_replace(["$", ","], "", $unprocessedData['NTE']);

    if(!is_numeric($NTE))
        throw new InvalidArgumentException("Invalid NTE format. Values is not float.");

    $validData['NTE'] = floatval($NTE);

    // Store ID
    $validData['storeID'] = $unprocessedData['storeID'];

    // Address
    $address = preg_split("/\n/", $unprocessedData["address"]);

    if (count($address) != 2)
        throw new InvalidArgumentException("Invalid address format. Address should be divided in two lines.");

    foreach ($address as &$line)
        $line = trim($line);

    $matches = [];

    preg_match_all('/([a-zA-Z\s]+)([A-Z]{2})\s+(\d+)/', $address[1], $matches);

    if(count($matches) != 4)
        throw new InvalidArgumentException("Invalid address format. Address should contains city, state and code.");

    $street = $address[0];
    $city = trim($matches[1][0]);
    $state = $matches[2][0];
    $code = $matches[3][0];

    $validData["street"] = $street;
    $validData["city"] = $city;
    $validData["state"] = $state;
    $validData["code"] = $code;

    // Phone number
    $phoneNumber = str_replace('-', '', $unprocessedData['phoneNumber']);

    if(!is_numeric($phoneNumber))
        throw new InvalidArgumentException("Phone number contains non-numerical charaters.");

    $validData['phoneNumber'] = floatval($phoneNumber);
} catch (InvalidArgumentException $e) {
    die($e->getMessage());
}

// Saving data
$file = fopen("test.csv", "w+");

if (!$file)
    die("Couldn't open a file.");

fputcsv($file, array_keys($validData));

fputcsv($file, $validData);

fclose($file);
