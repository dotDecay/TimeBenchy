<?php

namespace DotDecay\TimeBenchy;


/**
 * Benchmark Tool for measuring execution times
 * Marking places in code to measure execution time between them.
 * Example usage:
 * $timeBenchy = new TimeBenchy();
 * $timeBenchy->mark('Start');
 * [... some code here ...]
 * $timeBenchy->mark('After function X');
 * [... some code here ...]
 * $timeBenchy->mark('After Database call');
 * [... some code here ...]
 * $timeBenchy->mark('End');
 * $timeBenchy->printStats();
 *
 * @author  Alexander Solbrig <aexl@dotdecay.com>
 * @version 1.0.0
 */
class TimeBenchy
{
    public array $marker = [];

    /**
     * Set a benchmark marker
     * Multiple calls can be made.
     *
     * @param string $label Marker label
     *
     * @return    void
     * @access public
     */
    public function mark(string $label): void
    {
        $this->marker[$label] = microtime(true);
    }

    /**
     * Calc time diff
     * Calculates the time difference between two marked points.
     *
     * @param string $point1   A particular marked point
     * @param string $point2   A particular marked point
     * @param int    $decimals Number of decimal places
     *
     * @return    string    Calculated elapsed time on success,
     *            an '{elapsed_string}' if $point1 is empty
     *            or an empty string if $point1 is not found.
     * @access public
     */
    public function calcTimeDiff(string $point1, string $point2 = '', int $decimals = 4): string
    {
        if (!isset($this->marker[$point1])) {
            return '';
        }

        if (!isset($this->marker[$point2])) {
            $this->marker[$point2] = microtime(true);
        }

        return self::numberFormatAuto($this->marker[$point2] - $this->marker[$point1], $decimals);
    }

    /**
     * Get stats
     * Returns rough stats as array.
     *
     * @return array
     * @access public
     */
    public function getStats(): array
    {
        $stats = [];

        $prevCheckTime = 0;
        if (!empty($this->marker)) {
            $startTime = array_values($this->marker)[0];
            $prevTime  = $startTime;
            foreach ($this->marker as $markerLabel => $markerTime) {
                $stats[$markerLabel] = [
                    'microtime'          => $markerTime,
                    'timeDiffSinceStart' => $markerTime - $startTime,
                    'timeDiffSincePrev'  => $markerTime - $prevTime,
                ];

                $prevTime = $markerTime;
            }
        }

        return $stats;
    }

    /**
     * Print stats
     * Prints stats as a table directly with echo as a floating table at the bottom left corner.
     *
     * @param int $roundTimediffDecimals
     *
     * @return void
     * @access public
     */
    public function printStats(int $roundTimediffDecimals = 4): void {
        $stats = $this->getStats();

        if (!empty($stats)) {
            $styleTagContent = <<<CSS
#time-benchy {
    position:fixed;
    left:10px;
    bottom:10px;
    opacity:.5;
    color:rgb(220 220 220);
}

#time-benchy__button {
    display:inline-block;
    padding: 8px 12px;
    background:rgb(49 49 49);
    border: 1px solid rgb(100 100 100);
}

#time-benchy__item {
    display: none;
    max-width:90vw;
    max-height:90vh;
    position: absolute;
    bottom:100%;
    left:0;
    background:rgb(49 49 49);
    overflow:auto;
}

#time-benchy__item table {
    border-collapse: collapse;
}

#time-benchy__item table, #time-benchy__item th, #time-benchy__item td {
    border: 1px solid rgb(100 100 100);
}

#time-benchy__item th, #time-benchy__item td {
    padding:5px 10px;
    border:1px solid rgb(100 100 100);
    white-space: nowrap;
}

#time-benchy__item table {
    width: 100%;
    border-collapse: collapse;
}

#time-benchy:hover {
    opacity:1;
}

#time-benchy:hover #time-benchy__item {
    display: block;
}

#time-benchy th,
#time-benchy td {
    text-align: right;
}
#time-benchy th:first-child,
#time-benchy td:first-child {
    text-align: left;
}
CSS;

            echo "<script>
    const css = '" . trim(preg_replace('/\s+/', ' ', $styleTagContent)) . "',
    head = document.head || document.getElementsByTagName('head')[0],
    style = document.createElement('style');
    
    style.appendChild(document.createTextNode(css));
    head.appendChild(style);
</script>";

            echo '<div id="time-benchy">';
            echo '<div id="time-benchy__button">TimeBenchy</div>';
            echo '<div id="time-benchy__item">';
            echo '<table>';
            echo '<thead>';
            echo '<tr><th>Label</th><th><abbr title="Zeitunterschied seit Start bis diesem Eintrag">seit Start</abbr></th><th><abbr title="Zeitunterschied seit vorherigem bis diesem Eintrag">seit Vorheriger</abbr></th><th>Microtime</th></tr>';
            echo '</thead>';
            echo '<tbody>';
            foreach ($stats as $label => $stats) {
                echo '<tr><th>' . $label . '</th><td>' . self::numberFormatAuto(
                        $stats['timeDiffSinceStart'],
                        $roundTimediffDecimals
                    ) . ' s</td><td>' . self::numberFormatAuto(
                        $stats['timeDiffSincePrev'],
                        $roundTimediffDecimals
                    ) . ' s</td><td>' . self::numberFormatAuto($stats['microtime'], 4) . ' s</td></tr>';
            }
            echo '</tbody>';
            echo '<tfoot>';
            echo '<tr><td colspan="4"><small>Angaben in Sekunden gerundet auf ' . $roundTimediffDecimals . '. Nachkommastelle.</small></td></tr>';
            echo '</tfoot>';
            echo '</div>';
            echo '</div>';
        }
    }

    /**
     * Number format auto
     * Formating numbers with . for thousands separator and , for decimal separator.
     *
     * @param $value
     * @param $decimals
     * @param $zeroIsNull
     *
     * @return string
     * @access private
     */
    private static function numberFormatAuto($value, $decimals = 2, $zeroIsNull = false): string
    {
        // Not a number
        if (!is_numeric($value)) {
            return '';
        }
        $value = (float) $value;

        /** @noinspection TypeUnsafeComparisonInspection */
        if ($zeroIsNull && $value == 0) {
            return '';
        }
        return number_format($value, self::maxDecimals($value, $decimals), ',', '.');
    }

    /**
     * Max decimals
     * Check the number for the max possible decimals.
     *
     * @param $value
     * @param $decimals
     *
     * @return int
     * @access private
     */
    private static function maxDecimals($value, $decimals = 3): int
    {
        $value = round($value, $decimals);
        for ($i = 0; $i < $decimals; $i++) {
            /** @noinspection TypeUnsafeComparisonInspection */
            if ($value * (10 ** $i) == (int) ($value * (10 ** $i))) {
                return $i;
            }
        }

        // fallback
        return $decimals;
    }
}