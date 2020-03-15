<?php

namespace App\Http\Controllers;

use App\CarbonFootprintCache;
use App\Http\Resources\CarbonFootprintResource;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class CarbonFootprint extends Controller
{
    private $apiToken = "4922f4bb62b02a24e1d310432dc8342337e0bb2c";
    private $association = [
        "activity" => 'activity',
        "activityType" => "activity_type",
        "fuelType" => "fuel_type",
        "mode" => "mode",
        "country" => "country",
        "carbonFootprint" => "carbon_footprint",
    ];

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
        $url .= "appTkn={$this->apiToken}";
        return $url;
    }

    private function validateRequestParameters(Request $request) {
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

    private function checkCarbonFootprintCache(array $data) {
        $where = [];
        foreach( $data as $key => $value ) {
            if ( $value ) {
                $where[$this->association[$key]] = $value;
            }
        }
        $cache = CarbonFootprintCache::where($where)->first();
        return $cache;
    }

    private function saveNewResponseToCache(array $data, float $result) {
        $columns = [];
        foreach( $data as $key => $value ) {
            $columns[$this->association[$key]] = $value;
        }
        $columns['carbon_footprint'] = $result;
        $carbonFootprint = CarbonFootprintCache::create($columns);
        if ( $carbonFootprint->save() ) {
            return $carbonFootprint;
        }
        return false;
    }

    public function get(Request $request) {

        $data = $this->validateRequestParameters($request);

        if ( gettype($data) === 'object' ) {
            return $data;
        }

        $cache = $this->checkCarbonFootprintCache($data);
        if ( $cache ) {
            return response()->json(new CarbonFootprintResource($cache, true));
        }

        $carbonFootprintApiUrl = $this->buildCarbonFootprintApiUrl($data);

        $client = new Client();
        $response = $client->request('GET', $carbonFootprintApiUrl);

        if ( (int) $response->getStatusCode() === 200 ) {
            $responseBody = json_decode($response->getBody());
            if ( isset($responseBody->carbonFootprint) ) {
                $savedRecord = $this->saveNewResponseToCache($data, (float)$responseBody->carbonFootprint);
                if ($savedRecord) {
                    return response()->json(new CarbonFootprintResource($savedRecord, false), $response->getStatusCode());
                }
            }
        }
        
        // This will be only excuted if there is any kind of error
        return response()->json(json_decode($response->getBody()), $response->getStatusCode());
    }
}


