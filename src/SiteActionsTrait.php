<?php

namespace Drupal\aqto_ai_core;

trait SiteActionsTrait
{

    /**
     * Returns a standardized array of a "result" from an action taken. 
     * 
     * We have an arg of some data chunk that we can return as well as the "status".
     */
    public function getStandardizedResult($action, $data, $status = 'success')
    {
        return [
            'action' => $action,
            'status' => $status,
            'data' => $data,
        ];
    }
}
