<?php
    /*
     * Caching  A small PHP class to get data from forrst and cache it
     */
 
    class Caching {
 
        var $filePath = "";
        var $apiURI = "";
 
        function __construct($filePath, $apiURI) {
            //check if the file path and api URI are specified, if not: break out of construct.
            if (strlen($filePath) > 0 && strlen($apiURI) > 0) {
                //set the local file path and api path
                $this->filePath = $filePath;
                $this->apiURI = $apiURI;
 
                //does the file need to be updated?
                if ($this->checkForRenewal()) {
 
                    //get the data you need
                    $data = $this->getExternalInfo();
 
                    //save the data to your file
                    $this->saveFile($data);
 
                    return true;
                } else {
                    //no need to update the file
                    return true;
                }
 
            } else {
                echo "No file path and / or api URI specified.";
                return false;
            }
        }
 
        function checkForRenewal() {
            

            //set the caching time (in seconds)
            $cachetime = (3600);//1 hour
 
            //get the file time
            $filetimemod = getlastmod($this->filePath) + $cachetime;
 
            //if the renewal date is smaller than now, return true; else false (no need for update)
            if ($filetimemod < time()) {
                //return true;
            } else {
                //return false;
            }
            return true;
        }
 
        function getExternalInfo() {

            //get remote data
            $resp = wp_remote_get( $this->apiURI );

            //if http status code is equal to 200 carry on otherwise return false
            if ( 200 == $resp['response']['code'] ) {
                //get the returned data
                $body = $resp['body'];
                //detcode the json object
                $data = json_decode($body, true);                
                return $data;
            } else {
                return false;
            }

        }
 
        function saveFile($data) {

            $fp = fopen($this->filePath, 'w+')or die('cannot open file'); 
			fwrite($fp, base64_encode(serialize($data)));
			fclose($fp);

           
            
        }
 
    }
?>