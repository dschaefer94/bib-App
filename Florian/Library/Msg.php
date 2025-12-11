<?php

namespace ppb\Library;

/**
 * Msg Class
 *
 * Eine Hilfsklasse, die eine formatierte JSON-Antwort ausgibt und danach
 * die Skriptausführung sofort beendet.
 * Nützlich für einfaches, zentralisiertes Fehler-Handling in Models oder Controllern.
 *
 * WICHTIG: Die Instanziierung dieser Klasse führt immer zu einem `die;`.
 */
class Msg
{
    /**
     * Gibt eine Erfolgs- oder Fehlermeldung als JSON-Objekt aus und beendet das Skript.
     *
     * Bei einem Fehler (`$isError = true`) wird ein Objekt mit den Details zum Fehler ausgegeben.
     * Bei Erfolg wird ein einfaches Objekt zurückgegeben, das den Erfolg signalisiert.
     *
     * @param bool $isError Gibt an, ob es sich um eine Fehlermeldung handelt.
     * @param string $msg Eine optionale, benutzerdefinierte Nachricht.
     * @param mixed $ex Optionale Debug-Informationen, oft ein Exception-Objekt. HTML-Tags werden entfernt.
     */
    public function __construct($isError = false, $msg = '', $ex = '')
    {
        if ($isError) {
            $striped = strip_tags((string)$ex);
            echo json_encode(array(
                "isError" => true,
                "msg" => is_null($msg) || $msg === '' ? 'Ihre Anfrage konnte nicht verarbeitet werden' : $msg,
                "ex" => $striped
            ));
        } else {
            echo json_encode(array("isError" => false));
        } 
        die;
    }
}