#!/usr/bin/php -q
<?PHP
/*
  xl.copy.void - remove unused key entries in foreign language files
  Copyright @2021, Bergware International.
*/

$rootdir = "/boot/unraid/languages";
$native  = glob("$rootdir/lang-en_US/*.txt",GLOB_NOSORT);
$foreign = array_filter(glob("$rootdir/*",GLOB_ONLYDIR|GLOB_NOSORT),'foreign');

function foreign($lang) {
  return strpos($lang,'en_US')===false;
}
function escapeQuotes($text) {
  return str_replace(["\"\n",'"'],["\" \n",'\"'],$text);
}
function parse_lang_file($file) {
  return file_exists($file) ? parse_ini_string(preg_replace(['/^(null|yes|no|true|false|on|off|none)=/mi','/^([^>].*?)=(.*)$/m','/^:(.+_(help|plug)):$/m','/^:end$/m'],['$1.=','$1="$2"','_$1="','"'],escapeQuotes(file_get_contents($file)))) : [];
}
function is_label($row) {
  return $row[0]=='_' && in_array(end(explode('_',$row)),['help','plug']);
}
function screen($text,&$array) {
  echo preg_replace(["/( => )?Array\n\s*\(/m","/^\s*\)\n/m","/=> /"],'',$text.print_r($array,true));
}
// loop thru native language files
foreach ($native as $file) {
  $name = basename($file);
  if ($name=='helptext.txt') continue;
  $source = parse_lang_file($file);
  if (!count($source)) continue;
  // compare with foreign language files
  foreach ($foreign as $language) {
    $output = "$language/$name";
    $target = parse_lang_file($output);
    $void   = [];
    // make list of entries to remove
    foreach ($target as $row => $text) if (!isset($source[$row])) {
      if (is_label($row)) {
        $label = substr($row,1);
        $void[] = "/^:$label:\\\$/,/^:end\\\$/d";
      } else {
        $void[] = "/^$row=.*/d";
      }
    }
    $size = count($void);
    if ($size) {
      echo "Updating: $output ($size)\n";
      foreach ($void as $row) exec("sed -ri \"$row\" $output");
    }
  }
}
?>
