<?php

define("WORK_SPACE_PATH",   "workspace");
define("RESULT_PATH",       "result");
define("DIR_SEPERATOR",     "/");
define("BACKUP_PATH",       "backup");
define("SNAPSHOT_PATH",     "snapshot");

/**
 *  Violation record structure is as same as Fusion Table
 *  http://jobhelper.g0v.ronny.tw/
 */
class violationRecord
{
    public $companyName;
    public $violationDate;
    public $violations;
    public $dataSource;
    public $dataImage;

    public function toCsv()
    {
        return $this->companyName . "," .
             $this->violationDate . "," .
             $this->violations . "," .
             $this->dataSource . "," .
             $this->dataImage;
    }
}

/**
 *  Crawler basic class
 */
class crawlerBase
{
    /**
     *  Following data must be over-writen.
     */
    public $targetUrl = "";
    public $refererUrl = "";
    public $moduleName = "Base";
    private $_debug = false;  // debug switch

    /**
     *  Violation records
     */
    protected $violationRecords = array();

    /**
     *  Runtime data
     */
    private $_curl;
    private $dateString = "";
    private $workspacePath = "";
    private $backupPath = "";
    private $snapshotPath = "";

    /**
     *  Run crawler on $this->targetUrl and export $this->violationRecords
     *  in csv format
     */
    public function run()
    {
        $this->dump("Start crawling for violation record of " . $this->moduleName);

        $this->init();

        $this->dump("Getting violation records.");
        if ($this->getViolationRecords())
        {
            // We expect $this->violationRecords is filled in $this->getViolationRecords()
            $this->dump("Exporting records.");
            $this->exportViolationRecordToCsvFile();
        }
    }

    /**
     *  Crawler implementation, crawler result must be filled in $this->violationRecords
     *  Return true for success, false for failed or not updated
     */
    protected function getViolationRecords()
    {
        echo "Must be implemented";
        return false;
    }

    /**
     *  Shared functions
     */
    protected function init()
    {
        // Initial required directories
        $this->debug("init()\n");

        // Directory for exporting violation data
        if (!is_dir(RESULT_PATH))
        {
            mkdir(RESULT_PATH, 0777, true);
        }

        // Directory as crawler workspace.
        // It stores update check file now.
        $this->workSpacePath = WORK_SPACE_PATH . DIR_SEPERATOR . $this->moduleName;
        if (!is_dir($this->workSpacePath))
        {
            mkdir($this->workSpacePath, 0777, true);
        }

        // Directory for saving packup data.
        // Files generated by $this->saveBackup() is saved here
        $this->backupPath = BACKUP_PATH . DIR_SEPERATOR . $this->moduleName;
        if (!is_dir($this->backupPath))
        {
            mkdir($this->backupPath, 0777, true);
        }

        // Directory for web page snapshots.
        $this->snapshotPath = SNAPSHOT_PATH . DIR_SEPERATOR . $this->moduleName;
        if (!is_dir($this->snapshotPath))
        {
            mkdir($this->snapshotPath, 0777, true);
        }

        $this->dateString = date("Y_m_d_H_i_s");
    }

    /**
     *  Use MD5 to determine if $content has been updated. the MD5 record is
     *  stored in WORK_SPACE_PATH/$this->moduleName/$this->moduleName.content.md5
     *  There is only one value for each crawler, I believe(hope) it's enough.
     *
     *  Content of *.content.md5
     *  First line      md5(content), mandatory
     *  Second line     date of content captured
     */
    protected function checkUpdated($content)
    {
        $this->debug("checkUpdated()\n");

        $tagFilename = $this->workSpacePath . DIR_SEPERATOR . $this->moduleName . ".content.md5";
        $contentMd5 = md5($content);
        @$lastContentMd5 = file($tagFilename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Check MD5 to determine if $content has been changed.
        // MD5 not exist is treated as $content changed.
        if ($lastContentMd5[0] && trim($lastContentMd5[0], "\r\n") == $contentMd5)
        {
            $this->dump("Content not changed since last crawling.\n");
            return false;
        }

        // Update MD5 record
        $tagFile = fopen($tagFilename, "w+");
        if ($tagFile)
        {
            fwrite($tagFile, $contentMd5 . "\n");
            fwrite($tagFile, $this->dateString);

            fclose($tagFile);
        }
        return true;
    }

    /**
     *  Save content to BACKUP_PATH/$this->moduleName/
     *  The filename will be $prefix + date + $postfix
     */
    protected function saveBackup($content, $prefix, $postfix)
    {
        $backupFilename = $this->backupPath . DIR_SEPERATOR . $prefix . $this->dateString . $postfix;
        $backupFile = fopen($backupFilename, "w+");
        if ($backupFile)
        {
            fwrite($backupFile, $content);
            fclose($backupFile);
        }

        return true;
    }

    /**
     *  Capture snapshot of web page and save to SNAPSHOT_PATH/$this->moduleName/
     *  The filename will be $prefix + date
     *
     *  I don't know who to do it yet.
     */
    protected function getSnapShot($url, $prefix)
    {
        // TODO: Get Snapshot
        return;
    }

    /*
     *  Get content of $target through http protocol
     *
     *  Borrowed from https://github.com/ronnywang/fidb-crawler/blob/master/crawler.php
     */
    protected function http($target, $referer, $post_params)
    {
        $this->debug("http()\n");

        if (is_null($this->_curl)) {
            $this->_curl = curl_init();
        }

        $curl = $this->_curl;
        $terms = array();

        foreach ($post_params as $k => $v) {
            $terms[] = urlencode($k) . '=' . urlencode($v);
        }

        curl_setopt($curl, CURLOPT_POSTFIELDS, implode('&', $terms));
        curl_setopt($curl, CURLOPT_COOKIEFILE, 'cookie-file');
        curl_setopt($curl, CURLOPT_URL, $target);
        curl_setopt($curl, CURLOPT_REFERER, $referer);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $content = curl_exec($curl);
        return $content;
    }

    /**
     *  Only print message on $this->_debug == enabled
     */
    protected function debug($msg)
    {
        if ($this->_debug)
        {
            echo $this->moduleName . ":" . $msg . "\n";
        }
    }

    /**
     *  Always print message
     */
    protected function dump($msg)
    {
        echo $this->moduleName . ":" . $msg . "\n";
    }

    /**
     *  Transform date in ROC to AD
     */
    protected function twDateTransform($twDateString)
    {
        $year = (int)preg_replace("/[^0-9]*([0-9]*)年.*/", "\\1", $twDateString) + 1911;
        $month = preg_replace("/.*年([0-9]*)月.*/", "\\1", $twDateString);
        $day = preg_replace("/.*年.*月([0-9]*)日.*/", "\\1", $twDateString);
        return "$year/$month/$day";
    }

    /**
     *  Export $this->violationRecords into RESULT_PATH/$this->moduleName in csv
     *  form, the filename will be $this->moduleName-DATE.csv
     */
    protected function exportViolationRecordToCsvFile()
    {
        $csvFilename = RESULT_PATH . DIR_SEPERATOR . $this->moduleName . "-" . $this->dateString . ".csv";
        $csvFile = fopen($csvFilename, "w+");

        if (!$csvFile)
        {
            return false;
        }

        foreach ($this->violationRecords as $record)
        {
            fwrite($csvFile, $record->toCsv() . "\n");
        }

        fclose($csvFile);

        return true;
    }
}

?>
