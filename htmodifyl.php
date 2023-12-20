<?php
$contents = file_get_contents('php://stdin');
$doc = new DOMDocument();
$doc->formatOutput = true;
$doc->loadHTML($contents);

if (empty($argv[1])) {
  exit('HTModifyL: No tag provided');
}
$tag = $argv[1];
$xpath = new DOMXPath($doc);

$nodes = $xpath->query($tag);
if (empty($argv[2])) {
  echo "Found ".count($nodes)." occurence(s)\n";
  foreach ($nodes as $node) {
    echo $node->ownerDocument->saveHTML($node);
  }
  exit;
}

$command = array_slice($argv, 2);
$has_braces = in_array('{}', $command);
foreach ($nodes as $node) {
  if ($has_braces) {
    $node->textContent = run_braces($command, $node->textContent);
  } else {
    $node->textContent = run_stdin($command, $node->textContent);
  }
}

echo $doc->saveHTML($doc);

function run_braces($parts, $txt) {
  $cmd = [];
  foreach ($parts as $part) {
    if ($part == '{}') {
      $cmd[] = escapeshellarg($txt);
    } else {
      $cmd[] = $part;
    }
  }
  echo implode(' ', $cmd);
  return shell_exec(implode(' ', $cmd));
}

function run_stdin($cmd, $txt) {
  $desc = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w']
  ];
  $proc = proc_open(implode(' ', $cmd), $desc, $pipes);
  if (!is_resource($proc)) {
    exit('Could not start cmd');
  }
  fwrite($pipes[0], $txt);
  fclose($pipes[0]);

  $ran = stream_get_contents($pipes[1]);

  if (proc_close($proc)) {
    exit('Proc closed with error');
  }
  return $ran;
}
