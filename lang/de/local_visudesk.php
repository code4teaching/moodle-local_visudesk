<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Language strings (German).
 *
 * @package    local_visudesk
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['advancedgrading'] = 'Diese Aufgabe nutzt eine erweiterte Bewertungsmethode (zum Beispiel ein Bewertungsraster). Die Note bitte in Moodle setzen; Feedback geht weiter hier.';
$string['autoapprove'] = 'Kursanträge automatisch annehmen';
$string['autoapprove_desc'] = 'Folgt ein Antrag dem Namensschema unten und hat die Klasse schon einen Kurs, wird der Kurs sofort angelegt: verborgen, im Kursbereich des neuesten Kurses dieser Klasse (Archiv-Bereiche zählen nicht), mit der antragstellenden Person als Trainer/in. Sonst bleibt es ein normaler Antrag. Das voreingestellte Schema ist „Schuljahr Klasse Kürzel Fach“, z. B. 2026 11FI2 SebH Objektorientierte Programmierung, Kurzname 2026 11FI2 SebH OOP.';
$string['autoapprovearchive'] = 'Archiv-Bereiche';
$string['autoapprovearchive_desc'] = 'Kursbereiche, deren Pfad diesen Text enthält (Groß-/Kleinschreibung egal), zählen bei der Suche nach dem Bereich einer Klasse nicht. Leer: keine.';
$string['autoapprovedbody'] = '{$a->requester} hat einen Kurs beantragt, er wurde sofort angelegt.

Name: {$a->fullname}
Kurzname: {$a->shortname}
Kursbereich: {$a->category}

Der Kurs ist verborgen. Bitte Name und Kursbereich prüfen: {$a->url}';
$string['autoapprovedsubject'] = 'Kurs automatisch angelegt: {$a->fullname}';
$string['autoapprovefullname'] = 'Schema für den vollständigen Namen';
$string['autoapprovefullname_desc'] = 'Regulärer Ausdruck mit Begrenzern. Die Gruppe (?&lt;class&gt;…) ist die Klasse; die Kurse derselben Klasse bestimmen den Kursbereich. Eine optionale Gruppe (?&lt;key&gt;…) muss in vollständigem und Kurznamen gleich sein.';
$string['autoapproveheading'] = 'Kursanträge';
$string['autoapproveheading_desc'] = 'Lehrkräfte beantragen Kurse aus VisuDesk. Kurse können sofort angelegt und danach geprüft werden.';
$string['autoapprovenotify'] = 'Benachrichtigung über angelegte Kurse';
$string['autoapprovenotify_desc'] = 'Anmeldename der Person, die automatisch angelegte Kurse prüft. Leer: die Hauptadministration.';
$string['autoapproveshortname'] = 'Schema für den Kurznamen';
$string['autoapproveshortname_desc'] = 'Regulärer Ausdruck mit Begrenzern, mit denselben Gruppen (?&lt;class&gt;…) und gegebenenfalls (?&lt;key&gt;…) wie beim vollständigen Namen.';
$string['availabilitydisabled'] = 'Voraussetzungen sind auf dieser Website ausgeschaltet (Website-Administration → Erweiterte Funktionen → Voraussetzungen aktivieren). Ein Freigabedatum hätte deshalb keine Wirkung.';
$string['blindmarking'] = 'Diese Aufgabe nutzt anonyme Abgaben. Bitte in Moodle bewerten.';
$string['connectbutton'] = 'Mit VisuDesk verbinden';
$string['connectinvalid'] = 'Dieser Link zum Verbinden einer App ist ungültig. Bitte in der App noch einmal auf „Mit Moodle verbinden“ klicken.';
$string['connectnoservice'] = 'Der Webservice {$a} steht zum Verbinden von Apps nicht zur Verfügung. Bitte wende dich an die Moodle-Administration.';
$string['connectnotallowed'] = 'Für den Webservice {$a} kannst du noch keinen Schlüssel bekommen. Bitte wende dich an die Moodle-Administration.';
$string['connectquestion'] = '<p>VisuDesk auf diesem Computer möchte mit deinem Moodle-Konto <strong>{$a->name}</strong> arbeiten (Webservice „{$a->service}“).</p><p>VisuDesk kann dann in deinen Kursen das tun, was du in Moodle tun kannst: Dateien hochladen, Tests anlegen und Abgaben ansehen. Der Schlüssel geht nur an VisuDesk auf diesem Computer und wird dort im Schlüsselbund gespeichert.</p><p>Entfernen kannst du ihn jederzeit unter Einstellungen → Sicherheitsschlüssel.</p>';
$string['connecttitle'] = 'VisuDesk verbinden';
$string['errornamepattern'] = 'Bitte einen gültigen regulären Ausdruck mit Begrenzern und einer Gruppe „class“ eingeben, zum Beispiel /^(?<class>\\S+) .+$/u.';
$string['feedbackcommentsdisabled'] = 'Feedback-Kommentare sind in dieser Aufgabe ausgeschaltet.';
$string['feedbackfilesdisabled'] = 'In dieser Aufgabe sind Feedbackdateien ausgeschaltet. In den Einstellungen der Aufgabe unter Feedback-Typen „Feedbackdateien“ einschalten.';
$string['gradenotinscale'] = 'Diese Note gehört nicht zur Skala der Aufgabe.';
$string['gradetoohigh'] = 'Die Bewertung ist höher als das Maximum von {$a} Punkten.';
$string['gradingdisabled'] = 'Diese Bewertung lässt sich hier nicht ändern: Sie ist in der Bewertungsübersicht gesperrt oder überschrieben, oder ihr Bewertungsstatus liegt außerhalb dessen, was du ändern darfst.';
$string['gradingstarted'] = 'In dieser Aufgabe hat schon jemand eine Bewertung, deshalb lässt sich die Bewertungsart nicht mehr ändern.';
$string['messageprovider:courseautoapproved'] = 'Kurs automatisch aus einem Antrag angelegt';
$string['nogradetype'] = 'Diese Aufgabe wird nicht bewertet.';
$string['nomarkingworkflow'] = 'Diese Aufgabe hat keinen Bewertungs-Workflow. Freigeben ist nicht nötig, Bewertungen sind sofort sichtbar.';
$string['notparticipant'] = 'Diese Person nimmt nicht an der Aufgabe teil, oder du darfst sie nicht bewerten (Gruppen oder Zuteilung der Bewertenden).';
$string['planheading'] = 'Unterrichtsplanung';
$string['planheading_desc'] = 'VisuDesk speichert hier die eigene Planung jeder Lehrkraft (Thema, Plan, wie weit die Klasse gekommen ist), damit sie auf dem Mac und dem iPad gleich ist. Nur die Lehrkraft selbst liest und ändert sie; auch Administrator:innen sehen sie in VisuDesk nicht.';
$string['planretentiondays'] = 'Planung aufbewahren (Tage)';
$string['planretentiondays_desc'] = 'Eine geplante Stunde wird so viele Tage nach ihrer letzten Änderung gelöscht. 0 bewahrt sie auf, bis das Konto gelöscht wird.';
$string['privacy:metadata:plan'] = 'Die eigene Unterrichtsplanung der Lehrkraft, ein Eintrag je Stunde. Nur die Lehrkraft kann sie lesen und ändern.';
$string['privacy:metadata:plan:lessonkey'] = 'Die Stunde: Datum, Beginn, Klassen und Fach';
$string['privacy:metadata:plan:plandata'] = 'Was die Lehrkraft eingetragen hat: Thema, Plan, wie weit die Klasse gekommen ist, Verweise auf Kursmaterial, Hausaufgaben';
$string['privacy:metadata:plan:timemodified'] = 'Wann die Stunde zuletzt geändert wurde';
$string['privacy:metadata:plan:userid'] = 'Die Lehrkraft, der die Planung gehört';
$string['privacy:metadata:vg:data'] = 'Der verschlüsselte Inhalt';
$string['privacy:metadata:vg:device'] = 'Zufällige Kennung des Geräts, das die Änderung geschickt hat';
$string['privacy:metadata:vg:timecreated'] = 'Wann sie angekommen ist';
$string['privacy:metadata:vg:userid'] = 'Die Lehrkraft, der die Daten gehören';
$string['privacy:metadata:vgentry'] = 'VisuGrade-Abgleich: Änderungen am Notenbuch der Lehrkraft, auf ihren Geräten verschlüsselt. Moodle kann sie nicht lesen.';
$string['privacy:metadata:vgsnap'] = 'VisuGrade-Abgleich: Stand des ganzen Notenbuchs der Lehrkraft, auf ihren Geräten verschlüsselt.';
$string['privacy:path:plan'] = 'Unterrichtsplanung';
$string['privacy:path:vg'] = 'VisuGrade-Abgleich (verschlüsselt)';
$string['privacy:vg:encrypted'] = 'Auf den Geräten der Lehrkraft verschlüsselt; lesen kann es nur VisuGrade mit ihrem Schlüssel.';
$string['scalenotfound'] = 'Auf dieser Website und in diesem Kurs gibt es keine Skala mit dem Namen „{$a}“.';
$string['task_cleanupplan'] = 'Alte Unterrichtsplanung löschen';
$string['task_cleanupvg'] = 'Alte Änderungen des VisuGrade-Abgleichs löschen';
$string['vgfull'] = 'Die Abgleich-Daten von VisuGrade für dieses Konto haben die Größengrenze erreicht.';
$string['vgnosnapshot'] = 'Änderungen lassen sich erst löschen, wenn ein Stand sie enthält.';
$string['vgpairgone'] = 'Diese Kopplungsanfrage ist abgelaufen. Bitte auf dem neuen Gerät neu beginnen.';
$string['workflowstatenotallowed'] = 'Diesen Bewertungsstatus darfst du hier nicht setzen ({$a}).';
