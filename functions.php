<?php

/**
 * @package DspaceRestapiHarvester
 * @modified from OaipmhHarvester
 */

function dspace_restapi_harvester_config($key, $default = null)
{
    $config = Zend_Registry::get('bootstrap')->getResource('Config');
    if (isset($config->plugins->DspaceRestapiHarvester->$key)) {
        return $config->plugins->DspaceRestapiHarvester->$key;
    } else if ($default) {
        return $default;
    } else {
        return null;
    }
}
