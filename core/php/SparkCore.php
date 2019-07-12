<?php namespace Wensleydale;

use Guzzle\Http\Client;

/**
 * Class SparkCore
 * "spark" corresponds to the old name of particle cloud
 * @package Wensleydale
 */
class SparkCore
{

    /**
     * @param $accessToken
     * @return Spark
     */
    public static function make($accessToken)
    {
        $client = new Client('https://api.particle.io/{version}', array(
            'version' => 'v1',
            'request.options' => array(
                'headers' => array('Authorization' => 'Bearer ' . $accessToken)
            )
        ));

        return new Spark($client);
    }

    public static function token()
    {
        $client = new Client('https://api.particle.io');

        return new SparkToken($client);
    }

} 
