<?php

define("UPDATE_RECORD_PATH",    "updateRecord");
define("RESULT_PATH",           "result");
define("DIR_SEPERATOR",         "/");
define("BACKUP_PATH",           "backup");
define("SNAPSHOT_PATH",         "snapshot");

/**
 *  Violation record structure supports both LSA-CSV format and Fusion Table format
 *  raw CSV : https://github.com/nansenat16/LSA-CSV
 *  Fustion table : http://jobhelper.g0v.ronny.tw/
 */
class violationRecord
{
    // Common field
    public $companyName;
    public $violationDateTw;
    public $violationLaw;
    public $violationDscr;
    public $violationDoc;

    // For Fusion Table
    public $violationDateCe;
    public $violationSummary;
    public $dataSource;
    public $dataImage;

    public function getLsaCsvHeader()
    {
        return "事業單位名稱,違反勞動基準法條款,違反法規內容,發文字號,處分日期";
    }

    public function getFusionCsvHeader()
    {
        return "公司名稱,發生日期,發生事由,原始連結,網頁截圖";
    }

    public function getLsaCsv()
    {
        return $this->companyName . "," .
             $this->violationLaw . "," .
             $this->violationDscr . "," .
             $this->violationDoc . "," .
             $this->violationDateTw;
    }

    public function getFusionCsv()
    {
        return $this->companyName . "," .
             $this->violationDateCe . "," .
             $this->violationSummary . "," .
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
    public $targetId = "Base";  // ID on LSA-CSV
    public $targetName = "Base";
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
    private $resultPath = "";
    private $updateRecordPath = "";
    private $backupPath = "";
    private $snapshotPath = "";

    /**
     *  Run crawler on $this->targetUrl and export $this->violationRecords
     *  in csv format
     */
    public function run($dateString)
    {
        $this->dump("");
        $this->dump("Start crawling for violation records.");

        $this->init($dateString);

        $this->dump("Getting violation records.");
        if ($this->getViolationRecords())
        {
            // We expect $this->violationRecords is filled in $this->getViolationRecords()
            $this->dump("Exporting records.");
            $this->exportViolationRecordToCsvFile();

            return true;
        }

        return false;
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
    protected function init($dateString)
    {
        // Initial required directories
        $this->debug("init()");

        $this->dateString = $dateString;

        // Stores update check file.
        $this->updateRecordPath = UPDATE_RECORD_PATH;
        if (!is_dir($this->updateRecordPath))
        {
            mkdir($this->updateRecordPath, 0777, true);
        }

        // Directory for exporting violation data
        $this->resultPath = RESULT_PATH . DIR_SEPERATOR . $this->dateString .
                            DIR_SEPERATOR . $this->targetId;
        if (!is_dir($this->resultPath))
        {
            mkdir($this->resultPath, 0777, true);
        }

        // Directory for saving packup data.
        // Files generated by $this->saveBackup() is saved here
        $this->backupPath = $this->resultPath;

        // Directory for web page snapshots.
        $this->snapshotPath = $this->resultPath;
    }

    protected function writeToFile($data, $filename)
    {
        $output = fopen($filename, "w+");
        if ($output)
        {
            fwrite($output, $data);
            fclose($output);
            return true;
        }
        return false;
    }

    /**
     *  Use MD5 to determine if $content has been updated. the MD5 record is
     *  stored in WORK_SPACE_PATH/$this->targetName/$this->targetName.content.md5
     *  There is only one value for each crawler, I believe(hope) it's enough.
     *
     *  Content of *.content.md5
     *  First line      md5(content), mandatory
     *  Second line     date of content captured
     */
    protected function checkUpdated($content)
    {
        $this->debug("checkUpdated()");

        $tagFilename = $this->updateRecordPath . DIR_SEPERATOR .
                       $this->targetId . "_" . $this->targetName . ".content.md5";
        $contentMd5 = md5($content);
        @$lastContentMd5 = file($tagFilename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Check MD5 to determine if $content has been changed.
        // MD5 not exist is treated as $content changed.
        if ($lastContentMd5[0] && trim($lastContentMd5[0], "\r\n") == $contentMd5)
        {
            $this->dump("Content not changed since last crawling.");
            return false;
        }

        // Update MD5 record
        $this->writeToFile($contentMd5 . "\n" . $this->dateString, $tagFilename);
        return true;
    }

    /**
     *  Save URL to BACKUP_PATH/$this->targetName/
     *  The filename will be $prefix + date + $postfix
     */
    protected function downloadUrl($url, $prefix, $postfix)
    {
        $content = $this->http($url, $url, false, array());
        $backupFilename = $this->backupPath . DIR_SEPERATOR . $prefix . $this->dateString . $postfix;

        $this->writeToFile($content, $backupFilename);
        return true;
    }

    /**
     *  Save content to BACKUP_PATH/$this->targetName/
     *  The filename will be $prefix + date + $postfix
     */
    protected function saveBackup($content, $prefix, $postfix)
    {
        $backupFilename = $this->backupPath . DIR_SEPERATOR . $prefix . $this->dateString . $postfix;

        $this->writeToFile($content, $backupFilename);
        return true;
    }

    /**
     *  Capture snapshot of web page and save to SNAPSHOT_PATH/$this->targetName/
     *  The filename will be $prefix + date
     *
     *  Use http://www.hiqpdf.com/demo/ConvertHtmlToImage.aspx now
     */
    protected function getSnapShot($url, $prefix)
    {
        $this->debug("getSnapShot() for " . $url);

        $snapShotProvider = "http://www.hiqpdf.com/demo/ConvertHtmlToImage.aspx";
        $img = $this->http($snapShotProvider, $snapShotProvider, true, [
                                '__LASTFOCUS' => '',
                                'ctl00_treeView_ExpandState' => 'nennnnnnnnnnnnnnnennnnnnnnnnn',
                                'ctl00_treeView_SelectedNode' => 'ctl00_treeViewt15',
                                '__EVENTTARGET' => '',
                                '__EVENTARGUMENT' => '',
                                'ctl00_treeView_PopulateLog' => '',
                                '__VIEWSTATE' => '/wEPDwUIMTI3MTYxNzcPZBYCZg9kFgICAw9kFgQCAQ88KwAJAgAPFgYeDU5ldmVyRXhwYW5kZWRkHgxTZWxlY3RlZE5vZGUFEWN0bDAwX3RyZWVWaWV3dDE1HglMYXN0SW5kZXgCHWQIFCsAD2QUKwACFgIeCEV4cGFuZGVkZ2QUKwACFgIfA2cUKwAOZBQrAAIWAh8DZ2QUKwACFgIfA2dkFCsAAhYCHwNnZBQrAAIWAh8DZ2QUKwACFgIfA2dkFCsAAhYCHwNnZBQrAAIWAh8DZ2QUKwACFgIfA2dkFCsAAhYCHwNnZBQrAAIWAh8DZ2QUKwACFgIfA2dkFCsAAhYCHwNnZBQrAAIWAh8DZ2QUKwACFgQeCFNlbGVjdGVkZx8DZ2QUKwACFgIfA2dkFCsAAhYCHwNnFCsAA2QUKwACFgIfA2dkFCsAAhYCHwNnZBQrAAIWAh8DZ2QUKwACFgIfA2dkFCsAAhYCHwNnZBQrAAIWAh8DZ2QUKwACFgIfA2dkFCsAAhYCHwNnZBQrAAIWAh8DZ2QUKwACFgIfA2dkFCsAAhYCHwNnZGQCAw9kFgICAw9kFgJmD2QWAgIJDxBkZBYBZmQYAwUeX19Db250cm9sc1JlcXVpcmVQb3N0QmFja0tleV9fFgUFDmN0bDAwJHRyZWVWaWV3BS9jdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJHJhZGlvQnV0dG9uQ29udmVydFVybAU0Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRyYWRpb0J1dHRvbkNvbnZlcnRIdG1sQ29kZQU0Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRyYWRpb0J1dHRvbkNvbnZlcnRIdG1sQ29kZQUyY3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRjaGVja0JveFRyYW5zcGFyZW50SW1hZ2UFImN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkZGVtb1RhYnMPD2RmZAUiY3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRkZW1vTWVudQ8PZAUBMGQ=',
                                '__EVENTVALIDATION' => '/wEdABA8QMyC+Lgt4pggz3m0vRAZuCtPWWr3lQqwkNVrFep13DRsOdRYtyNK/pfSRf4IpDHSJu3SChououi6IfjmzQp0fJiEIDKFbqI+ekloWfNvJ1hTIFooOkHCOLjng6mhybbHgXFJfyGXjsy72bjvoGjjXej5qz5mz748+AchEy1ssxn9iG37gf6XR6aXCVWFtwlTHu84NKYILlryojtfonLvXTQNg6I6Uj8X4CWpA9Wyr16/IKhT9upp0uC97vmG85omisyJzVuJ+vbMdZoMKTFwVD07DJFIM9AyfoJR123X1Z1/uWcTrwAVrtEjfMqvye1TEcgCqPnKz2A8x4DImfNC',
                                'ctl00$ContentPlaceHolder1$UrlOrHtmlCode' => 'radioButtonConvertUrl',
                                'ctl00$ContentPlaceHolder1$textBoxUrl' => $url,
                                'ctl00$ContentPlaceHolder1$dropDownListImageFormat' => 'PNG',
                                'ctl00$ContentPlaceHolder1$checkBoxTransparentImage' => 'on',
                                'ctl00$ContentPlaceHolder1$textBoxBrowserWidth' => 1200,
                                'ctl00$ContentPlaceHolder1$textBoxLoadHtmlTimeout' => 120,
                                'ctl00$ContentPlaceHolder1$buttonConvertToImage' => 'Convert to Image'
                           ]);


        $this->writeToFile($img, $this->snapshotPath . DIR_SEPERATOR . $prefix .
                                 $this->dateString . ".png");
        return;
    }

    /*
     *  Get content of $target through http protocol
     *
     *  Borrowed from https://github.com/ronnywang/fidb-crawler/blob/master/crawler.php
     */
    protected function http($target, $referer, $usePost, $post_params)
    {
        $this->debug("http()");

        if (is_null($this->_curl)) {
            $this->_curl = curl_init();
        }

        $curl = $this->_curl;

        if ($usePost) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');

            $terms = array();
            foreach ($post_params as $k => $v) {
                $terms[] = urlencode($k) . '=' . urlencode($v);
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, implode('&', $terms));
        } else {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        }
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
            echo $this->targetName . ":" . $msg . "\n";
        }
    }

    /**
     *  Always print message
     */
    protected function dump($msg)
    {
        echo $this->targetName . ":" . $msg . "\n";
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
     *  Export $this->violationRecords into RESULT_PATH/$this->targetName in csv
     *  form, the filename will be $this->targetName-DATE.csv
     */
    protected function exportViolationRecordToCsvFile()
    {
        $recordHeader = new violationRecord;

        // Export LSA-CSV format
        $csvFilename = $this->resultPath . DIR_SEPERATOR .
                       $this->targetName . "-LSA-" . $this->dateString . ".csv";
        $csvFile = fopen($csvFilename, "w+");

        if (!$csvFile)
        {
            return false;
        }

        fwrite($csvFile, $recordHeader->getLsaCsvHeader() . "\n");
        foreach ($this->violationRecords as $record)
        {
            fwrite($csvFile, $record->getLsaCsv() . "\n");
        }

        fclose($csvFile);

        // Export Fusion format
        $csvFilename = $this->resultPath . DIR_SEPERATOR .
                       $this->targetName . "-Fusion-" . $this->dateString . ".csv";
        $csvFile = fopen($csvFilename, "w+");

        if (!$csvFile)
        {
            return false;
        }

        fwrite($csvFile, $recordHeader->getFusionCsvHeader() . "\n");
        foreach ($this->violationRecords as $record)
        {
            fwrite($csvFile, $record->getFusionCsv() . "\n");
        }

        fclose($csvFile);

        return true;
    }

    protected function getUrlPath($url)
    {
        $urlInfo = parse_url($url);
        $cleanUrl = $urlInfo['scheme'] . "://" . $urlInfo['host'] . $urlInfo['path'];
        // cleanUrl contains filename
        return  substr($cleanUrl, 0, strrpos($cleanUrl, "/") + 1);
    }
}

?>
