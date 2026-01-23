create database if not exists stundenplan_db;
use stundenplan_db;

create table benutzer (
  benutzer_id int auto_increment primary key,
  passwort varchar(255) not null,
  email varchar(255) not null unique
);

create table klassen (
  klassen_id int auto_increment primary key,
  klassenname varchar(100) not null,
  ical_link varchar(255)
);

create table persoenliche_daten (
  benutzer_id int primary key,
  name varchar(100) not null,
  vorname varchar(100) not null,
  klassen_id int,
  foreign key (benutzer_id) references benutzer(benutzer_id),
  foreign key (klassen_id) references klassen(klassen_id)
);
