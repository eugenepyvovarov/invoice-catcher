<?php

namespace App\Components;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Carbon;

class TrafikverketApi
{
    const ACTIVITY_TYPE_ARRIVAL = 'Ankomst';
    const ACTIVITY_TYPE_DEPARTURE = 'Avgang';

    public $apiUrl;
    private $apiToken;

    function __construct()
    {
        $this->apiToken = 'a7b5e58253d04384a8c8dbc4bc0a093c'; // @todo put to config
        $this->apiUrl = 'https://api.trafikinfo.trafikverket.se/v2/data.json';// @todo put to config
    }

    /**
     * @param null $activityType
     * @param null $trainNumber
     * @param null $locationSignature
     * @param null $scheduledDepartureDateTime
     * @return mixed
     */
    public function getTrainAnnouncements($activityType = null, $trainNumber = null, $locationSignature = null, $scheduledDepartureDateTime = null)
    {
        $query = '<QUERY objecttype="TrainAnnouncement" schemaversion="1.3" orderby="AdvertisedTimeAtLocation">
                        <FILTER>
                          <AND>';
        if ($activityType) {
            $query .= '<EQ name="ActivityType" value="'.$activityType.'" />';
        }

        if ($trainNumber) {
            $query .= '<EQ name="AdvertisedTrainIdent" value="'.$trainNumber.'" />';
        }

        if ($locationSignature) {
            $query .= '<EQ name="LocationSignature" value="'.$locationSignature.'" />';
        }

        if ($scheduledDepartureDateTime) {
            $query .= '<EQ name="ScheduledDepartureDateTime" value="'.$scheduledDepartureDateTime.'" />';
        }

        $query .= '

        </AND>
                   </FILTER>
                   
        
        <INCLUDE>ActivityType</INCLUDE>
        <INCLUDE>LocationSignature</INCLUDE>
        <INCLUDE>ScheduledDepartureDateTime</INCLUDE>
        <INCLUDE>AdvertisedTrainIdent</INCLUDE>
        <INCLUDE>AdvertisedTimeAtLocation</INCLUDE>
        <INCLUDE>TimeAtLocation</INCLUDE>
                  </QUERY>';

        return $this->callApi('POST', $query);
    }

    /**
     * @param $activityType
     * @param $trainNumber
     * @param $locationSignature
     * @param Carbon $time
     * @return mixed
     */
    public function getTrainArrivalDeparture($activityType, $trainNumber, $locationSignature, Carbon $time)
    {
        return $this->callApi('POST', '<QUERY objecttype="TrainAnnouncement" schemaversion="1.3" orderby="AdvertisedTimeAtLocation">
                        <FILTER>
                          <AND>
                            <EQ name="ActivityType" value="'.$activityType.'" />
                            <EQ name="AdvertisedTrainIdent" value="'.$trainNumber.'" />
                            <EQ name="LocationSignature" value="'.$locationSignature.'" />
                            <EQ name="AdvertisedTimeAtLocation" value="'.$time->toDateTimeString().'" />
                            </AND>
                        </FILTER>
                         </QUERY>'
           );

    }

    /**
     * @param $activityType
     * @param $trainNumber
     * @param $location
     * @param Carbon $time
     * @return mixed
     */
    public function getStations()
    {
        return $this->callApi('POST', '
                  <QUERY objecttype="TrainStation" schemaversion="1">
                        <FILTER>
                              <EQ name="Advertised" value="true" />
                        </FILTER>
                        <INCLUDE>AdvertisedLocationName</INCLUDE>
                        <INCLUDE>AdvertisedShortLocationName</INCLUDE>
                        <INCLUDE>LocationSignature</INCLUDE>
                  </QUERY>'
           );
    }


    /**
     * @param $method
     * @param $query
     * @return mixed
     */
    protected function callApi($method, $query)
    {
        try {
            $response = (new Client())->request($method, $this->apiUrl, [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=UTF8',
                ],
                'body' => '<REQUEST>
                      <LOGIN authenticationkey="'.$this->apiToken.'" />
                      '.$query.'                     
                </REQUEST>',
            ]);

        } catch (RequestException $e) {
            return $body = json_decode($e->getResponse()->getBody(), true);
        }
        return json_decode($response->getBody(), true);
    }
}