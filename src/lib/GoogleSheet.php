<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Accuweather\App\Lib;

/**
 * Description of classGoogleSheet
 *
 * @author Hans
 */
class GoogleSheet
{
    /**
     * 
     * @var \Google_Service_Drive
     */
    private $driveService;
    
    /**
     * 
     * @var \Google_Service_Sheets
     */
    private $service;
    private $columns = ['Name','Country','Region','Timezone','Rank','Latitude','Longitude','Weather Text','Is Day Time','Temperature Celsius (C)','Temperature Fahrenheit (F)'];
    
    /**
     * Constructor
     */
    public function __construct()
    {
        
    }
    
    /**
     * create new spreadsheet in google drive 
     * 
     * @param array $sharedWith
     * @return Google\Service\Sheets\Spreadsheet $spreadsheet
     */
    public function createGoogleSheet($sharedWith = []) {
        
        $spreadsheet = new \Google_Service_Sheets_Spreadsheet([
            'properties' => [
                'title' => "AccuWeather TopCities Data: ".date('d-m-y')
            ]
        ]);
        $spreadsheet = $this->service->spreadsheets->create($spreadsheet, [
            'fields' => 'spreadsheetId'
        ]);
        //printf("Spreadsheet ID: %s\n", $spreadsheet->spreadsheetId);
        if($sharedWith) {
            foreach ($sharedWith as $key => $useremail)
            {
                $domainPermission = new \Google_Service_Drive_Permission([
                    'type'  => 'user',
                    'role'  => 'reader',
                    'emailAddress' => $useremail //eg user@myCompany.com
                ]);

                $permissions = $this->driveService->permissions->create($spreadsheet->spreadsheetId, $domainPermission);
            }
        }
        

        return $spreadsheet;
    }
    
    /**
     * update the sheet data 
     * 
     * @param String $spreadsheetId
     * @param String $range yoursheetname! cellId i.e Sheet1!A1
     * @param String $valueInputOption https://developers.google.com/sheets/api/reference/rest/v4/ValueInputOption
     * @param Array $_values
     * @return type
     */
    public function batchUpdateValues($spreadsheetId, $range, $valueInputOption, $_values)
    {
        $service = $this->service;
        
        array_unshift($_values,$this->columns);
        
        $values = [
            // Additional rows ...
        ];
        // [START_EXCLUDE silent]
        $values = $_values;
        // [END_EXCLUDE]
        $data = [];
        $data[] = new \Google_Service_Sheets_ValueRange([
            'range' => $range,
            'values' => $values
        ]);
        // Additional ranges to update ...
        $body = new \Google_Service_Sheets_BatchUpdateValuesRequest([
            'valueInputOption' => $valueInputOption,
            'data' => $data
        ]);
        $result = $service->spreadsheets_values->batchUpdate($spreadsheetId, $body);



        // get sheetId of sheet with index 0
        $sheetId = $service->spreadsheets->get($spreadsheetId);
        $sheetId = $sheetId->sheets[0]->properties->sheetId;

        // set colour to a medium gray
        $red = 66; $green = 134; $blue = 244;

        // define range
        $myRange = [
            'sheetId' => $sheetId, // IMPORTANT: sheetId IS NOT the sheets index but its actual ID
            'startRowIndex' => 0,
            'endRowIndex' => 1,
            'endColumnIndex' => 11,
        ];

        // define the formatting, change background colour and bold text
        $format = [
            'backgroundColor' => [
                'red' => $red/255,
                'green' => $green/255,
                'blue' => $blue/255
            ],
            'textFormat' => [
              'foregroundColor'=>[
                'red' => 1.0,
                'green' => 1.0,
                'blue' => 1.0,
              ],
              'bold' => true
            ]
        ];

        // build request
        $requests = [
            new \Google_Service_Sheets_Request([
                'repeatCell' => [
                    'fields' => 'userEnteredFormat.backgroundColor, userEnteredFormat.textFormat.bold,userEnteredFormat.textFormat.foregroundColor',
                    'range' => $myRange,
                    'cell' => [
                        'userEnteredFormat' => $format,
                    ],
                ],
            ])
        ];

        // add request to batchUpdate    
        $batchUpdateRequest = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
          'requests' => $requests
        ]);

        // run batchUpdate
        $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);

        return $result;
    }
    
    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     */
    public function getClient()
    {
        $client = new \Google_Client();
        $client->setApplicationName('Google Sheets API PHP Quickstart');
        $client->setScopes(\Google_Service_Sheets::DRIVE);
        $client->setAuthConfig(__dir__.'/../../config/credentials.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.
        $tokenPath = __dir__.'/../../config/token.json';
        if (file_exists($tokenPath))
        {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired())
        {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken())
            {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            }else
            {
                // Request authorization from the user.
                print sprintf("<a href='%s'>Authenticate With Google</a>", $client->createAuthUrl());
                if(array_key_exists('code', $_GET)) {
                    // Exchange authorization code for an access token.
                    $accessToken = $client->fetchAccessTokenWithAuthCode($_GET['code']);
                    $client->setAccessToken($accessToken);

                    // Check to see if there was an error.
                    if (array_key_exists('error', $accessToken))
                    {
                        throw new Exception(join(', ', $accessToken));
                    }
                    
                    header('location:'.APP_URL);
                }
            }
            
        }
        // Save the token to a file.
        if($client->getAccessToken()) {
            if (!file_exists(dirname($tokenPath)))
            {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));

            $this->service = new \Google_Service_Sheets($client);
            $this->driveService = new \Google_Service_Drive($client);
        }
        return $client;
    }

}
