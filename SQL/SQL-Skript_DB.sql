CREATE DATABASE IF NOT EXISTS pbd2h24asc_stundenplan_db;
USE pbd2h24asc_stundenplan_db;

CREATE TABLE benutzer (
  benutzer_id CHAR(36) PRIMARY KEY,
  passwort VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  istadmin TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE klassen (
  klassen_id INT AUTO_INCREMENT PRIMARY KEY,
  klassenname VARCHAR(100) NOT NULL,
  ical_link VARCHAR(255)
);

CREATE TABLE persoenliche_daten (
  benutzer_id CHAR(36) PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  vorname VARCHAR(100) NOT NULL,
  klassen_id INT,
  FOREIGN KEY (benutzer_id) REFERENCES benutzer(benutzer_id),
  FOREIGN KEY (klassen_id) REFERENCES klassen(klassen_id)
);

CREATE TABLE gelesene_termine (
  benutzer_id CHAR(36),
  termin_id VARCHAR(255),
  PRIMARY KEY (benutzer_id, termin_id),
  FOREIGN KEY (benutzer_id) REFERENCES benutzer(benutzer_id)
);

INSERT INTO klassen (klassenname, ical_link) 
VALUES ('Zombieklasse', 'https://bibapp.pbd2h24asc.web.bib.de/empty.ics');

DELIMITER //

CREATE TRIGGER prevent_delete_zombie_class
BEFORE DELETE ON klassen
FOR EACH ROW
BEGIN
    IF OLD.klassen_id = 1 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Die Zombieklasse kann nicht gelöscht werden!';
    END IF;
END //

DELIMITER ;