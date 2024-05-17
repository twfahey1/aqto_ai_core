<?php

namespace Drupal\aqto_ai_core;

trait SiteActionsTrait
{

    /**
     * Returns a standardized array of a "result" from an action taken. 
     * 
     * We have an arg of some data chunk that we can return as well as the "status".
     * 
     * @param string $action
     *  The action that was taken.
     * 
     * @param mixed $data
     * The data that was returned.
     * 
     * @param string $status
     * The status of the action standardized like "success" or "error".
     * 
     * @param mixed $report
     * The report of the action optional HTML that will display to frontend.
     */
    public function getStandardizedResult($action, $data, $status = 'success', $report = NULL)
    {
        $return_data = [
            'action' => $action,
            'status' => $status,
            'data' => $data,
        ];
        // Set report if not null
        if ($report) {
            $return_data['report'] = $report;
        }
        return $return_data;
    }
}
