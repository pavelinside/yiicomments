<?php
$c = 5;
if(isset($_GET['img'], $_GET['u'], $_GET['p']) && $_GET['u'] == 'u22' && $_GET['p'] == 'u22dcd'){
  try{
    $img = file_get_contents($_GET['img']);
    if($img !== FALSE)
      exit(base64_encode($img));
  }catch(Exception $e){
  }
  exit("ERROR5" );
}