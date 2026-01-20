<?php

namespace SDP\Updater;
/**
 * 
 * Daniel
 * bereitet Tabellen für neue Vergleichsoperation vor
 * @param mixed $pdo PDO-Datenbankverbindungsobjekt
 * @param mixed $alter_stundenplan für alle Termine des letzten Updateintervalls in der jeweiligen Klassentabelle
 * @param mixed $neuer_stundenplan für alle Termine des aktuellen Updateintervalls in der jeweiligen Klassentabelle
 * @param mixed $wartezimmer Platzhaltername bei der Transformation der aktuellen Termine in die alten Termine
 * @return void
 */
function tabellenAufraeumen($pdo, $alter_stundenplan, $neuer_stundenplan, $wartezimmer)
{
    $query = "TRUNCATE TABLE `{$alter_stundenplan}`;";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $query = "RENAME TABLE `{$alter_stundenplan}` TO `{$wartezimmer}`;";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $query = "RENAME TABLE `{$neuer_stundenplan}` TO `{$alter_stundenplan}`;";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $query = "RENAME TABLE `{$wartezimmer}` TO `{$neuer_stundenplan}`;";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
}
/**
 * Daniel
 * fügt neue Termine in die Tabelle neuer_stundenplan ein und wertet Änderungen aus
 * unterteilt in Chunks, um die Anzahl der Inserts pro Statement zu begrenzen
 * labelt Änderungen in der Tabelle aenderungen (neu, gelöscht, geändert)
 * @param mixed $pdo PDO-Datenbankverbindungsobjekt
 * @param mixed $alter_stundenplan für alle Termine des letzten Updateintervalls in der jeweiligen Klassentabelle
 * @param mixed $neuer_stundenplan für alle Termine des aktuellen Updateintervalls in der jeweiligen Klassentabelle
 * @param mixed $name jeweiliger Klassenname
 * @param mixed $events Array mit bereits geparseten Termindaten aus der ics-Datei
 * @return void
 */
function dbMagic($pdo, $alter_stundenplan, $neuer_stundenplan, $name, $events)
{
    $pdo->beginTransaction();
    $chunkSize = 100;
    $chunks = array_chunk($events, $chunkSize, true);

    try {
        foreach ($chunks as $chunk) {
            $valueParts = [];
            $params = [];
            $counter = 1;

            foreach ($chunk as $uid => $event) {
                $valueParts[] = "(:termin_id$counter, :summary$counter, :start$counter, :end$counter, :location$counter)";
                $params[":termin_id$counter"] = $uid;
                $params[":summary$counter"] = $event['summary'];
                $params[":start$counter"] = $event['start'];
                $params[":end$counter"] = $event['end'];
                $params[":location$counter"] = $event['location'];
                $counter++;
            }
            $query = "INSERT INTO `{$neuer_stundenplan}` (termin_id, summary, start, end, location)
            VALUES " . implode(", ", $valueParts);
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
        }
        $aenderungen = "{$name}_aenderungen";
        $veraenderte_termine = "{$name}_veraenderte_termine";

        $query = "INSERT INTO `{$aenderungen}` (termin_id, label)
        SELECT DISTINCT alt.termin_id, 'gelöscht'
        FROM `{$alter_stundenplan}` AS alt
        WHERE NOT EXISTS (
            SELECT termin_id
            FROM `{$neuer_stundenplan}` AS neu
            WHERE neu.termin_id = alt.termin_id
            )
        ON DUPLICATE KEY UPDATE label = VALUES(label);
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        //hier muss noch wie bei geändert die alten Termindaten in die aenderungen-Tabelle rein

        $query = "INSERT INTO `{$aenderungen}` (termin_id, label)
        SELECT neu.termin_id, 'neu'
        FROM `{$neuer_stundenplan}` AS neu
        LEFT JOIN `{$alter_stundenplan}` AS alt
        ON alt.termin_id = neu.termin_id
        WHERE alt.termin_id IS NULL
        ON DUPLICATE KEY UPDATE label = VALUES(label);
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        $query = "INSERT INTO `{$aenderungen}` (termin_id, label)
        SELECT neu.termin_id, 'geändert'
        FROM `{$neuer_stundenplan}` AS neu
        JOIN `{$alter_stundenplan}` AS alt ON neu.termin_id = alt.termin_id
            WHERE NOT (
            neu.summary   <=> alt.summary AND
            neu.start     <=> alt.start   AND
            neu.end       <=> alt.end     AND
            neu.location  <=> alt.location
            )
        ON DUPLICATE KEY UPDATE label = VALUES(label);
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        //das kommt in die bereits gealterte Tabelle aenderungen in einem GO mit rein
        $query = "INSERT INTO `{$veraenderte_termine}` (termin_id, summary, start, end, location)
        SELECT alt.termin_id, alt.summary, alt.start, alt.end, alt.location
        FROM `{$alter_stundenplan}` AS alt
        WHERE alt.termin_id IN (
            SELECT termin_id
            FROM `{$aenderungen}`
                WHERE label = 'geändert'
            )
        ON DUPLICATE KEY UPDATE
        summary = VALUES(summary),
        start   = VALUES(start),
        end     = VALUES(end),
        location= VALUES(location);
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $pdo->commit();

    } catch (\PDOException $e) {
        $pdo->rollBack();
        echo "Fehler bei der Datenbankoperation: " . $e->getMessage() . "\n";
    }
    echo "Datenbankoperationen erfolgreich!\n";
}