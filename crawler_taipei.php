<?php

include_once 'crawlerBase.php';

class crawlerTaipei extends crawlerBase
{
    public $targetUrl = "http://www.bola.taipei.gov.tw/ct.asp?xItem=41223990&ctNode=62846&mp=116003";
    public $refererUrl = "http://www.bola.taipei.gov.tw/ct.asp?xItem=41223990&ctNode=62846&mp=116003";
    public $targetId = "01";
    public $targetName = "台北市";
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

        $violationDoc = trim($docTd->nodeValue);
        $violationLaw = "勞動基準法";
        $violationDscr = "";
        $violationSummary = "";
        for ($i = 0; $i < $behaviors->length; $i++ )
        {
            $law = trim($rules->item($i)->nodeValue, "●。 ");
            $dscr = trim($behaviors->item($i)->nodeValue, "●。 ");

            $violationLaw = $violationLaw . $law .
                            ($i == ($behaviors->length - 1) ? "" : "、");
            $violationDscr = $violationDscr . $dscr .
                             ($i == ($behaviors->length - 1) ? "" : "、");
            $violationSummary = $violationSummary . $dscr .
                                "(勞基法" . $law . ")" .
                                ($i == ($behaviors->length - 1) ? "" : "、");
        }

        $violationSummary = $violationSummary . "(" . $violationDoc . ")";
        return array($violationLaw, $violationDscr,
                     $violationDoc, $violationSummary);
    }

    protected function getViolationRecords()
    {
        $this->debug("getViolationRecords() Start");

        // Get web content
        $content = $this->http($this->targetUrl, $this->refererUrl, false, array());
        $htmlDoc = new DOMDocument;
        @$htmlDoc->loadHTML($content);

        $domTables = $htmlDoc->getElementsByTagName('table');
        $violationTable = $domTables->item(1);

        // Check if violation table is updated
        if (!$this->checkUpdated($violationTable->nodeValue))
        {
            return false;
        }

        $this->dump("Saving backup");
        $this->saveBackup($content, "", ".html");
        $this->dump("Getting snapshot");
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
            $violation->violationDateTw = trim($domTds->item(5)->nodeValue);
            $violation->violationDateCe = $this->twDateTransform($violation->violationDateTw);
            list ($violation->violationLaw, $violation->violationDscr,
                  $violation->violationDoc, $violation->violationSummary)
                  = $this->processViolation($domTds->item(2), $domTds->item(3), $domTds->item(4));
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
