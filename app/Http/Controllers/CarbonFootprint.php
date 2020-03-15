<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class CarbonFootprint extends Controller
{

    private function returnError(string $message) {
        $output = (object)[];
        $output->error = true;
        $output->message = $message;
        return response()->json($output, 400);
    }

    private function buildCarbonFootprintApiUrl(array $data):string {
        $url = "https://api.triptocarbon.xyz/v1/footprint?";
        foreach ($data as $key => $value) {
            if ( $value ) {
                $url .= "{$key}={$value}&";
            }
        }
        $url = rtrim($url, '&');
        return $url;
    }

    private function validateRequestParameters(Request $request):array {
        // Activity validation
        $activity = (float) $request->input('activity', false);
        if ( !$activity || $activity <= 0 ) {
            return $this->returnError("Activity is required and should be a positive number");
        }

        // Activity Type validation
        $activityType = (string) $request->input('activityType', false);
        if ( !$activityType ) {
            return $this->returnError("Activity Type is required.");
        }
        $allowedActivityTypes = ['miles', 'fuel'];
        if ( !in_array($activityType, $allowedActivityTypes) ) {
            return $this->returnError("Activity Type is invalid. valid values for this parameter are [miles, fuel]");
        }

        // Fuel Type validation
        $fuelType = (string)$request->input('fuelType', false);
        $allowedFuelTypes = ['motorGasoline', 'diesel', 'aviationGasoline', 'jetFuel'];
        if ( $fuelType && $activityType === 'fuel' ) {
            if ( !in_array($fuelType, $allowedFuelTypes) ) {
                $fuelType = false;
            }
        }

        // Mode validation
        $mode = (string)$request->input('mode', false);
        $allowedModes = ['dieselCar', 'petrolCar', 'anyCar', 'taxi', 'economyFlight', 'businessFlight', 'firstclassFlight', 'anyFlight', 'motorbike', 'bus', 'transitRail'];
        if ( $activityType === 'miles' ) {
            if ( !$mode ) {
                return $this->returnError("Mode is required when activityType represents the miles travelled");
            }
            if ( !in_array($mode, $allowedModes) ) {
                return $this->returnError("Mode is invalid. valid values for this parameter are [dieselCar, petrolCar, anyCar, taxi, economyFlight, businessFlight, firstclassFlight, anyFlight, motorbike, bus, transitRail]");
            }
        }

        // Country validation
        $country = (string)$request->input('country', false);
        $allowedCountries = ['usa', 'gbr', 'def'];
        if ( !$country ) {
            return $this->returnError("Country is required.");
        }
        if ( !in_array($country, $allowedCountries) ) {
            return $this->returnError("Country is invalid. valid values for this parameter are [usa, gbr, def]");
        }

        $data = [];
        $data['activity'] = $activity;
        $data['activityType'] = $activityType;
        $data['fuelType'] = $fuelType;
        $data['mode'] = $mode;
        $data['country'] = $country;
        return $data;
    }

    public function get(Request $request) {

        $data = $this->validateRequestParameters($request);

        $carbonFootprintApiUrl = $this->buildCarbonFootprintApiUrl($data);

        $client = new Client();
        $response = $client->request('GET', $carbonFootprintApiUrl);
        // $response = $client->request('GET', 'https://api.github.com/repos/guzzle/guzzle');

        return response()->json(json_decode($response->getBody()));

        echo $response->getStatusCode();
        echo "<br><br>";
        echo $response->getHeaderLine('content-type');
        echo "<br><br>";
        echo $response->getBody();
        

    }
}


