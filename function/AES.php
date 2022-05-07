<?php

class AES {

    private $hex_iv = "G36voj5UD123456voj5UDeofG36voj5U";

    private $key = 'yrzs';

    function __construct($key) {
        $this->key = $key;
        $this->key = hash('sha256', $this->key, true);
    }
    public function encrypt($input)
    {
        $data = openssl_encrypt($input, 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA, $this->hexToStr($this->hex_iv));
        $data = base64_encode($data);
        return $data;
    }

    public function decrypt($input)
    {
        $decrypted = openssl_decrypt(base64_decode($input), 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA, $this->hexToStr($this->hex_iv));
        return $decrypted;
    }
    private function addpadding($string, $blocksize = 16) {

        $len = strlen($string);

        $pad = $blocksize - ($len % $blocksize);

        $string .= str_repeat(chr($pad), $pad);

        return $string;

    }

    private function strippadding($string) {

        $slast = ord(substr($string, -1));

        $slastc = chr($slast);

        $pcheck = substr($string, -$slast);

        if (preg_match("/$slastc{" . $slast . "}/", $string)) {

            $string = substr($string, 0, strlen($string) - $slast);

            return $string;

        } else {

            return false;

        }

    }

    function hexToStr($hex)
    {

        $string='';

        for ($i=0; $i < strlen($hex)-1; $i+=2)

        {

            $string .= chr(hexdec($hex[$i].$hex[$i+1]));

        }

        return $string;
    }
}
function AESDecode($data){
    $keyStr = '12345j5UDeof';
    $aes = new AES($keyStr);
    $chgstr = $aes->decrypt($data);
    return $chgstr;
}
function AESEncode($data){
    $keyStr = '12345j5UDeof';
    $aes = new AES($keyStr);
    $chgstr = $aes->encrypt($data);
    return $chgstr;
}