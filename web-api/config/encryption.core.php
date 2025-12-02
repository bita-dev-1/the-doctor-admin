<?php

    function customEncryption($cleartext){
  
        $ciphering = "AES-128-CTR";
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;
        $encryption_iv = 'CODEX23X011995DZ';
        $encryption_key = "DadiKermiche";
        $ciphertext = openssl_encrypt($cleartext, $ciphering, $encryption_key, $options, $encryption_iv);

        return $ciphertext;
    }

    function customDecrypt($ciphertext){

        $ciphering = "AES-128-CTR";
        $options = 0;
        $decryption_iv = 'CODEX23X011995DZ';
        $decryption_key = "DadiKermiche";
        $clearText = openssl_decrypt ($ciphertext, $ciphering, $decryption_key, $options, $decryption_iv);

        return $clearText;
    }
?>