<?php

/**
 * Uloz.to host file for Synology Download Station, requires backend service to work.
 */

class SynologyUloztoFree
{
    private $Url;
    private $BackendBaseUrl;
    private $Parts;
    private $HostInfo;

    public function __construct($Url, $Username, $Password, $HostInfo)
    {
        $this->Url = $Url;
        $this->BackendBaseUrl = $Username;
        $this->Parts = $Password;
        #$this->BackendBaseUrl = "http://127.0.0.1:8765";
        #$this->Parts = "7";
        $this->HostInfo = $HostInfo;
        #ini_set('display_errors', 0);
        ini_set('display_errors', 1);
        ini_set('log_errors', 1);
        ini_set('error_log', '/tmp/ulozto_error.log');
        ini_set('error_reporting', E_ALL);
    }

    public function getDownloadInfo()
    {

        $ret = $this->Verify(false);
        if ($ret == false)
            return array(DOWNLOAD_ERROR => LOGIN_FAIL);

        $ret = $this->getFileLink();
        if ($ret == false) {
            return array(DOWNLOAD_ERROR => ERR_TRY_IT_LATER);
        } elseif (array_key_exists(DOWNLOAD_COUNT, $ret)) {
            return $ret;
        }

        error_log("Returning: " . print_r($ret, true));
        return $ret;
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function Verify($ClearCookie)
    {
        return USER_IS_FREE;
    }

    private function getFileLink()
    {
        $initiateUrl = $this->getInitiatedUrl();

        error_log("Initiate url: " . $initiateUrl);
        $curl = curl_init($initiateUrl);
        $headers = array();
        curl_setopt($curl, CURLOPT_USERAGENT, DOWNLOAD_STATION_USER_AGENT);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$headers) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                    return $len;

                $headers[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_errno($curl) . "\n" . curl_error($curl);
        if ($curlError != false) {
            error_log($curlError);
        }
        //error_log("Response: " . $response);
        curl_close($curl);
        if ($response == false) {
            error_log("Error initiating download: " . $response);
            return false;
        }

        if ($httpCode == 429) {
            // Wait 60 seconds then query this host plugin again
            // Passing download url is required
            return array(DOWNLOAD_COUNT => 60, DOWNLOAD_URL => $this->Url, INFO_NAME => trim($this->HostInfo[INFO_NAME]), DOWNLOAD_ISQUERYAGAIN => 1);
        }

        if ($httpCode == 200) {
            $downloadUrl = $this->getDownloadUrl();
            error_log("Download url: " . $downloadUrl);
            return array(DOWNLOAD_URL => $downloadUrl);
        }

        error_log("Unspecified error, http code: " . $httpCode . "\n");
        return false;
    }

    public function getInitiatedUrl()
    {
        return $this->constructBaseBackendUrl("/initiate") . "&parts=" . urldecode($this->Parts);
    }

    public function getDownloadUrl()
    {
        return $this->constructBaseBackendUrl("/download");
    }

    private function constructBaseBackendUrl($ApiUrl)
    {
        return $this->BackendBaseUrl . $ApiUrl . "?url=" . urlencode($this->Url);
    }

}

?>
