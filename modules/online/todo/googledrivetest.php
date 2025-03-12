<?php // phpinfo(); exit; // print_r($_SERVER); exit;
/*
php D:\sites\composer.phar require google/apiclient:"^2.7"
https://console.developers.google.com/ Включить Google Drive API
Учетные данные -> Создать учетные данные -> Сервисный аккаунт (vel01ServiceAccount)
Управление сервисными аккаунтам -> Создать ключ -> JSON
*/

// require "google/apiclient": "^2.7",

$client = new Google_Client();
$service_account_path = __DIR__.DIRECTORY_SEPARATOR.'service_account.json';
$client->setApplicationName('GetFiles');
putenv('GOOGLE_APPLICATION_CREDENTIALS='.$service_account_path);
$client->addScope(Google_Service_Drive::DRIVE);// условие
$client->useApplicationDefaultCredentials();

$folderId = '12pSkOOjTYpjjiTVDHV1LDFggaJ8Wk2iK';
//https://drive.google.com/file/d/1-9w0_POtzz6pPiPDDA_KDP1kbq70LRpA/view?usp=sharing
$imgId = '1-9w0_POtzz6pPiPDDA_KDP1kbq70LRpA';
$optParams = [
  'q' => "'" . $folderId . "' in parents",
  'fields' => 'files(id,name,size)'
];

$service = new Google_Service_Drive($client);

$results = $service->files->listFiles($optParams);

$files = $results->getFiles();
foreach($files as $key => $file){
  $id = $file->getId();
  $name = $file->getName();
  if($id){
    //$content = $service->files->get($id);
    header("Content-type: image/jpeg");

    $fileToDown = $service->files->get($id, array('alt' => 'media'));
    echo $fileToDown->getBody();

    //echo $content->getBody()->getContents();

//    $http = $client->authorize();
//    $response = $http->request(
//      'GET',
//      sprintf('/drive/v3/files/%s', $fileId),
//      [
//        'query' => ['alt' => 'media'],
//        'headers' => [
//          'Range' => sprintf('bytes=%s-%s', $chunkStart, $chunkEnd)
//        ]
//      ]
//    );
//    $chunkStart = $chunkEnd + 1;
    //fwrite($fp, $response->getBody()->getContents());

    break;
  }
}

//$createdFile = $service->files->create($file, array(
//  'data' => $data,
//  'mimeType' => $mimeType,
//));



//var_dump($results);

//echo "Hello";
exit;