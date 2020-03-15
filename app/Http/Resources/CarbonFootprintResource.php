<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CarbonFootprintResource extends JsonResource
{

    protected bool $cached;

    public function __construct($resource, bool $cached) {
        $this->cached = $cached;
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $output = [];
        $output['carbonFootprint'] = $this->carbon_footprint;
        if ( $this->cached ) {
            $output['cached'] = true;
            $output['cached_at'] = $this->created_at;
        } else {
            $output['cached'] = false;
        }
        $output['poweredBy'] = 'https://triptocarbon.xyz';
        return $output;
    }

}
