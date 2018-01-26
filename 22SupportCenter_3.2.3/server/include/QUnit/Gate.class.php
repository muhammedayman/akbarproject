<?php
class QUnit_Gate {

    //------------------------------------------------------------------------

    function currentVersion() {
        return '01';
    }

    //------------------------------------------------------------------------

    function generateKey($domain, $length = 32) {
        $key = md5(strtolower($domain));
        while (strlen($key) < $length) {
            $key .= md5($key);
        }
        return substr($key, 0, $length);
    }

    //------------------------------------------------------------------------

    function getDomain() {
        if (strlen(strtolower(getenv('HTTP_HOST')))) {
            return strtolower(getenv('HTTP_HOST'));
        } else {
            return 'commandline';
        }
    }

    //------------------------------------------------------------------------
    
    function fnxor($key, $string) {
        $res = '';
        for($i = 0; $i < (strlen($string) / 4); $i++) {
            $res .= str_pad(base_convert((int)(base_convert(substr($key, $i*4, 4), 16, 10)) ^ 
            (int)(base_convert(substr($string, $i*4, 4), 16, 10)), 10, 16), 4, '0', STR_PAD_LEFT);                        
        }
        return $res;
    }

    //------------------------------------------------------------------------

    function encodeDate($date) {
        return base_convert(strtotime($date), 10, 16);
    }

    //------------------------------------------------------------------------
    
    function decodeDate($date) {
        return date('Y-m-d H:i:s', base_convert($date, 16, 10));
    }

    //------------------------------------------------------------------------

    function transformProductID($productId) {
        $productId .= str_repeat('0', strlen($productId) - (((int)(strlen($productId)/4)) * 4));
        return $productId;
    }

    //------------------------------------------------------------------------
    
    //productId has to be hexadecimal string
    function encodeLicense($validFrom, $validTo, $domain, $productId) {
        if (!strlen(trim($validFrom))) {
            $validFrom = date('Y-m-d H:i:s');
        }
        
        if (!strlen(trim($validTo))) {
            $validTo = '2037-12-31 12:00:00';
        }
        
        $str = QUnit_Gate::currentVersion() . 
        QUnit_Gate::encodeDate($validFrom) . 
        QUnit_Gate::encodeDate($validTo) . 
        QUnit_Gate::transformProductID($productId);
        
        return QUnit_Gate::fnxor(QUnit_Gate::generateKey($domain, strlen($str)), $str); 
    }

    //------------------------------------------------------------------------
    
    function decodeLicense($license, $domain) {
        $ret = array();
        $strRet = QUnit_Gate::fnxor(QUnit_Gate::generateKey($domain, strlen($license)), $license);
        
        $ret['licenseVersion'] = substr($strRet, 0, 2);
        
        switch ($ret['licenseVersion']) {
            case '01':
                $ret['validFrom'] = QUnit_Gate::decodeDate(substr($strRet, 2, 8));
                $ret['validTo'] = QUnit_Gate::decodeDate(substr($strRet, 10, 8));
                $ret['custProduktId'] = substr($strRet, 18);
                break;
            default:
                break;
        }
        
        return $ret;
    }
}

?>