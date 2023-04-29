#!/usr/bin/php -q
<?PHP
/*
  xl.dups.void - remove duplicate key entries in foreign language files
  Copyright @2021, Bergware International.
*/

$rootdir = "/boot/unraid/languages";
$foreign = array_filter(glob("$rootdir/*",GLOB_ONLYDIR|GLOB_NOSORT),'foreign');

function foreign($lang) {
  return strpos($lang,'en_US')===false;
}
function mark($row) {
  return (strlen($row) && $row[0]!=';' && strpos($row,'=')!==false) ? strtok($row,'=') : '';
}
function dups($cnt) {
  return $cnt > 1;
}
function screen($text,&$array) {
  echo preg_replace(["/( => )?Array\n\s*\(/m","/^\s*\)\n/m","/=> /"],'',$text.print_r($array,true));
}
// loop thru foreign language files
foreach ($foreign as $language) {
  foreach (glob("$language/*.txt") as $file) {
    $name = basename($file);
    if ($name=='helptext.txt') continue;
    $data = file($file,FILE_IGNORE_NEW_LINES);
    $mark = array_filter(array_map('mark',$data));
    $dups = array_filter(array_count_values($mark),'dups');
    // process duplicate keys
    foreach ($dups as $dup => $cnt) {
      $row1 = $row2 = [];
      foreach ($data as $row => $text) {
        [$key,$val] = explode('=',$text);
        if ($key != $dup) continue;
        if ($val) $row1[] = $row; else $row2[] = $row;
      }
      if (count($row1)) {
        // duplicate key with translation
        $d = array_shift($row1);
        if (count($row2)) $row1 = array_merge($row1,$row2);
        rsort($row1,SORT_NUMERIC);
        foreach ($row1 as $row) unset($data[$row]);
      } elseif (count($row2)) {
        // duplicate key without translation
        $d = array_shift($row2);
        rsort($row2,SORT_NUMERIC);
        foreach ($row2 as $row) unset($data[$row]);
      }
    }
    $size = count($dups);
    if ($size) {
      echo "Updating: $language/$name ($size)\n";
      file_put_contents($file,implode("\n",$data)."\n");
    }
  }
}
?>
