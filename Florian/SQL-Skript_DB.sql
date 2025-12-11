-- Komplettes Skript: Datenbank + Tabellen + 10 Klassen + 50 Benutzer mit bib.de E-Mails + persönliche Daten

CREATE DATABASE IF NOT EXISTS stundenplan_db;
USE stundenplan_db;

CREATE TABLE benutzer (
  benutzer_id INT AUTO_INCREMENT PRIMARY KEY,
  passwort VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE klassen (
  klassen_id INT AUTO_INCREMENT PRIMARY KEY,
  klassenname VARCHAR(100) NOT NULL,
  ical_link VARCHAR(255),
  json_link VARCHAR(255)
);

CREATE TABLE persoenliche_daten (
  benutzer_id INT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  vorname VARCHAR(100) NOT NULL,
  klassen_id INT,
  FOREIGN KEY (benutzer_id) REFERENCES benutzer(benutzer_id),
  FOREIGN KEY (klassen_id) REFERENCES klassen(klassen_id)
);

-- 10 Klassen einfügen
INSERT INTO klassen (klassenname, ical_link, json_link) VALUES
  ('FI23A', 'https://schule.de/ical/FI23A.ics', 'https://api.schule.de/stundenplan/FI23A.json'),
  ('FI23B', 'https://schule.de/ical/FI23B.ics', 'https://api.schule.de/stundenplan/FI23B.json'),
  ('AI22A', 'https://schule.de/ical/AI22A.ics', 'https://api.schule.de/stundenplan/AI22A.json'),
  ('AI22B', 'https://schule.de/ical/AI22B.ics', 'https://api.schule.de/stundenplan/AI22B.json'),
  ('SE21A', 'https://schule.de/ical/SE21A.ics', 'https://api.schule.de/stundenplan/SE21A.json'),
  ('SE21B', 'https://schule.de/ical/SE21B.ics', 'https://api.schule.de/stundenplan/SE21B.json'),
  ('DB20A', 'https://schule.de/ical/DB20A.ics', 'https://api.schule.de/stundenplan/DB20A.json'),
  ('DB20B', 'https://schule.de/ical/DB20B.ics', 'https://api.schule.de/stundenplan/DB20B.json'),
  ('WD19A', 'https://schule.de/ical/WD19A.ics', 'https://api.schule.de/stundenplan/WD19A.json'),
  ('WD19B', 'https://schule.de/ical/WD19B.ics', 'https://api.schule.de/stundenplan/WD19B.json');

-- 50 Benutzer mit vorname.name@bib.de E-Mails
INSERT INTO benutzer (passwort, email) VALUES
  ('pw1', 'anna.müller@bib.de'), ('pw2', 'ben.schmidt@bib.de'), ('pw3', 'clara.schneider@bib.de'),
  ('pw4', 'david.fischer@bib.de'), ('pw5', 'eva.weber@bib.de'), ('pw6', 'florian.meyer@bib.de'),
  ('pw7', 'gina.wagner@bib.de'), ('pw8', 'hans.becker@bib.de'), ('pw9', 'iris.hoffmann@bib.de'),
  ('pw10', 'jan.schulz@bib.de'), ('pw11', 'klara.koch@bib.de'), ('pw12', 'lukas.richter@bib.de'),
  ('pw13', 'mara.klein@bib.de'), ('pw14', 'nico.wolf@bib.de'), ('pw15', 'olga.schröder@bib.de'),
  ('pw16', 'paul.neumann@bib.de'), ('pw17', 'quirin.schwarz@bib.de'), ('pw18', 'rita.zimmermann@bib.de'),
  ('pw19', 'sven.hartmann@bib.de'), ('pw20', 'tina.krüger@bib.de'), ('pw21', 'uwe.lang@bib.de'),
  ('pw22', 'vera.simon@bib.de'), ('pw23', 'walt.graf@bib.de'), ('pw24', 'xenia.jung@bib.de'),
  ('pw25', 'yara.martin@bib.de'), ('pw26', 'zeno.fuchs@bib.de'), ('pw27', 'alex.bauer@bib.de'),
  ('pw28', 'birgit.lehmann@bib.de'), ('pw29', 'colin.schmitt@bib.de'), ('pw30', 'diana.krause@bib.de'),
  ('pw31', 'erik.meier@bib.de'), ('pw32', 'fiona.schulze@bib.de'), ('pw33', 'gerd.horn@bib.de'),
  ('pw34', 'hanna.peters@bib.de'), ('pw35', 'igor.lang@bib.de'), ('pw36', 'julia.brandt@bib.de'),
  ('pw37', 'kevin.friedrich@bib.de'), ('pw38', 'lea.ott@bib.de'), ('pw39', 'miriam.kraus@bib.de'),
  ('pw40', 'nils.walter@bib.de'), ('pw41', 'olaf.engel@bib.de'), ('pw42', 'petra.schreiber@bib.de'),
  ('pw43', 'ralf.lorenz@bib.de'), ('pw44', 'sina.götz@bib.de'), ('pw45', 'tom.roth@bib.de'),
  ('pw46', 'ulla.seidel@bib.de'), ('pw47', 'viktor.winkler@bib.de'), ('pw48', 'wiebke.bauer@bib.de'),
  ('pw49', 'xaver.fischer@bib.de'), ('pw50', 'yvonne.keller@bib.de');

-- 50 persönliche Daten, verteilt auf die 10 Klassen
INSERT INTO persoenliche_daten (benutzer_id, name, vorname, klassen_id) VALUES
  (1, 'Müller', 'Anna', 1), (2, 'Schmidt','Ben',   1), (3, 'Schneider','Clara', 2),
  (4, 'Fischer','David', 2), (5, 'Weber',  'Eva',   3), (6, 'Meyer',  'Florian',3),
  (7, 'Wagner', 'Gina',  4), (8, 'Becker','Hans',  4), (9, 'Hoffmann','Iris',  5),
  (10,'Schulz', 'Jan',   5), (11,'Koch',  'Klara', 6), (12,'Richter','Lukas', 6),
  (13,'Klein',  'Mara',  7), (14,'Wolf',  'Nico',  7), (15,'Schröder','Olga',  8),
  (16,'Neumann','Paul',  8), (17,'Schwarz','Quirin',9), (18,'Zimmermann','Rita',9),
  (19,'Hartmann','Sven', 10), (20,'Krüger','Tina',  10),
  (21,'Lang',   'Uwe',   1), (22,'Simon', 'Vera',  2), (23,'Graf',  'Walt',  3),
  (24,'Jung',   'Xenia', 4), (25,'Martin','Yara',  5), (26,'Fuchs', 'Zeno',  6),
  (27,'Bauer',  'Alex',  7), (28,'Lehmann','Birgit',8), (29,'Schmitt','Colin', 9),
  (30,'Krause', 'Diana', 10), (31,'Meier', 'Erik',  1), (32,'Schulze','Fiona', 2),
  (33,'Horn',   'Gerd',  3), (34,'Peters','Hanna', 4), (35,'Lang',  'Igor',  5),
  (36,'Brandt', 'Julia', 6), (37,'Friedrich','Kevin',7), (38,'Ott',  'Lea',   8),
  (39,'Kraus',  'Miriam',9), (40,'Walter','Nils',  10), (41,'Engel', 'Olaf',  1),
  (42,'Schreiber','Petra',2), (43,'Lorenz','Ralf',  3), (44,'Götz',  'Sina',  4),
  (45,'Roth',   'Tom',   5), (46,'Seidel','Ulla',  6), (47,'Winkler','Viktor',7),
  (48,'Bauer',  'Wiebke',8), (49,'Fischer','Xaver', 9), (50,'Keller','Yvonne',10);
