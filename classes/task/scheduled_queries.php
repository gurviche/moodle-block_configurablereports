<?php

namespace block_configurable_reports\task;


class scheduled_queries extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('scheduledqueries', 'block_configurable_reports');
    }

    public function execute() {
        global $CFG, $DB;
$CFG->debug=0;
$CFG->debugdeveloper=0;
$CFG->debugdisplay=0;

        $hour = get_config('block_configurable_reports','cron_hour');
        $min = get_config('block_configurable_reports','cron_minute');

        $date = usergetdate(time());
        $usertime = mktime($date['hours'], $date['minutes'], $date['seconds'], $date['mon'], $date['mday'], $date['year']);

        $crontime = mktime($hour, $min, $date['seconds'], $date['mon'], $date['mday'], $date['year']);

    #    if ( ($crontime - $usertime) < 0 ) return false;

    #    $lastcron = $DB->get_field('block', 'lastcron', array('name' => 'configurable_reports'));
    #    if (!$lastcron and ($lastcron + $this->cron < time()) ) return false;

        // Starting to run...
        //$DB->set_field('blocks','lastcron',time(), array('name' => 'configurable_reports'));

        require_once($CFG->dirroot."/blocks/configurable_reports/locallib.php");
        require_once($CFG->dirroot.'/blocks/configurable_reports/report.class.php');
        require_once($CFG->dirroot.'/blocks/configurable_reports/reports/sql/report.class.php');

        mtrace("\nConfigurable report (block)");

        $reports = $DB->get_records('block_configurable_reports');
	$DB->execute("SET SESSION wait_timeout=100000");
        if ($reports) {
            foreach ($reports as $report) {
                // Running only SQL reports. $report->type == 'sql'
                if ($report->type == 'sql' AND (!empty($report->cron) AND $report->cron == '1')) {
                    $reportclass = new \report_sql($report);

                    // Execute it using $remoteDB
                    $starttime = microtime(true);
                    mtrace("\nExecuting query '$report->name'");
                    //$results = $reportclass->create_report();
                    $components = cr_unserialize($reportclass->config->components);
                    $config = (isset($components['customsql']['config']))? $components['customsql']['config'] : new stdclass;
                    $sql = $reportclass->prepare_sql($config->querysql);
                    //if (strpos($sql, ';') !== false) {
                        $sqlqueries = explode(';',$sql);
                    //} else
                    foreach ($sqlqueries as $sql) {
                        mtrace(substr($sql,0,60)); // Show some SQL
                        $results = $reportclass->execute_query($sql);
                        mtrace(($results==1) ? '...OK time='.round((microtime(true) - $starttime) * 1000).'mSec' : 'Some SQL Error'.'\n');
                    }
                    unset($reportclass);
                }
            }
        }
        return true; // Finished OK.
   
    }

}
