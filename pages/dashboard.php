<?php
/**
 * Dashboard für Umfrage-Auswertungen
 *
 * @package poll
 */

use Poll\Poll;
use Poll\Vote;
use Poll\Vote\Answer;

// Titel der Seite
echo rex_view::title($this->i18n('poll') . ' - Dashboard');


// CSS für animiertes Symbol
echo '
<style>
.poll-active-icon {
    display: inline-block;
    color: #28a745;
    animation: pulse 1.5s infinite;
    margin-right: 5px;
}

@keyframes pulse {
    0% { opacity: 0.6; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.2); }
    100% { opacity: 0.6; transform: scale(1); }
}

.poll-inactive-icon {
    display: inline-block;
    color: #dc3545;
    margin-right: 5px;
}
</style>
';

// Statistik-Übersicht erzeugen
$content = '';
$pollData = [];
$pollLabels = [];
$pollVotes = [];
$pollColors = [];
$pollStatus = []; // Status jeder Umfrage speichern

// Grundlegende Statistiken sammeln
$totalPolls = count(Poll::getAll());
$activePolls = count(Poll::query()->where('status', 1)->find());

$totalVotes = Vote::query()->where('status', 1)->count();

// Chart-Daten für alle Umfragen sammeln
$polls = Poll::query()->orderBy('title')->find();
foreach ($polls as $poll) {
    // Titel mit Statusanzeige
    $statusIcon = $poll->isOnline() ? '<i class="fa fa-circle poll-active-icon"></i> ' : '<i class="fa fa-circle-o poll-inactive-icon"></i> ';
    $pollLabels[] = $statusIcon . $poll->getTitle();
    $pollVotes[] = $poll->getHits();
    $pollStatus[] = $poll->isOnline(); // Status speichern für spätere Verwendung
    
    // Farbe für jede Umfrage
    $pollColors[] = 'rgba(' . mt_rand(0, 150) . ',' . mt_rand(0, 150) . ',' . mt_rand(150, 255) . ', 0.6)';
}

// Aktivitätsdaten für Timeline sammeln
$activityData = [];
$activityDates = [];

// Votes nach Datum gruppieren (letzte 30 Tage)
$query = "
    SELECT 
        DATE(create_datetime) as vote_date, 
        COUNT(*) as vote_count 
    FROM 
        " . rex::getTablePrefix() . "poll_vote
    WHERE 
        status = 1
        AND create_datetime >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY 
        DATE(create_datetime)
    ORDER BY 
        vote_date ASC
";

$result = rex_sql::factory()->setQuery($query);
$votesByDate = $result->getArray();

// Letzten 30 Tage als Basis verwenden (auch Tage ohne Votes)
$endDate = new DateTime();
$startDate = new DateTime();
$startDate->modify('-29 days'); // 30 Tage inkl. heute

$currentDate = clone $startDate;
while ($currentDate <= $endDate) {
    $dateString = $currentDate->format('Y-m-d');
    $formattedDate = $currentDate->format('d.m.Y');
    
    // Prüfen ob es für dieses Datum Votes gibt
    $voteCount = 0;
    foreach ($votesByDate as $vote) {
        if ($vote['vote_date'] === $dateString) {
            $voteCount = (int)$vote['vote_count'];
            break;
        }
    }
    
    $activityData[] = $voteCount;
    $activityDates[] = $formattedDate;
    
    $currentDate->modify('+1 day');
}

$content .= '<div class="poll-dashboard-container">';

// Dashboard-Header mit Kernzahlen
$content .= '
<div class="poll-stats-grid">
    <div class="poll-stat-box">
        <h3><i class="fa fa-list"></i> ' . rex_i18n::msg('poll_dashboard_total_polls') . '</h3>
        <p>' . $totalPolls . '</p>
    </div>
    <div class="poll-stat-box">
        <h3><i class="fa fa-check-circle"></i> ' . rex_i18n::msg('poll_dashboard_active_polls') . '</h3>
        <p>' . $activePolls . '</p>
    </div>
    <div class="poll-stat-box">
        <h3><i class="fa fa-bar-chart"></i> ' . rex_i18n::msg('poll_dashboard_total_votes') . '</h3>
        <p>' . $totalVotes . '</p>
    </div>
</div>';

// Balkendiagramm für alle Umfragen
$content .= '
<div class="poll-card">
    <div class="poll-card-header"><i class="fa fa-bar-chart"></i> ' . rex_i18n::msg('poll_dashboard_votes_per_poll') . '</div>
    <div class="poll-card-body">
        <div id="poll-overview-chart" class="poll-bar-chart" 
            data-values=\'' . json_encode($pollVotes) . '\' 
            data-labels=\'' . json_encode($pollLabels) . '\' 
            data-colors=\'' . json_encode($pollColors) . '\'></div>
    </div>
</div>';

// Detaillierte Aufschlüsselung für jede Umfrage
$content .= '<div class="poll-card">';
$content .= '<div class="poll-card-header"><i class="fa fa-pie-chart"></i> ' . rex_i18n::msg('poll_dashboard_detailed_results') . '</div>';
$content .= '<div class="poll-card-body">';

// Umfrage-Selector
$content .= '<div class="form-group">';
$content .= '<label for="poll-selector">' . rex_i18n::msg('poll_select_poll_to_analyze') . ':</label>';
$content .= '<select class="form-control poll-selector" id="poll-selector">';
$content .= '<option value="">' . rex_i18n::msg('poll_please_select') . '</option>';
$i = 0;
foreach ($polls as $poll) {
    $status_icon = $pollStatus[$i] ? '<i class="fa fa-circle poll-active-icon"></i> ' : '<i class="fa fa-circle-o poll-inactive-icon"></i> ';
    $content .= '<option value="poll-detail-' . $poll->getId() . '">' . $status_icon . $poll->getTitle() . ' (' . $poll->getHits() . ' ' . rex_i18n::msg('poll_votes') . ')</option>';
    $i++;
}
$content .= '</select>';
$content .= '</div>';

// Container für detaillierte Umfragestatistiken
$i = 0;
foreach ($polls as $poll) {
    $statistics = $poll->getStatistics();
    
    $content .= '<div id="poll-detail-' . $poll->getId() . '" class="poll-details" style="display:none;">';
    $status_display = $pollStatus[$i] ? 
        '<span class="poll-status active"><i class="fa fa-circle poll-active-icon"></i> ' . rex_i18n::msg('poll_status_active') . '</span>' : 
        '<span class="poll-status inactive"><i class="fa fa-circle-o poll-inactive-icon"></i> ' . rex_i18n::msg('poll_status_inactive') . '</span>';
    $content .= '<h3>' . $poll->getTitle() . ' ' . $status_display . '</h3>';
    $content .= '<p>' . $poll->getDescription() . '</p>';
    $i++;
    
    // Für jede Frage in der Umfrage
    foreach ($statistics['questions'] as $questionIndex => $question) {
        $content .= '<div class="poll-question">';
        $content .= '<h4>' . $question['title'] . '</h4>';
        
        // Wenn die Frage Auswahlmöglichkeiten hat
        if (isset($question['choices']) && !empty($question['choices'])) {
            $content .= '<div class="poll-question-results">';
            
            // Tabellendarstellung
            $content .= '<div class="poll-question-table">';
            $content .= '<table class="poll-results-table">';
            $content .= '<thead><tr>';
            $content .= '<th class="poll-option-col">' . rex_i18n::msg('poll_option') . '</th>';
            $content .= '<th class="poll-votes-col">' . rex_i18n::msg('poll_votes') . '</th>';
            $content .= '<th class="poll-percentage-col">' . rex_i18n::msg('poll_percentage') . '</th>';
            $content .= '</tr></thead>';
            $content .= '<tbody>';
            
            $choiceLabels = [];
            $choiceCounts = [];
            $choiceColors = [];
            
            foreach ($question['choices'] as $choice) {
                $content .= '<tr>';
                $content .= '<td class="poll-option-col">' . $choice['title'] . '</td>';
                $content .= '<td class="poll-votes-col">' . $choice['count'] . '</td>';
                $content .= '<td class="poll-percentage-col">';
                
                // Prozentangabe mit Fortschrittsbalken darstellen
                $content .= '<div class="poll-percentage-bar">';
                $content .= '<div class="poll-percentage-fill" style="width: ' . $choice['percentage'] . '%;"></div>';
                $content .= '<span class="poll-percentage-text">' . $choice['percentage'] . '%</span>';
                $content .= '</div>';
                
                $content .= '</td>';
                $content .= '</tr>';
                
                $choiceLabels[] = $choice['title'];
                $choiceCounts[] = $choice['count'];
                $choiceColors[] = 'rgba(' . mt_rand(0, 150) . ',' . mt_rand(0, 150) . ',' . mt_rand(150, 255) . ', 0.6)';
            }
            
            $content .= '</tbody>';
            $content .= '</table>';
            $content .= '</div>';
            
            // Statt Kreisdiagramm direkt anzuzeigen, Button zur Modal-Anzeige einfügen
            $chartId = "chart-modal-" . $poll->getId() . "-" . $question['id'];
            $chartModalId = "modal-" . $chartId;
            
            // Button für die Diagramm-Anzeige
            $content .= '<div class="poll-question-chart-button">';
            $content .= '<button class="btn btn-default" data-toggle="modal" data-target="#' . $chartModalId . '">';
            $content .= '<i class="fa fa-pie-chart"></i> ' . rex_i18n::msg('poll_show_chart') . '</button>';
            $content .= '</div>';
            
            // Modal für das Kreisdiagramm
            $content .= '<div class="modal fade" id="' . $chartModalId . '" tabindex="-1" role="dialog">';
            $content .= '<div class="modal-dialog modal-lg" role="document">'; // Größeres Modal verwenden
            $content .= '<div class="modal-content">';
            $content .= '<div class="modal-header">';
            $content .= '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
            $content .= '<h4 class="modal-title">' . $question['title'] . '</h4>';
            $content .= '</div>';
            $content .= '<div class="modal-body" style="padding: 20px; text-align: center;">'; // Mehr Padding und zentrieren
            $content .= '<div id="' . $chartId . '" class="poll-pie-chart" 
                data-values=\'' . json_encode($choiceCounts) . '\' 
                data-labels=\'' . json_encode($choiceLabels) . '\' 
                data-colors=\'' . json_encode($choiceColors) . '\'></div>';
            $content .= '</div>';
            $content .= '<div class="modal-footer">';
            $content .= '<button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>';
            $content .= '</div>';
            $content .= '</div>';
            $content .= '</div>';
            $content .= '</div>';
            
            $content .= '</div>'; // Ende poll-question-results
        } 
        // Für Freitext-Fragen
        elseif (isset($question['text_answers'])) {
            $content .= '<div class="text-answers">';
            $content .= '<h5>' . rex_i18n::msg('poll_text_responses') . ' (' . count($question['text_answers']) . ')</h5>';
            
            if (!empty($question['text_answers'])) {
                $content .= '<div class="poll-text-answers">';
                foreach ($question['text_answers'] as $textAnswer) {
                    $content .= '<div class="poll-text-item">' . htmlspecialchars($textAnswer) . '</div>';
                }
                $content .= '</div>';
            } else {
                $content .= '<p>' . rex_i18n::msg('poll_no_text_responses') . '</p>';
            }
            
            $content .= '</div>';
        }
        
        $content .= '</div>'; // Ende poll-question
    }
    
    $content .= '</div>'; // Ende poll-details
}

$content .= '</div>'; // Ende poll-card-body
$content .= '</div>'; // Ende poll-card

$content .= '</div>'; // Ende poll-dashboard-container

$nonce = rex_response::getNonce();
// JavaScript für das Dropdown mit HTML-Inhalt
$content .= '
<script nonce="$nonce">
$(document).ready(function() {
    var selector = $("#poll-selector");
    
    // Füge HTML-Inhalte für das Dropdown hinzu
    selector.html(selector.html().replace(/&lt;i class="fa/g, "<i class=\"fa")
                                 .replace(/&lt;\/i&gt;/g, "</i>"));
    
    // Event Listener für Dropdown-Auswahl
    selector.on("change", function() {
        var selectedId = $(this).val();
        $(".poll-details").hide();
        if (selectedId) {
            $("#" + selectedId).show();
        }
    });
});
</script>
';

// Ausgabe der Seite
$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('poll_dashboard'), false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');