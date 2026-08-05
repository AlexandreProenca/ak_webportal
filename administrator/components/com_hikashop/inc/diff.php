<?php
/**
 * @package	HikaShop
 * @version	6.5.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2026 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or defined('ABSPATH') or die('Restricted access');
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?><?php


class HikashopDiffInc{

  const UNMODIFIED = 0;
  const DELETED    = 1;
  const INSERTED   = 2;

  public static function compare(
      $string1, $string2, $compareCharacters = false){

    if(!$compareCharacters){
      $string1 = str_replace(array("\r\n", "\r"), "\n", $string1);
      $string2 = str_replace(array("\r\n", "\r"), "\n", $string2);
    }

    $start = 0;
    if ($compareCharacters){
      $sequence1 = $string1;
      $sequence2 = $string2;
      $end1 = strlen($string1) - 1;
      $end2 = strlen($string2) - 1;
    }else{
      $sequence1 = explode("\n", $string1);
      $sequence2 = explode("\n", $string2);
      $end1 = count($sequence1) - 1;
      $end2 = count($sequence2) - 1;
    }

    while ($start <= $end1 && $start <= $end2
        && $sequence1[$start] == $sequence2[$start]){
      $start ++;
    }

    while ($end1 >= $start && $end2 >= $start
        && $sequence1[$end1] == $sequence2[$end2]){
      $end1 --;
      $end2 --;
    }

    $table = self::computeTable($sequence1, $sequence2, $start, $end1, $end2);

    $partialDiff =
        self::generatePartialDiff($table, $sequence1, $sequence2, $start);

    $diff = array();
    for ($index = 0; $index < $start; $index ++){
      $diff[] = array($sequence1[$index], self::UNMODIFIED);
    }
    while (count($partialDiff) > 0) $diff[] = array_pop($partialDiff);
    for ($index = $end1 + 1;
        $index < ($compareCharacters ? strlen($sequence1) : count($sequence1));
        $index ++){
      $diff[] = array($sequence1[$index], self::UNMODIFIED);
    }

    return $diff;

  }

  public static function compareFiles(
      $file1, $file2, $compareCharacters = false){

    return self::compare(
        file_get_contents($file1),
        file_get_contents($file2),
        $compareCharacters);

  }

  private static function computeTable(
      $sequence1, $sequence2, $start, $end1, $end2){

    $length1 = $end1 - $start + 1;
    $length2 = $end2 - $start + 1;

    $table = array(array_fill(0, $length2 + 1, 0));

    for ($index1 = 1; $index1 <= $length1; $index1 ++){

      $table[$index1] = array(0);

      for ($index2 = 1; $index2 <= $length2; $index2 ++){

        if ($sequence1[$index1 + $start - 1]
            == $sequence2[$index2 + $start - 1]){
          $table[$index1][$index2] = $table[$index1 - 1][$index2 - 1] + 1;
        }else{
          $table[$index1][$index2] =
              max($table[$index1 - 1][$index2], $table[$index1][$index2 - 1]);
        }

      }
    }

    return $table;

  }

  private static function generatePartialDiff(
      $table, $sequence1, $sequence2, $start){

    $diff = array();

    $index1 = count($table) - 1;
    $index2 = count($table[0]) - 1;

    while ($index1 > 0 || $index2 > 0){

      if ($index1 > 0 && $index2 > 0
          && $sequence1[$index1 + $start - 1]
              == $sequence2[$index2 + $start - 1]){

        $diff[] = array($sequence1[$index1 + $start - 1], self::UNMODIFIED);
        $index1 --;
        $index2 --;

      }elseif ($index2 > 0
          && $table[$index1][$index2] == $table[$index1][$index2 - 1]){

        $diff[] = array($sequence2[$index2 + $start - 1], self::INSERTED);
        $index2 --;

      }else{

        $diff[] = array($sequence1[$index1 + $start - 1], self::DELETED);
        $index1 --;

      }

    }

    return $diff;

  }

  public static function toString($diff, $separator = "\n"){

    $string = '';

    foreach ($diff as $line){

      switch ($line[1]){
        case self::UNMODIFIED : $string .= '  ' . $line[0];break;
        case self::DELETED    : $string .= '- ' . $line[0];break;
        case self::INSERTED   : $string .= '+ ' . $line[0];break;
      }

      $string .= $separator;

    }

    return $string;

  }

  public static function toHTML($diff, $separator = '<br/>'){

    $html = '';

    foreach ($diff as $line){

      switch ($line[1]){
        case self::UNMODIFIED : $element = 'span'; break;
        case self::DELETED    : $element = 'del';  break;
        case self::INSERTED   : $element = 'ins';  break;
      }
      $html .=
          '<' . $element . '>'
          . htmlspecialchars($line[0])
          . '</' . $element . '>';

      $html .= $separator;

    }

    return $html;

  }

  public static function toTable($diff, $indentation = '', $separator = '<br/>'){
    $html = $indentation . "<table class=\"hikadiff\">\n";

    $lineLeft = 0;
    $lineRight = 0;
    $index = 0;
    $contextLines = 3;
    $isFirstBlock = true;

    while($index < count($diff)){

      if($diff[$index][1] === 3){
        $html .= $indentation . "  <tr class=\"hikadiff-header\">\n"
            . $indentation . "    <td colspan=\"2\">" . $diff[$index][0] . "</td>\n"
            . $indentation . "    <td colspan=\"2\">" . $diff[$index][2] . "</td>\n"
            . $indentation . "  </tr>\n";
        $index++;
        $lineLeft = 1;
        $lineRight = 1;
        continue;
      }

      if($diff[$index][1] == self::UNMODIFIED){
        $block = array();
        while($index < count($diff) && $diff[$index][1] == self::UNMODIFIED){
          $block[] = $diff[$index][0];
          $index++;
        }

        $blockSize = count($block);
        $isLastBlock = ($index >= count($diff));
        $threshold = $contextLines * 2 + 1;

        $skipStart = -1;
        $skipEnd = -1;

        if($isFirstBlock && $isLastBlock){
        }elseif($isFirstBlock){
          if($blockSize > $contextLines + 1){
            $skipStart = 0;
            $skipEnd = $blockSize - $contextLines;
          }
        }elseif($isLastBlock){
          if($blockSize > $contextLines + 1){
            $skipStart = $contextLines;
            $skipEnd = $blockSize;
          }
        }else{
          if($blockSize > $threshold){
            $skipStart = $contextLines;
            $skipEnd = $blockSize - $contextLines;
          }
        }

        for($i = 0; $i < $blockSize; $i++){
          if($skipStart >= 0 && $i == $skipStart){
            $skipped = $skipEnd - $skipStart;
            $html .= $indentation . "  <tr class=\"hikadiff-skip\"><td colspan=\"4\">"
                . "&#8943; " . JText::sprintf('X_UNCHANGED_LINES', $skipped) . " &#8943;"
                . "</td></tr>\n";
            $lineLeft += $skipped;
            $lineRight += $skipped;
            $i = $skipEnd;
            if($i >= $blockSize)
              break;
          }
          $html .= self::renderLine($block[$i], $block[$i], $lineLeft, $lineRight, 'unmodified', $indentation);
          $lineLeft++;
          $lineRight++;
        }

        $isFirstBlock = false;
        continue;
      }

      if($diff[$index][1] == self::DELETED){
        $deletedLines = array();
        while($index < count($diff) && $diff[$index][1] == self::DELETED){
          $deletedLines[] = $diff[$index][0];
          $index++;
        }
        $insertedLines = array();
        while($index < count($diff) && $diff[$index][1] == self::INSERTED){
          $insertedLines[] = $diff[$index][0];
          $index++;
        }

        $maxLines = max(count($deletedLines), count($insertedLines));
        for($i = 0; $i < $maxLines; $i++){
          $hasLeft = isset($deletedLines[$i]);
          $hasRight = isset($insertedLines[$i]);

          if($hasLeft && $hasRight)
            $type = 'modified';
          elseif($hasLeft)
            $type = 'deleted';
          else
            $type = 'inserted';

          $html .= self::renderLine(
              $hasLeft ? $deletedLines[$i] : null,
              $hasRight ? $insertedLines[$i] : null,
              $hasLeft ? $lineLeft : null,
              $hasRight ? $lineRight : null,
              $type,
              $indentation
          );

          if($hasLeft) $lineLeft++;
          if($hasRight) $lineRight++;
        }

        $isFirstBlock = false;
        continue;
      }

      if($diff[$index][1] == self::INSERTED){
        $html .= self::renderLine(null, $diff[$index][0], null, $lineRight, 'inserted', $indentation);
        $lineRight++;
        $index++;
        $isFirstBlock = false;
        continue;
      }

      $index++;
    }

    return $html . $indentation . "</table>\n";
  }

  private static function renderLine($leftContent, $rightContent, $leftNum, $rightNum, $type, $indentation){
    $html = $indentation . "  <tr class=\"hikadiff-row hikadiff-" . $type . "\">\n";

    $html .= $indentation . "    <td class=\"hikadiff-num hikadiff-num-left\">"
        . ($leftNum !== null ? $leftNum : '') . "</td>\n";

    $leftClass = 'hikadiff-code hikadiff-left';
    if($leftContent === null) $leftClass .= ' hikadiff-empty';
    $html .= $indentation . "    <td class=\"" . $leftClass . "\">"
        . ($leftContent !== null ? htmlspecialchars($leftContent) : '') . "</td>\n";

    $html .= $indentation . "    <td class=\"hikadiff-num hikadiff-num-right\">"
        . ($rightNum !== null ? $rightNum : '') . "</td>\n";

    $rightClass = 'hikadiff-code hikadiff-right';
    if($rightContent === null) $rightClass .= ' hikadiff-empty';
    $html .= $indentation . "    <td class=\"" . $rightClass . "\">"
        . ($rightContent !== null ? htmlspecialchars($rightContent) : '') . "</td>\n";

    $html .= $indentation . "  </tr>\n";
    return $html;
  }

}
