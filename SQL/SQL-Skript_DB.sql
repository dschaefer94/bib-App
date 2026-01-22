create database if not exists pbd2h24asc_stundenplan_db;
use pbd2h24asc_stundenplan_db;

create table benutzer (
  benutzer_id char(36) primary key,
  passwort varchar(255) not null,
  email varchar(255) not null unique
);

create table klassen (
  klassen_id int auto_increment primary key,
  klassenname varchar(100) not null,
  ical_link varchar(255)
);

create table persoenliche_daten (
  benutzer_id char(36) primary key,
  name varchar(100) not null,
  vorname varchar(100) not null,
  klassen_id int,
  foreign key (benutzer_id) references benutzer(benutzer_id),
  foreign key (klassen_id) references klassen(klassen_id)
);

create table gelesene_termine (
  benutzer_id char(36),
  termin_id varchar(255),
  primary key (benutzer_id, termin_id),
  foreign key (benutzer_id) references benutzer(benutzer_id)
);
