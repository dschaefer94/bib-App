<?php
namespace ppb\Library;

class Msg {
    public function __construct(bool $isError, string $message, string $ex = "") {
        echo json_encode([
            "success" => !$isError,
            "message" => $message,
            "exception" => $ex
        ], JSON_PRETTY_PRINT);
    }
}
?>
