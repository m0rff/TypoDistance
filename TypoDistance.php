<?php

namespace App\Lib\Chatbot;

use Exception;

/**
 * Class TypoDistance
 *
 * This class contains a function called typoDistance, which takes in two strings as arguments.
 * This function finds the typo distance between the two strings, which is the likelihood that the second string is a typo of the first,
 * as measured by a floating point number.
 * The lower the number, the more likely it is that the second string is a typo of the first.
 * Thus, for instance, "rlephants" is a fairly likely typo of "elephants" on a QWERTY keyboard, since R is fairly close to E, but "ilephants" is a less likely typo,
 * and therefore would have a higher score.  One thing to note is that typoDistance is not commutative, since insertions are considered more costly than deletions.
 *
 * Inspiration gathered from https://github.com/wsong/Typo-Distance
 *
 */
class TypoDistance
{
    // Actions costs
    public const SHIFT_COST = 2.0;
    public const INSERTION_COST = 3.0;
    public const DELETION_COST = 3.0;
    public const SUBSTITUTION_COST = 1.0;

    // "lowercase" qwertz keyboard
    public const QWERTZKEYBOARDARRAY = [
        ['^', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', 'ß', '`'],
        ['q', 'w', 'e', 'r', 't', 'z', 'u', 'i', 'o', 'p', 'ü', '+'],
        ['a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'ö', 'ä', '#'],
        ['<', 'y', 'x', 'c', 'v', 'b', 'n', 'm', ',', '.', '-'],
        ['', '', ' ', ' ', ' ', ' ', ' ', '', '']
    ];

    // "uppercase" qwertz keyboard
    public const QWERTZSHIFTEDKEYBOARDARRAY = [
        ['°', '!', '"', '§', '$', '%', '&', '/', '(', ')', '=', '?', '`'],
        ['Q', 'W', 'E', 'R', 'T', 'Z', 'U', 'I', 'O', 'P', 'Ü', '*'],
        ['A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'Ö', 'Ä', '\''],
        ['>', 'Y', 'X', 'C', 'V', 'B', 'N', 'M', ';', ':', '_'],
        ['', '', ' ', ' ', ' ', ' ', ' ', '', '']
    ];

    /**
     * Find $itemSeach in $array
     *
     * @param array  $array      haystack
     * @param string $itemSearch needle
     * @return bool
     */
    public static function findItem($array, $itemSearch)
    {
        foreach ($array as $item) {
            if ($item === $itemSearch) {
                return true;
            } elseif (is_array($item) && self::findItem($item, $itemSearch)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the keyboard layout c "lives in"; for instance, if c is A, this will
     * return the shifted keyboard array, but if it is a, it will return the regular
     * keyboard array.
     *
     * @param string $c needle character
     * @return array keyboard array
     * @throws Exception
     */
    public static function arrayForChar($c)
    {
        if (self::findItem(self::QWERTZKEYBOARDARRAY, $c)) {
            return self::QWERTZKEYBOARDARRAY;
        } elseif (self::findItem(self::QWERTZSHIFTEDKEYBOARDARRAY, $c)) {
            return self::QWERTZSHIFTEDKEYBOARDARRAY;
        } else {
            throw new Exception($c . " not found in any keyboard layouts");
        }
    }

    /**
     * Finds a 2-tuple (array with 2 elements) representing c's position on the given keyboard array.  If
     * the character is not in the given array
     *
     * @param string $c             character
     * @param array  $keyboardArray keyboard array
     * @return array
     * @throws Exception
     */
    public static function getCharacterCoord($c, $keyboardArray)
    {
        foreach ($keyboardArray as $r) {
            if (in_array($c, $r)) {
                $row = array_search($r, $keyboardArray);
                $column = array_search($c, $r);

                return [$row, $column];
            }
        }

        throw new Exception($c . " not found in given keyboard layout");
    }

    /**
     * Finds the Euclidean distance between two characters, regardless of whether
     * they're shifted or not.
     * https://en.wikipedia.org/wiki/Euclidean_distance
     *
     * @param string $c1 character 1
     * @param string $c2 character 2
     * @return float
     * @throws Exception
     */
    public static function euclideanKeyboardDistance($c1, $c2)
    {
        $coord1 = self::getCharacterCoord($c1, self::arrayForChar($c1));
        $coord2 = self::getCharacterCoord($c2, self::arrayForChar($c2));

        return (($coord1[0] - $coord2[0]) ** 2 + ($coord1[1] - $coord2[1]) ** 2) ** (0.5);
    }

    /**
     * The cost of inserting c at position i in string s
     *
     * @param string $s string
     * @param int    $i index
     * @param string $c character
     * @return float
     * @throws Exception
     */
    public static function insertionCost($s, $i, $c)
    {
        if (!$s || $i >= strlen($s)) {
            return self::INSERTION_COST;
        }

        $cost = self::INSERTION_COST;

        if (self::arrayForChar(mb_substr($s, $i, 1)) !== self::arrayForChar($c)) {
            /*
             * We weren't holding down the shift key when we were typing the original
             * string, but started holding it down while inserting this character, or
             * vice versa. Either way, this action should have a higher cost.
             */
            $cost += self::SHIFT_COST;
        }

        $cost += self::euclideanKeyboardDistance(mb_substr($s, $i, 1), $c);

        return $cost;
    }

    /**
     * Return deletion cost
     *
     * @return float
     */
    public static function deletionCost()
    {
        return self::DELETION_COST;
    }

    /**
     * Calculate substitution cost
     *
     * @param string $s string
     * @param int    $i index
     * @param string $c character
     * @return float
     * @throws Exception
     */
    public static function substitutionCost($s, $i, $c)
    {
        $cost = self::SUBSTITUTION_COST;

        if (strlen($s) === 0 || $i >= strlen($s)) {
            return self::INSERTION_COST;
        }
        if (self::arrayForChar(mb_substr($s, $i, 1)) !== self::arrayForChar($c)) {
            /*
             * We weren't holding down the shift key when we were typing the original
             * string, but started holding it down while inserting this character, or
             * vice versa. Either way, this action should have a higher cost.
             */
            $cost += self::SHIFT_COST;
        }
        $cost += self::euclideanKeyboardDistance(mb_substr($s, $i, 1), $c);

        return $cost;
    }

    /**
     * Declare and fill a 2d array with 0s
     *
     * @param int $m     columns
     * @param int $n     rows
     * @param int $value fill value
     * @return array
     */
    public static function declare($m, $n, $value = 0)
    {
        return array_fill(0, $m, array_fill(0, $n, $value));
    }

    /**
     * Finds the typo distance (a floating point number) between two strings, based
     * on the canonical Levenshtein distance algorithm.
     *
     * @param string $string1 string 1
     * @param string $string2 string 2
     * @return float typo distance
     * @throws Exception
     */
    public static function typoDistance($string1, $string2)
    {
        // generates a matrix
        $matrix = self::declare(strlen($string1) + 2, strlen($string2) + 2);

        $strLen1 = strlen($string1);
        $strLen2 = strlen($string2);

        for ($i = 0; $i <= $strLen1 + 1; $i++) {
            $sum = 0;
            for ($j = 0; $j <= $strLen2 + 1; $j++) {
                $sum += self::deletionCost();
            }
            $matrix[$i][0] = $sum;
        }

        for ($i = 0; $i <= $strLen2 + 1; $i++) {
            $intermediateString = "";
            $cost = 0.0;
            for ($j = 0; $j < $i; $j++) {
                $cost += self::insertionCost($intermediateString, $j - 1, mb_substr($string2, $j - 1, 1));
                $intermediateString = $intermediateString . mb_substr($string2, $j - 1, 1);
            }
            $matrix[0][$i] = $cost;
        }

        for ($j = 1; $j < $strLen2 + 1; $j++) {
            for ($i = 1; $i < $strLen1 + 1; $i++) {
                if (mb_substr($string1, $i - 1, 1) === mb_substr($string2, $j - 1, 1)) {
                    $matrix[$i][$j] = $matrix[$i - 1][$j - 1];
                } else {
                    $delCost = self::deletionCost();
                    $insertCost = self::insertionCost($string1, $i, mb_substr($string2, $j - 1, 1));
                    $subCost = self::substitutionCost($string1, $i - 1, mb_substr($string2, $j - 1, 1));
                    $matrix[$i][$j] = min($matrix[$i - 1][$j] + $delCost, $matrix[$i][$j - 1] + $insertCost, $matrix[$i - 1][$j - 1] + $subCost);
                }
            }
        }

        return $matrix[$strLen1][$strLen2];
    }
}
