<?php

namespace ppb\Library;

class Msg
{
    /**
     * Sends either an error or a success message as JSON Object. The error message contains 
     * additional informations about the error. If no individual msg is given as parameter, 
     * the default msg will be send
     *
     * @param boolean $isError is the msg an error msg?
     * @param string $msg optional error message
     * @param string $ex optional debug message
     */
    public function __construct($isError = false, $msg = '', $ex = '')
    {
        if ($isError) {
            $striped = strip_tags($ex);
            echo json_encode(array(
                "isError" => true,
                "msg" => is_null($msg) ? 'Ihre Anfrage konnte nicht verarbeitet werden' : $msg,
                "ex" => $striped
            ));
        } else {
            echo json_encode(array("isError" => false));
        } 
        die;
    }
}