<?php
/**
 * Daniel
 * Hilfsskript zum Klasse-Hinzufügen
 * postet Klassenname und ical-Link in die writeClass-Controllermethode
 * Tut so, als wäre sie eine Admin-Seite im Frontend, bis Adminaccount hinzugefügt wird
 */
define('API', 'restAPI.php');
$url = "http://localhost/bibapp_xampp/" . API;

//D-Klasse
//$klassenname = 'pbd2h24a';
//$ical_link = 'https://intranet.bib.de/ical/d819a07653892b46b6e4d2765246b7ab';

//S-Klasse
// $klassenname = 'pbs2h24a';
// $ical_link = 'https://intranet.bib.de/ical/fad4c7872fcb7c42517c495fd83d99d6';

//M(D)-Klasse
// $klassenname = 'pbm2h24d';
// $ical_link = 'https://intranet.bib.de/ical/d9c4b07d09be9d16214a9c1de6601139';

$klassenname = 'dozreb';
$ical_link = 'https://intranet.bib.de/ical/97ec74b60c62a26c6835edf17d9241ae';

$params = json_encode(array(
  "klassenname" => $klassenname,
  'ical_link' => $ical_link
));
$defaults = array(
  CURLOPT_URL => $url . '/class',
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => $params
);

$ch = curl_init();
curl_setopt_array($ch, ($defaults));
curl_exec($ch);
if (curl_error($ch)) {
  print(curl_error($ch));
}
