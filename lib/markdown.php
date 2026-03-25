<?php
declare(strict_types=1);

/**
 * Petit convertisseur Markdown -> HTML (support volontairement limité).
 *
 * Objectif hackathon : garder du "Markdown" simple sans dépendances.
 * - titres : #, ##, ###
 * - gras : **texte**
 * - italique : *texte*
 * - code inline : `code`
 * - liens : [texte](https://...)
 */
function render_markdown(string $markdown): string
{
  $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
  $escaped = htmlspecialchars($markdown, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

  // Lignes (pour gérer les titres).
  $lines = explode("\n", $escaped);
  foreach ($lines as &$line) {
    $line = preg_replace_callback('/^(#{1,3})\s*(.+)$/', function ($m) {
      $level = strlen($m[1]);
      $tag = match ($level) {
        1 => 'h1',
        2 => 'h2',
        default => 'h3',
      };
      return '<' . $tag . '>' . $m[2] . '</' . $tag . '>';
    }, $line);
  }
  unset($line);

  $html = implode("\n", $lines);

  // Liens
  $html = preg_replace(
    '/\[(.+?)\]\((https?:\/\/[^\)]+)\)/',
    '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>',
    $html
  );

  // Code inline
  $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);

  // Gras
  $html = preg_replace('/\*\*([^\*]+)\*\*/', '<strong>$1</strong>', $html);

  // Italique (éviter la collision avec gras)
  $html = preg_replace('/(^|[^*])\*([^*\n]+)\*/', '$1<em>$2</em>', $html);

  // Retours ligne => <br> sauf si on a déjà des blocs (h1/h2/h3).
  $segments = preg_split('/\n{2,}/', $html);
  $out = [];
  foreach ($segments as $seg) {
    $seg = str_replace("\n", "<br>\n", $seg);
    $out[] = $seg;
  }

  return implode("\n<br>\n", $out);
}

function markdown_snippet(string $markdown, int $maxChars = 200): string
{
  // Transformer en texte lisible (sans HTML).
  $text = str_replace(["\r\n", "\r"], "\n", $markdown);
  $text = preg_replace('/\[(.*?)\]\((https?:\/\/[^\)]+)\)/', '$1', (string)$text);
  $text = preg_replace('/\*\*([^*]+)\*\*/', '$1', (string)$text);
  $text = preg_replace('/\*([^*\n]+)\*/', '$1', (string)$text);
  $text = preg_replace('/`([^`]+)`/', '$1', (string)$text);
  $text = preg_replace('/^#{1,3}\s*/m', '', (string)$text);
  $text = preg_replace('/\s+/', ' ', trim((string)$text));

  if (mb_strlen($text) <= $maxChars) {
    return $text;
  }
  return mb_substr($text, 0, $maxChars - 1) . '…';
}

