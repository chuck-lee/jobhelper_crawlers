<?php

include_once 'crawlerBase.php';

class crawlerTaipei extends crawlerBase
{
    public $targetUrl = "http://www.bola.taipei.gov.tw/ct.asp?xItem=41223990&ctNode=62846&mp=116003";
    public $refererUrl = "http://www.bola.taipei.gov.tw/ct.asp?xItem=41223990&ctNode=62846&mp=116003";
    public $moduleName = "TaipeiCity";
    private $_debug = false;

    /**
     *  Helper to transform Violation rule/Descriptition and Document Number into
     *  single string.
     */
    private function processViolation($ruleTd, $behaviorTd, $docTd)
    {
        $rules = $ruleTd->getElementsByTagName('p');
        $behaviors = $behaviorTd->getElementsByTagName('p');

        $this->debug("processViolation(), rules->length: " . $rules->length .
              "behaviors->length: " . $behaviors->length);

        $violations = "";
        for ($i = 0; $i < $behaviors->length; $i++ )
        {
            $violations = $violations .
                         trim($behaviors->item($i)->nodeValue, "。 ") .
                         "(勞基法" . trim($rules->item($i)->nodeValue, "●。 ") . ")";
        }

        $violations = $violations . "(" . trim($docTd->nodeValue) . ")";
        return $violations;
    }

    protected function getViolationRecords()
    {
        $this->debug("getViolationRecords() Start");

        // Get web content
        $content = $this->http($this->targetUrl, $this->refererUrl, array());
        $htmlDoc = new DOMDocument;
        @$htmlDoc->loadHTML($content);

        $domTables = $htmlDoc->getElementsByTagName('table');
        $violationTable = $domTables->item(1);

        // Check if violation table is updated
        if (!$this->checkUpdated($violationTable->nodeValue))
        {
            return false;
        }

        $this->dump("Save backp");
        $this->saveBackup($content, "", ".html");
        $this->dump("Get snapshot");
        $this->getSnapShot($this->targetUrl, "");

        $this->dump("Crawl!");
        // Parse violation record
        $domTrs = $violationTable->getElementsByTagName('tr');
        for ($trIndex = 1; $trIndex < $domTrs->length; $trIndex++) {
            /** Row format
             *  Table format for each violation record:
             *
             *  <TR>
             *    <TD tabindex="0">Index</TD>
             *    <TD tabindex="0">Company Name</TD>
             *    <TD tabindex="0">
             *       <P>Violation Rule 1</P>
             *       <P>Violation Rule 2</P>
             *       ...
             *    </TD>
             *    <TD tabindex="0">
             *       <P>Violation Description 1</P>
             *       <P>Violation Description 2</P>
             *       ...
             *    </TD>
             *    <TD tabindex="0">Document Number</TD>
             *    <TD tabindex="0">Date</TD>
             *  </TR>
             *
             *  From current observation, number of "Violation Rule" is as same
             *  as "Violation Description"
             */
            $violateTr = $domTrs->item($trIndex);

            $domTds = $violateTr->getElementsByTagName('td');

            $this->debug("Handle row " . $trIndex . ", companyName: " . $domTds->item(1)->nodeValue);

            $violation = new violationRecord;
            $violation->companyName = trim($domTds->item(1)->nodeValue);
            $violation->violationDate = $this->twDateTransform($domTds->item(5)->nodeValue);
            $violation->violations = $this->processViolation($domTds->item(2), $domTds->item(3), $domTds->item(4));
            $violation->dataSource = $this->targetUrl;
            $violation->dataImage = "";

            if ($this->_debug)
            {
                echo $violation->toCsv() . "\n";
            }

            $this->violationRecords[] = $violation;
        }

        return true;
    }
}

?>
