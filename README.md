# Umfragen

Erstellt und verwaltet Umfragen in REDAXO 5, bei Bedarf mit E-Mailbestätigung.

![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/poll/assets/poll.png)

## Installation

Installationsvoraussetzungen: YForm >4.0, REDAXO ^5.16 

* Ins Backend einloggen und mit dem Installer installieren

## Funktionsweise

Das Poll-AddOn ermöglicht die Erstellung und Verwaltung von Umfragen in REDAXO. Es bietet verschiedene Typen von Umfragen:

* **Direkt**: Benutzer können direkt abstimmen, ohne weitere Überprüfung
* **Hash**: Jeder Benutzer kann nur einmal abstimmen (Browser-Cookie-basiert)
* **E-Mail**: Benutzer müssen ihre E-Mail-Adresse angeben und erhalten einen Bestätigungslink

### Ablauf

1. Eine Umfrage erstellen mit den verschiedenen Optionen
2. Das Umfragemodul auf einer Seite einbinden und dort die entsprechende Umfrage festlegen
3. Ausgabe in der Modulausgabe anpassen
4. In YForm das Email-Template anpassen

## Dashboard & Datenauswertung

Das Poll-AddOn bietet ein umfassendes Dashboard zur Auswertung aller Umfragen:

* Übersicht aller aktiven und inaktiven Umfragen
* Grafische Darstellung der Ergebnisse mit selbst implementierten Balken- und Kreis-Diagrammen
* Detaillierte Auswertung jeder einzelnen Frage einer Umfrage
* Zeitlicher Verlauf der Teilnahmen
* Anzeige von Freitext-Antworten

Das Dashboard verwendet keine externen Bibliotheken und ist vollständig mit eigenen CSS/JS-Assets implementiert.

## Eigene Module erstellen

### Grundlegendes Modul

Hier ist ein Beispiel für ein einfaches Poll-Modul:

```php
<?php
// Eingabe
?>
<div class="form-group">
    <label for="poll_id">Umfrage auswählen</label>
    <?php
    $select = new rex_select();
    $select->setName('REX_INPUT_VALUE[1]');
    $select->setId('poll_id');
    $select->setSize(1);
    $select->setAttribute('class', 'form-control');
    $select->addOption('Bitte wählen', '');

    $polls = Poll\Poll::query()->where('status', 1)->orderBy('title')->find();
    
    foreach ($polls as $poll) {
        $select->addOption($poll->getTitle(), $poll->getId());
    }
    $select->setSelected('REX_VALUE[1]');
    echo $select->get();
    ?>
</div>

<?php
// Ausgabe
?>
<div class="poll-container">
    <?php
    if ('REX_VALUE[1]' != '') {
        $poll = Poll\Poll::get(intval('REX_VALUE[1]'));
        if ($poll) {
            $fragment = new rex_fragment();
            $fragment->setVar('poll', $poll);
            echo $fragment->parse('addons/poll/poll.php');
        }
    }
    ?>
</div>
```

### Anpassung des vorhandenen Moduls

Um die Ausgabe des Fragments `addons/poll/poll.php` anzupassen, können Sie eine eigene Version erstellen:

### Project-AddOn 
1. Die Datei `/redaxo/src/addons/poll/fragments/addons/poll/poll.php` nach `/redaxo/src/addons/project/fragments/addons/poll/poll.php` kopieren
2. Dann gestalterisch anpassen. 


#### Theme-AddOn
1. Die Datei `/redaxo/src/addons/poll/fragments/addons/poll/poll.php` nach `/theme/private/fragments/addons/poll/poll.php` kopieren
2. Dann gestalterisch anpassen. 

## Sprog-Integration (CSV-Liste)

Hier sind die für das Sprog-AddOn benötigten Variablen:

```csv
key;de_de
poll_title;Umfrage
poll_result;Ergebnis
poll_votes_taken;Anzahl der abgegebenen Stimmen:
poll_vote_success;Ihre Stimme wurde erfolgreich gespeichert!
poll_vote_confirm;Bitte bestätigen Sie Ihre Abstimmung über den Link in der E-Mail.
poll_vote_exists;Sie haben bereits an dieser Umfrage teilgenommen.
poll_vote_fail;Die Aktivierung Ihrer Stimme ist fehlgeschlagen.
poll_finished;Diese Umfrage ist beendet.
poll_answer;Ihre Antwort
poll_validate_question;Bitte beantworten Sie diese Frage.
poll_validate_email;Bitte geben Sie eine gültige E-Mail-Adresse ein.
poll_email_label;E-Mail-Adresse
poll_email_note;Sie erhalten einen Link zur Bestätigung Ihrer Abstimmung per E-Mail.
poll_submit_poll;Abstimmen
poll_comment_legend;Kommentar
poll_comment_label;Ihr Kommentar (optional)
poll_datenschutz_checkbox;Ich stimme der Verarbeitung meiner Daten gemäß Datenschutzerklärung zu.
poll_datenschutz_checkbox_error;Bitte stimmen Sie der Datenverarbeitung zu.
```

## Changelog

Siehe [CHANGELOG.md](CHANGELOG.md).

## Lizenz

[MIT Lizenz](LICENSE.md)

## Autor

* [@FriendsOfREDAXO](https://github.com/FriendsOfREDAXO/poll/graphs/contributors)
