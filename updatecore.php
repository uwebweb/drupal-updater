<?php
// 2019-01-17
// >= PHP 5.1.0
// Drupal core 8.6.7
// Drupal core 7.63

//Setting
$latest_drupal8_url="https://ftp.drupal.org/files/projects/drupal-8.6.7.zip";
$folders_to_copy_v8=[
  'sites/default/files',
  'core/themes',
  'modules'
];
$files_to_copy_v8=[
  'sites/default/settings.php',
  '.htaccess',
  'robots.txt',
  'web.config'
];
$latest_drupal7_url="https://ftp.drupal.org/files/projects/drupal-7.63.zip";
$folders_to_copy_v7=[
  'sites/default/files',
  'sites/all',
];
$files_to_copy_v7=[
  'sites/default/settings.php',
  '.htaccess',
  'robots.txt',
  'web.config'
];

///////Auto detect version
$drupal_version=v_auto_detect();
$webrootname=basename(__DIR__);
$backup_filename='../'.$webrootname.'_'.date('Ymd_His');
if($drupal_version!=8 && $drupal_version!=7){
  echo 'Drupal version is not supported'.'<br/>';
  exit();
}

///////download drupal core
if($drupal_version==8)
  $latest_drupal_url=$latest_drupal8_url;
else if($drupal_version==7)
  $latest_drupal_url=$latest_drupal7_url;
$target_path='../latest_drupal_core.zip';
if(!downloadFile($latest_drupal_url,$target_path))
{
  echo 'Error while downloading Drupal core file'.'<br/>';
  exit();
}
if(!file_exists($target_path))
{
  echo 'Error while downloading Drupal core file. Please check whether the parent folder is writeable.'.'<br/>';
  exit();
}

///////unzip
$unzip_path='../latest_drupal_core';
if(!unzip($unzip_path,$target_path))
  exit();
unlink($target_path);

///////copy necessary files and folders
$unzipped_path=$unzip_path.'/'.preg_replace('/\\.[^.\\s]{3,4}$/', '', basename($latest_drupal_url));
if($drupal_version==8)
{
  $files_to_copy=$files_to_copy_v8;
  $folders_to_copy=$folders_to_copy_v8;
}
elseif($drupal_version==7)
{
  $files_to_copy=$files_to_copy_v7;
  $folders_to_copy=$folders_to_copy_v7;
}

foreach ($files_to_copy as $file) {
  if(!copy($file,$unzipped_path.'/'.$file))
  {
    echo 'There was a problem while copying '.$file.' to '.$unzipped_path.'/'.$file.'<br/>';
    exit();
  }
}
foreach ($folders_to_copy as $folder) {
  full_copy($folder,$unzipped_path.'/'.$folder);
}

///////// make a backup file
if(!rename('../'.$webrootname,$backup_filename))
{
  echo 'Error: Cannot create backup file'.'<br/>';
  exit();
}

///////// update completed, move new version to webroot
if(!rename($unzipped_path,'../'.$webrootname))
{
  //rollback
  if(!rename($backup_filename,'../'.$webrootname))
  {
    echo 'Error: Cannot move files to webroot'.'<br/>';
  }
  else
    echo 'Critical error!!! Cannot move files to webroot, please recover the webroot folder manually.'.'<br/>';
  exit();
}

//////////End
rmdir($unzip_path);
echo 'Update core finished'.'<br/>';
echo 'Go to <a href="/update.php">update.php</a><br/>';
exit();

////////////////////////////////////////////////////////////////////////////////////////////////
function v_auto_detect(){
  if(file_exists('sites/all/themes'))
  {
    echo 'Detected Version: 7<br/>';
    return 7;
  }
  else if(file_exists('core'))
  {
    echo 'Detected Version: 8<br/>';
    return 8;
  }

  return false;
}

function unzip($outpath,$zippath)
{
  $unzip = new ZipArchive;
  $out = $unzip->open($zippath);
  if ($out === TRUE) {
    $unzip->extractTo($outpath);
    $unzip->close();
    return true;
  } else {
    echo 'Error while unzipping the file.'.PHP_EOL;
    return false;
  }
}
function downloadFile($url, $path)
{
    $newfname = $path;
    $file = fopen ($url, 'rb');
    if ($file) {
        @unlink($newfname);
        $newf = fopen ($newfname, 'wb');
        if ($newf) {
            while(!feof($file)) {
                if(!fwrite($newf, fread($file, 1024 * 8), 1024 * 8))
                {
                  echo 'Cannot write to the path '.$path.'<br/>';
                }
            }
        }
        else {
          echo 'Cannot create the path '.$path.'<br/>';
        }
    }
    else{
      return false;
    }

    if ($file) {
        fclose($file);
    }
    if ($newf) {
        fclose($newf);
    }
    return true;
}
function full_copy( $source, $target ) {
    if ( is_dir( $source ) ) {
        @mkdir( $target );
        $d = dir( $source );
        while ( FALSE !== ( $entry = $d->read() ) ) {
            if ( $entry == '.' || $entry == '..' ) {
                continue;
            }
            $Entry = $source . '/' . $entry;
            if ( is_dir( $Entry ) ) {
                full_copy( $Entry, $target . '/' . $entry );
                continue;
            }
            copy( $Entry, $target . '/' . $entry );
        }

        $d->close();
    }else {
        copy( $source, $target );
    }
}
?>
