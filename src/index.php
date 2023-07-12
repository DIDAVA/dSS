<?php
$datadir = __DIR__ . "/data";
$streamdir = "$datadir/stream";
$staticdir = "$datadir/static";
foreach ([$datadir, $streamdir, $staticdir] as $dir) {
  if (!is_dir($dir)) mkdir($dir);
}

try {
  $path = explode('/', $_GET['path']);
  $mode = $path[0] ?? null;
  $id = $path[1] ?? null;
  if (!in_array($mode, ['static','stream'])) throw new Exception('invalid request mode', 400);
  if (empty($id)) throw new Exception('invalid media id', 400);
  $file = $mode == 'stream' ? "$streamdir/$id" : "$staticdir/$id";
  if (!is_file($file)) throw new Exception('media not found');
  $size = filesize($file);
  $mime = mime_content_type($file);
  header("Content-Type: $mime");
  if ($mode == 'static') {
    http_response_code(200);
    header("Content-Length: $size");
    $data = file_get_contents($file);
    echo $data;
  }
  else {
    if (empty($_SERVER['HTTP_RANGE'])) {
      http_response_code(200);
      header("Accept-Range: bytes");
    }
    else {
      $chunk = 100*1024;
      $range = str_replace('bytes=', '', $_SERVER['HTTP_RANGE']);
      list($start) = explode('-', $range, 1);
      $start = (int) $start;
      $max = $start + $chunk;
      $end = $max > $size ? $size : $max;
      http_response_code(206);
      header("Content-Type: $mime");
      header("Content-Range: bytes $start-$end/$size");
      echo file_get_contents($file, null, null, $start, $end);
    }
  }
}
catch (Exception $e) {
  $code = $e->getCode();
  $message = $e->getMessage();
  http_response_code($code);
  echo $message;
}