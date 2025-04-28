/**
 * Poll Dashboard JavaScript
 * 
 * Bietet Funktionen zur Erstellung von Diagrammen und interaktiven Elementen
 * für das Poll-AddOn Dashboard ohne externe Bibliotheken.
 */

(function() {
    'use strict';

    /**
     * PollDashboard Namespace
     */
    var PollDashboard = {
        
        /**
         * Initialisiert das Dashboard
         */
        init: function() {
            document.addEventListener('DOMContentLoaded', function() {
                // Umfrage-Selector Listener
                var pollSelector = document.getElementById('poll-selector');
                if (pollSelector) {
                    pollSelector.addEventListener('change', PollDashboard.handlePollSelection);
                }
                
                // Initialisiere alle Diagramme
                PollDashboard.initAllCharts();
                
                // Event-Listener für Bootstrap Modals
                PollDashboard.initModalEventListeners();
            });
        },
        
        /**
         * Initialisiert Event-Listener für Modal-Fenster
         */
        initModalEventListeners: function() {
            // Wenn ein Modal geöffnet wird, initialisiere das Kreisdiagramm
            $(document).on('shown.bs.modal', '.modal', function() {
                var modal = $(this);
                var chartContainer = modal.find('.poll-pie-chart');
                
                if (chartContainer.length) {
                    // Initialisiere das Chart im Modal
                    var data = JSON.parse(chartContainer.attr('data-values') || '[]');
                    var labels = JSON.parse(chartContainer.attr('data-labels') || '[]');
                    var colors = JSON.parse(chartContainer.attr('data-colors') || '[]');
                    
                    if (data.length) {
                        // Leere den Container für das Chart
                        chartContainer.empty();
                        
                        // Erstelle das Chart
                        PollDashboard.createPieChart(chartContainer[0], data, labels, colors);
                        
                        // Markiere als initialisiert
                        chartContainer.attr('data-initialized', 'true');
                    }
                }
            });
        },
        
        /**
         * Verarbeitet die Auswahl einer Umfrage im Selector
         */
        handlePollSelection: function() {
            var selector = document.getElementById('poll-selector');
            if (!selector) return;
            
            // Alle Poll-Details ausblenden
            var details = document.querySelectorAll('.poll-details');
            for (var i = 0; i < details.length; i++) {
                details[i].style.display = 'none';
            }
            
            // Ausgewählte Details anzeigen
            var selectedId = selector.value;
            if (selectedId) {
                var selected = document.getElementById(selectedId);
                if (selected) {
                    selected.style.display = 'block';
                }
            }
        },
        
        /**
         * Initialisiert alle Diagramme auf der Seite
         */
        initAllCharts: function() {
            PollDashboard.initBarCharts();
            // Kreisdiagramme werden jetzt bei Bedarf (im Modal) initialisiert
            PollDashboard.initTimeline();
        },
        
        /**
         * Initialisiert alle Balken-Diagramme
         */
        initBarCharts: function() {
            var barCharts = document.querySelectorAll('.poll-bar-chart');
            
            barCharts.forEach(function(chart) {
                var data = JSON.parse(chart.getAttribute('data-values') || '[]');
                var labels = JSON.parse(chart.getAttribute('data-labels') || '[]');
                var colors = JSON.parse(chart.getAttribute('data-colors') || '[]');
                
                if (data.length) {
                    PollDashboard.createBarChart(chart.id, data, labels, colors);
                }
            });
        },
        
        /**
         * Erstellt ein Balken-Diagramm
         */
        createBarChart: function(containerId, data, labels, colors) {
            var container = document.getElementById(containerId);
            if (!container) return;
            
            // Lösche vorhandene Inhalte
            container.innerHTML = '';
            
            // Erstelle eine Tabelle für bessere Darstellung
            var table = document.createElement('table');
            table.className = 'poll-bar-table';
            container.appendChild(table);
            
            var maxValue = Math.max.apply(null, data);
            if (maxValue === 0) maxValue = 1; // Vermeidet Division durch Null
            
            // Legende und Spaltenüberschriften
            var thead = document.createElement('thead');
            var headerRow = document.createElement('tr');
            
            var thTitle = document.createElement('th');
            thTitle.textContent = 'Umfrage';
            thTitle.className = 'poll-bar-table-title';
            headerRow.appendChild(thTitle);
            
            var thBar = document.createElement('th');
            thBar.textContent = 'Stimmen';
            thBar.className = 'poll-bar-table-bar';
            headerRow.appendChild(thBar);
            
            var thCount = document.createElement('th');
            thCount.textContent = 'Anzahl';
            thCount.className = 'poll-bar-table-count';
            headerRow.appendChild(thCount);
            
            thead.appendChild(headerRow);
            table.appendChild(thead);
            
            // Tabellenkörper mit Datenzeilen
            var tbody = document.createElement('tbody');
            
            for (var i = 0; i < data.length; i++) {
                var row = document.createElement('tr');
                
                // Umfragetitel
                var titleCell = document.createElement('td');
                titleCell.textContent = labels[i] || '';
                titleCell.className = 'poll-bar-table-title';
                row.appendChild(titleCell);
                
                // Balken
                var barCell = document.createElement('td');
                barCell.className = 'poll-bar-table-bar';
                
                var barContainer = document.createElement('div');
                barContainer.className = 'poll-bar-table-container';
                
                var barElement = document.createElement('div');
                barElement.className = 'poll-bar-table-element';
                
                var width = Math.max((data[i] / maxValue * 100), 3); // Mindestbreite 3%
                barElement.style.width = width + '%';
                barElement.style.backgroundColor = colors[i] || '#4b9ad9';
                
                barContainer.appendChild(barElement);
                barCell.appendChild(barContainer);
                row.appendChild(barCell);
                
                // Zahlenwert
                var countCell = document.createElement('td');
                countCell.textContent = data[i];
                countCell.className = 'poll-bar-table-count';
                row.appendChild(countCell);
                
                tbody.appendChild(row);
            }
            
            table.appendChild(tbody);
            
            // Hinweis hinzufügen, wenn keine Daten vorhanden
            if (data.length === 0) {
                var noDataRow = document.createElement('tr');
                var noDataCell = document.createElement('td');
                noDataCell.colSpan = 3;
                noDataCell.textContent = 'Keine Daten vorhanden';
                noDataCell.style.textAlign = 'center';
                noDataCell.style.padding = '20px';
                noDataRow.appendChild(noDataCell);
                tbody.appendChild(noDataRow);
            }
        },
        
        /**
         * Initialisiert alle Kreis-Diagramme
         * Wird jetzt hauptsächlich bei Modal-Öffnung verwendet
         */
        initPieCharts: function() {
            var pieCharts = document.querySelectorAll('.poll-pie-chart');
            
            pieCharts.forEach(function(chart) {
                // Verhindere doppelte Initialisierung
                if (chart.getAttribute('data-initialized') === 'true') {
                    return;
                }
                
                try {
                    var data = JSON.parse(chart.getAttribute('data-values') || '[]');
                    var labels = JSON.parse(chart.getAttribute('data-labels') || '[]');
                    var colors = JSON.parse(chart.getAttribute('data-colors') || '[]');
                    
                    if (data.length) {
                        // Leere das Element vor dem Erstellen des Charts
                        chart.innerHTML = '';
                        PollDashboard.createPieChart(chart, data, labels, colors);
                        
                        // Markiere als initialisiert
                        chart.setAttribute('data-initialized', 'true');
                    }
                } catch (e) {
                    console.error('Fehler beim Initialisieren des Pie-Charts:', e);
                }
            });
        },
        
        /**
         * Erstellt ein Kreis-Diagramm mit SVG für bessere Browser-Kompatibilität
         * 
         * @param {HTMLElement} container - Der Container für das Diagramm
         * @param {Array} data - Array mit Zahlenwerten
         * @param {Array} labels - Array mit Beschriftungen
         * @param {Array} colors - Array mit Farben
         */
        createPieChart: function(container, data, labels, colors) {
            // Berechne Gesamtsumme
            var total = data.reduce(function(sum, value) {
                return sum + value;
            }, 0);
            
            if (total === 0) {
                container.innerHTML = '<div class="poll-no-data">Keine Daten verfügbar</div>';
                return;
            }
            
            // Container für die neue Flex-Layout-Struktur
            var chartContainer = document.createElement('div');
            chartContainer.className = 'poll-chart-flex-container';
            chartContainer.style.display = 'flex';
            chartContainer.style.flexDirection = 'row';
            chartContainer.style.alignItems = 'center';
            chartContainer.style.gap = '20px';
            
            // Größere Grafik im Modal
            var isModal = $(container).closest('.modal-body').length > 0;
            var size = isModal ? 400 : 220; // Größeres Diagramm im Modal
            var radius = size / 2;
            var center = size / 2;
            
            // SVG-Container erstellen
            var svgContainer = document.createElement('div');
            svgContainer.className = 'poll-pie-svg-container';
            svgContainer.style.flexShrink = 0; // Verhindert das Schrumpfen
            
            // SVG-Element erstellen
            var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('width', size);
            svg.setAttribute('height', size);
            svg.setAttribute('viewBox', '0 0 ' + size + ' ' + size);
            svg.setAttribute('class', 'poll-pie-svg');
            
            // Kreisgruppe
            var pieGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            pieGroup.setAttribute('transform', 'translate(' + center + ',' + center + ')');
            
            // Startwinkel
            var startAngle = 0;
            
            // Erstelle für jeden Datenpunkt ein Segment
            for (var i = 0; i < data.length; i++) {
                if (data[i] === 0) continue;
                
                var percentage = data[i] / total;
                var angle = percentage * 360;
                
                // Kreissegment berechnen (SVG-Pfad)
                var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                
                // Arc-Pfad berechnen
                var endAngle = startAngle + angle;
                var startRad = (startAngle - 90) * Math.PI / 180;
                var endRad = (endAngle - 90) * Math.PI / 180;
                
                var x1 = center + radius * Math.cos(startRad);
                var y1 = center + radius * Math.sin(startRad);
                var x2 = center + radius * Math.cos(endRad);
                var y2 = center + radius * Math.sin(endRad);
                
                // Große/kleine Bogen-Flag (größer/kleiner als 180 Grad)
                var largeArcFlag = angle > 180 ? 1 : 0;
                
                // SVG-Pfad für das Kreissegment
                var d = [
                    'M', center, center,  // Bewege zum Zentrum
                    'L', x1, y1,          // Linie zum Kreisrand
                    'A', radius, radius, 0, largeArcFlag, 1, x2, y2, // Kreisbogen
                    'Z'                   // Schließe Pfad
                ].join(' ');
                
                path.setAttribute('d', d);
                path.setAttribute('fill', colors[i] || PollDashboard.getRandomColor());
                
                // Hover-Effekt hinzufügen
                path.setAttribute('class', 'poll-pie-segment-path');
                path.setAttribute('data-index', i);
                
                // Event-Listener für Hover-Effekt
                path.addEventListener('mouseenter', function() {
                    var index = this.getAttribute('data-index');
                    var legendItem = container.querySelector('.poll-pie-legend-item[data-index="' + index + '"]');
                    if (legendItem) {
                        legendItem.classList.add('poll-pie-legend-item-highlight');
                    }
                });
                
                path.addEventListener('mouseleave', function() {
                    var index = this.getAttribute('data-index');
                    var legendItem = container.querySelector('.poll-pie-legend-item[data-index="' + index + '"]');
                    if (legendItem) {
                        legendItem.classList.remove('poll-pie-legend-item-highlight');
                    }
                });
                
                pieGroup.appendChild(path);
                
                startAngle = endAngle; // Für das nächste Segment
            }
            
            // SVG zum Container hinzufügen
            svg.appendChild(pieGroup);
            svgContainer.appendChild(svg);
            
            // Legende erstellen
            var legend = document.createElement('div');
            legend.className = 'poll-pie-legend';
            legend.style.flexGrow = '1'; // Erlaubt das Wachsen, um den verfügbaren Platz zu füllen
            
            for (var j = 0; j < data.length; j++) {
                if (data[j] === 0) continue;
                
                var percent = Math.round((data[j] / total) * 100);
                
                var legendItem = document.createElement('div');
                legendItem.className = 'poll-pie-legend-item';
                legendItem.setAttribute('data-index', j);
                
                var colorBox = document.createElement('span');
                colorBox.className = 'poll-pie-legend-color';
                colorBox.style.backgroundColor = colors[j] || PollDashboard.getRandomColor();
                
                var labelText = document.createElement('span');
                labelText.className = 'poll-pie-legend-text';
                labelText.textContent = labels[j] + ': ' + data[j] + ' (' + percent + '%)';
                
                legendItem.appendChild(colorBox);
                legendItem.appendChild(labelText);
                
                // Event-Listener für Hover-Effekt
                legendItem.addEventListener('mouseenter', function() {
                    var index = this.getAttribute('data-index');
                    var segment = container.querySelector('.poll-pie-segment-path[data-index="' + index + '"]');
                    if (segment) {
                        segment.classList.add('poll-pie-segment-path-highlight');
                    }
                    this.classList.add('poll-pie-legend-item-highlight');
                });
                
                legendItem.addEventListener('mouseleave', function() {
                    var index = this.getAttribute('data-index');
                    var segment = container.querySelector('.poll-pie-segment-path[data-index="' + index + '"]');
                    if (segment) {
                        segment.classList.remove('poll-pie-segment-path-highlight');
                    }
                    this.classList.remove('poll-pie-legend-item-highlight');
                });
                
                legend.appendChild(legendItem);
            }
            
            // Füge SVG und Legende zum Flex-Container hinzu
            chartContainer.appendChild(svgContainer);
            chartContainer.appendChild(legend);
            
            // Leere den ursprünglichen Container und füge den Flex-Container hinzu
            container.innerHTML = '';
            container.appendChild(chartContainer);
        },
        
        /**
         * Initialisiert die Aktivitäts-Timeline
         */
        initTimeline: function() {
            var timeline = document.getElementById('poll-activity-timeline');
            if (!timeline) return;
            
            var data = JSON.parse(timeline.getAttribute('data-values') || '[]');
            var dates = JSON.parse(timeline.getAttribute('data-dates') || '[]');
            
            if (data.length && dates.length) {
                PollDashboard.createTimeline(timeline.id, data, dates);
            } else {
                // Statt zufällige Daten zu zeigen, einen Hinweis anzeigen
                timeline.innerHTML = '<div class="poll-no-data">Keine Aktivitätsdaten verfügbar</div>';
            }
        },
        
        /**
         * Erstellt eine Aktivitäts-Timeline
         */
        createTimeline: function(containerId, data, dates) {
            var container = document.getElementById(containerId);
            if (!container) return;
            
            // Container leeren
            container.innerHTML = '';
            
            // Timeline Container
            var timelineContainer = document.createElement('div');
            timelineContainer.className = 'poll-timeline';
            container.appendChild(timelineContainer);
            
            // Zeitachse
            var axis = document.createElement('div');
            axis.className = 'poll-timeline-axis';
            container.appendChild(axis);
            
            // Finde Maximum
            var maxValue = Math.max.apply(null, data) || 1;
            
            // Horizontale Linie
            var line = document.createElement('div');
            line.className = 'poll-timeline-line';
            line.style.width = '100%';
            timelineContainer.appendChild(line);
            
            // Datenpunkte hinzufügen
            for (var i = 0; i < data.length; i++) {
                var x = (i / (data.length - 1) * 100);
                var y = 100 - (data[i] / maxValue * 100);
                
                var point = document.createElement('div');
                point.className = 'poll-timeline-point';
                point.style.left = x + '%';
                point.style.top = y + '%';
                point.title = dates[i] + ': ' + data[i];
                
                timelineContainer.appendChild(point);
                
                // Zeigen wir nur einige Labels an, um Überlappung zu vermeiden
                if (i % Math.ceil(data.length / 10) === 0 || i === data.length - 1) {
                    var label = document.createElement('div');
                    label.className = 'poll-timeline-label';
                    label.textContent = dates[i];
                    label.style.left = x + '%';
                    axis.appendChild(label);
                }
            }
        },
        
        /**
         * Generiert eine zufällige Farbe
         */
        getRandomColor: function() {
            return 'rgba(' +
                Math.floor(Math.random() * 150) + ',' +
                Math.floor(Math.random() * 150) + ',' +
                (Math.floor(Math.random() * 105) + 150) + ',' +
                '0.6)';
        }
    };
    
    // Exportieren des PollDashboard-Objekts
    window.PollDashboard = PollDashboard;
    
    // Initialisierung beim Laden
    PollDashboard.init();
    
})();