<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Services\Matching;

/**
 * Similarity — name comparison helpers for customer matching.
 *
 * Soundex is the primary tool here because import names arrive with inconsistent
 * spelling ("Lovelace" / "Lovalace", "Smith" / "Smyth", "Kowalski" / "Kowalsky")
 * that defeat exact and substring comparison. It folds those together while
 * still separating genuinely different names that merely share a first letter.
 *
 * PHP 7.3 compatible: no typed properties, no property promotion.
 *
 * @package ksf_FA_ImportStagingProcessing
 */
class Similarity
{
    /**
     * Classic Russell soundex code, or '' when the input has no letters.
     *
     * @param string $value
     * @return string 4-character code, or '' if uncodeable
     */
    public function soundex($value)
    {
        // Soundex codes NAMES, so operate on letters only. Without this a
        // numeric id like "123" yields "1000" instead of an empty code.
        $value = preg_replace('/[^a-z ]+/', '', $this->normalise($value));
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        // Keep the first letter, drop H/W after it (they do not change the code).
        $first = $value[0];
        $previous = $this->codeOf($first);

        $digits = '';
        $length = strlen($value);
        for ($i = 1; $i < $length && strlen($digits) < 3; $i++) {
            $char = $value[$i];
            $code = $this->codeOf($char);

            // H and W are transparent: they neither emit nor reset.
            if ($char === 'h' || $char === 'w') {
                continue;
            }

            if ($code === '') {
                // A vowel or other non-coded letter resets adjacency.
                $previous = $code;
                continue;
            }

            if ($code !== $previous) {
                $digits .= $code;
            }
            $previous = $code;
        }

        return strtoupper($first) . str_pad($digits, 3, '0');
    }

    /**
     * Whether two names share a soundex code.
     *
     * @param string $a
     * @param string $b
     * @return bool False when either side cannot be coded
     */
    public function soundexMatches($a, $b)
    {
        $codeA = $this->soundex($a);
        $codeB = $this->soundex($b);

        if ($codeA === '' || $codeB === '') {
            return false;
        }

        return $codeA === $codeB;
    }

    /**
     * Similarity of the surname component, 0.0 - 1.0.
     *
     * Combines soundex equality (strong) with a character-level ratio, so
     * "Lovelace"/"Lovalace" scores high on both while "Smith"/"Jones" scores low
     * on both.
     *
     * @param string $a
     * @param string $b
     * @return float
     */
    public function nameSimilarity($a, $b)
    {
        $a = $this->normalise($a);
        $b = $this->normalise($b);
        if ($a === '' || $b === '') {
            return 0.0;
        }
        if ($a === $b) {
            return 1.0;
        }

        $ratio = $this->ratio($a, $b);

        if ($this->soundexMatches($a, $b)) {
            // A soundex collision lifts the score but never certifies a match on
            // its own -- common words collide (e.g. "Lee"/"Leigh").
            return max($ratio, 0.85);
        }

        return $ratio;
    }

    /**
     * Character-level similarity, 0.0 - 1.0.
     *
     * Levenshtein-based: (longer - distance) / longer. The implementation is
     * iterative over two rows to stay within memory on long inputs.
     *
     * @param string $a
     * @param string $b
     * @return float
     */
    public function ratio($a, $b)
    {
        $a = $this->normalise($a);
        $b = $this->normalise($b);

        if ($a === '' || $b === '') {
            return 0.0;
        }
        if ($a === $b) {
            return 1.0;
        }

        $lenA = strlen($a);
        $lenB = strlen($b);
        if ($lenA < $lenB) {
            $swap = $a;
            $a = $b;
            $b = $swap;
            $tmp = $lenA;
            $lenA = $lenB;
            $lenB = $tmp;
        }

        $previous = range(0, $lenB);
        for ($i = 1; $i <= $lenA; $i++) {
            $current = array($i);
            for ($j = 1; $j <= $lenB; $j++) {
                $cost = $a[$i - 1] === $b[$j - 1] ? 0 : 1;
                $current[$j] = min(
                    $previous[$j] + 1,
                    $current[$j - 1] + 1,
                    $previous[$j - 1] + $cost
                );
            }
            $previous = $current;
        }

        $distance = $previous[$lenB];

        return max(0.0, ($lenA - $distance) / $lenA);
    }

    /**
     * Last name of a free-text name, or the whole string when single-token.
     *
     * @param string $name
     * @return string
     */
    public function surname($name)
    {
        $parts = preg_split('/\s+/', trim((string)$name));
        if (!$parts) {
            return '';
        }

        return count($parts) > 1 ? (string)end($parts) : (string)$parts[0];
    }

    /**
     * First name of a free-text name.
     *
     * @param string $name
     * @return string
     */
    public function firstName($name)
    {
        $parts = preg_split('/\s+/', trim((string)$name));
        if (!$parts) {
            return '';
        }

        return (string)$parts[0];
    }

    /**
     * Lowercase, collapse whitespace, keep letters and digits.
     *
     * @param string $value
     * @return string
     */
    private function normalise($value)
    {
        $value = strtolower(trim((string)$value));
        $value = preg_replace('/[^a-z0-9 ]+/', ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    /**
     * Soundex digit for a letter, '' when the letter is not coded.
     *
     * @param string $char
     * @return string
     */
    private function codeOf($char)
    {
        $map = array(
            'b' => '1', 'f' => '1', 'p' => '1', 'v' => '1',
            'c' => '2', 'g' => '2', 'j' => '2', 'k' => '2', 'q' => '2', 's' => '2', 'x' => '2', 'z' => '2',
            'd' => '3', 't' => '3',
            'l' => '4',
            'm' => '5', 'n' => '5',
            'r' => '6',
        );

        return isset($map[$char]) ? $map[$char] : '';
    }
}