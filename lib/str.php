<?php
// /lib/str.php
declare(strict_types=1);

function str_slug(string $text): string {
  $text = trim(mb_strtolower($text,'UTF-8'));
  $text = iconv('UTF-8','ASCII//TRANSLIT',$text);
  $text = preg_replace('~[^a-z0-9]+~', '-', $text);
  $text = trim($text, '-');
  return $text ?: bin2hex(random_bytes(3));
}
