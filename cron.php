<?php

(PHP_SAPI !== 'cli' || isset($_SERVER['HTTP_USER_AGENT'])) && die('cli only');


/**
 * step 1 : create an app on accuweather 
 * created and got the API key 4sXStoFjwJ5F7zuvz5KpatUBGj8OV0pr
 */
require_once __dir__ . '/vendor/autoload.php';

use Accuweather\App\Lib\Accuweather;
use Accuweather\App\Lib\GoogleSheet;

define('APP_URL', 'http://localhost/accuweather/');
/**
 * Step 2 : Get the Top 50 cities data from its Location & Current Conditions APIs
 * 
 */
try
{
    $accuweather = new Accuweather();

    $topcities = $accuweather->getCitiesData();
} catch (Exception $exc)
{
    echo $exc->getMessage();
    die;
}

/**
 * step 3: Combine the two datasets to extract the relevant data fields of the Top 50 cities. In addition, you can look for the dummy dataset below for reference
 */

if($topcities->statusCode === 200) {
    $reportData = [];
    $rank = 1;
    foreach ($topcities->data as $key => $dataset)
    {
        //{"name": "Dhaka", "country": "Bangladesh", "region": "Asia", "timezone": "Asia/Dhaka", "rank": 10, "latitude": 23.7098, "longitude": 90.40711, "weather_text": "Partly sunny", "is_day_time": true, "temperature_celsius": 33, "temperature_fahrenheit": 91}
        $reportData[] = [
            $dataset->EnglishName, // name
            $dataset->Country->EnglishName, // country
            $dataset->Country->EnglishName, // region
            $dataset->TimeZone->Name, // timezone
            $rank, // rank
            $dataset->GeoPosition->Latitude, // latitude
            $dataset->GeoPosition->Longitude, // longitude
            $dataset->WeatherText, // weather_text
            $dataset->IsDayTime ? 'true' : 'false', // is_day_time
            $dataset->Temperature->Metric->Value, //temperature_celsius
            $dataset->Temperature->Imperial->Value, //temperature_fahrenheit
        ];
        
        $rank++;
    }
}else {
    echo "Some error to get the data. Please try again later";
    die;
}


/**
 * step 4: Create a google excel sheet with the final report data.
 * 
 * step 5: Share the excel sheet over email with us (zahin.alwa@adsparc.com, marc.lefoll@adsparc.com). Upload the google excel sheet to your google drive and add the generated public link to your email.
 */

//print_r($reportData);

$sheet = new GoogleSheet();
$googleClient = $sheet->getClient();
if($googleClient->getAccessToken()) {
    try
    {
        $spreadsheet = $sheet->createGoogleSheet(['']);
        $sheetId = $spreadsheet->spreadsheetId;
 
        $response = $sheet->batchUpdateValues($sheetId, "Sheet1!A1", 'RAW', $reportData);
        
        printf("%d cells updated.", $response->getTotalUpdatedCells());
        
    } catch (Exception $exc)
    {
        echo $exc->getMessage();
        die;
    }
}
