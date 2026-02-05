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
    try {
        $pdo->exec("TRUNCATE TABLE `{$alter_stundenplan}`");
        $pdo->exec("RENAME TABLE `{$alter_stundenplan}` TO `{$wartezimmer}`");
        $pdo->exec("RENAME TABLE `{$neuer_stundenplan}` TO `{$alter_stundenplan}`");
        $pdo->exec("RENAME TABLE `{$wartezimmer}` TO `{$neuer_stundenplan}`");
    } catch (\PDOException $e) {
        throw new \Exception("Tabellen-Cleanup fehlgeschlagen: " . $e->getMessage());
    }
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
                $params[":summary$counter"] = mb_substr($event['summary'], 0, 255);
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

        $query = "INSERT INTO `{$aenderungen}` (termin_id, label, summary_alt, start_alt, end_alt, location_alt)
        SELECT DISTINCT 
        alt.termin_id, 'gelöscht',
        alt.summary, alt.start, alt.end, alt.location
        FROM `{$alter_stundenplan}` AS alt
        LEFT JOIN `{$neuer_stundenplan}` AS neu
        ON neu.termin_id = alt.termin_id
        WHERE neu.termin_id IS NULL
        ON DUPLICATE KEY UPDATE label= VALUES(label);
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        $query = "DELETE vorher
        FROM `{$aenderungen}` AS vorher
        JOIN `{$neuer_stundenplan}` AS neu
        ON neu.termin_id = vorher.termin_id
        WHERE vorher.label = 'gelöscht'
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        $query = "INSERT INTO `{$aenderungen}` (termin_id, label)
        SELECT neu.termin_id, 'neu'
        FROM `{$neuer_stundenplan}` AS neu
        LEFT JOIN `{$alter_stundenplan}` AS alt
        ON alt.termin_id = neu.termin_id
        WHERE alt.termin_id IS NULL
        ON DUPLICATE KEY UPDATE label= VALUES(label);
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        $query = "DELETE vorher
        FROM `{$aenderungen}` AS vorher
        LEFT JOIN `{$neuer_stundenplan}` AS neu
        ON neu.termin_id = vorher.termin_id
        WHERE vorher.label = 'neu'
        AND neu.termin_id IS NULL;
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        $query = "INSERT INTO `{$aenderungen}` (termin_id, label, summary_alt, start_alt, end_alt, location_alt)
        SELECT neu.termin_id, 'geändert', alt.summary, alt.start, alt.end, alt.location
        FROM `{$neuer_stundenplan}` AS neu
        JOIN `{$alter_stundenplan}` AS alt ON neu.termin_id = alt.termin_id
            WHERE NOT (
            neu.summary   <=> alt.summary AND
            neu.start     <=> alt.start   AND
            neu.end       <=> alt.end     AND
            neu.location  <=> alt.location
            )
            ON DUPLICATE KEY
            UPDATE label= VALUES(label),
            summary_alt= VALUES(summary_alt),
            start_alt= VALUES(start_alt),
            end_alt= VALUES(end_alt),
            location_alt= VALUES(location_alt);
            ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        $query = "DELETE vorher
        FROM `{$aenderungen}` AS vorher
        JOIN `{$neuer_stundenplan}` AS neu
        ON neu.termin_id = vorher.termin_id
        WHERE vorher.label = 'geändert'
        AND vorher.summary_alt  <=> neu.summary
        AND vorher.start_alt    <=> neu.start
        AND vorher.end_alt      <=> neu.end
        AND vorher.location_alt <=> neu.location;
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        $pdo->commit();
    } catch (\PDOException $e) {
        $pdo->rollBack();
        echo "Fehler bei der Datenbankoperation: " . $e->getMessage() . "\n";
    }

}
