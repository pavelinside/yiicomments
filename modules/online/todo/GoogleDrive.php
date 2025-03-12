<?php
// app/Controller/LordsController.php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment as Render;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Google;

class GoogleDrive
{
  /**
   * @var Render
   */
  private Render $render;

  public function __construct(Render $render)
  {
    $this->render = $render;
  }

  /**
   * @param ServerRequestInterface $request
   * @param ResponseInterface $response
   * @return ResponseInterface
   * @throws LoaderError
   * @throws RuntimeError
   * @throws SyntaxError
   */
  public function show(ServerRequestInterface $request, ResponseInterface $response)
  {
    /*
    function isWebRequest()
    {
      return isset($_SERVER['HTTP_USER_AGENT']);
    }

    function pageHeader($title)
    {
      $ret = "<!doctype html>
  <html>
  <head>
    <title>" . $title . "</title>
    <link href='styles/style.css' rel='stylesheet' type='text/css' />
  </head>
  <body>\n";
      if ($_SERVER['PHP_SELF'] != "/index.php") {
        $ret .= "<p><a href='index.php'>Back</a></p>";
      }
      $ret .= "<header><h1>" . $title . "</h1></header>";

      // Start the session (for storing access tokens and things)
      if (!headers_sent()) {
        session_start();
      }

      return $ret;
    }


    function pageFooter($file = null)
    {
      $ret = "";
      if ($file) {
        $ret .= "<h3>Code:</h3>";
        $ret .= "<pre class='code'>";
        $ret .= htmlspecialchars(file_get_contents($file));
        $ret .= "</pre>";
      }
      $ret .= "</html>";

      return $ret;
    }

    function missingApiKeyWarning()
    {
      $ret = "
    <h3 class='warn'>
      Warning: You need to set a Simple API Access key from the
      <a href='http://developers.google.com/console'>Google API console</a>
    </h3>";

      return $ret;
    }

    function missingClientSecretsWarning()
    {
      $ret = "
    <h3 class='warn'>
      Warning: You need to set Client ID, Client Secret and Redirect URI from the
      <a href='http://developers.google.com/console'>Google API console</a>
    </h3>";

      return $ret;
    }

    function missingServiceAccountDetailsWarning()
    {
      $ret = "
    <h3 class='warn'>
      Warning: You need download your Service Account Credentials JSON from the
      <a href='http://developers.google.com/console'>Google API console</a>.
    </h3>
    <p>
      Once downloaded, move them into the root directory of this repository and
      rename them 'service-account-credentials.json'.
    </p>
    <p>
      In your application, you should set the GOOGLE_APPLICATION_CREDENTIALS environment variable
      as the path to this file, but in the context of this example we will do this for you.
    </p>";

      return $ret;
    }

    function missingOAuth2CredentialsWarning()
    {
      $ret = "
    <h3 class='warn'>
      Warning: You need to set the location of your OAuth2 Client Credentials from the
      <a href='http://developers.google.com/console'>Google API console</a>.
    </h3>
    <p>
      Once downloaded, move them into the root directory of this repository and
      rename them 'oauth-credentials.json'.
    </p>";

      return $ret;
    }

    function invalidCsrfTokenWarning()
    {
      $ret = "
    <h3 class='warn'>
      The CSRF token is invalid, your session probably expired. Please refresh the page.
    </h3>";

      return $ret;
    }

    function checkServiceAccountCredentialsFile()
    {
      // service account creds
      $application_creds = __DIR__ . '/../../service-account-credentials.json';

      return file_exists($application_creds) ? $application_creds : false;
    }

    function getCsrfToken()
    {
      if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
      }

      return $_SESSION['csrf_token'];
    }

    function validateCsrfToken()
    {
      return isset($_REQUEST['csrf_token'])
        && isset($_SESSION['csrf_token'])
        && $_REQUEST['csrf_token'] === $_SESSION['csrf_token'];
    }

    function getOAuthCredentialsFile()
    {
      // oauth2 creds
      $oauth_creds = __DIR__ . '/../../oauth-credentials.json';

      if (file_exists($oauth_creds)) {
        return $oauth_creds;
      }

      return false;
    }

    function setClientCredentialsFile($apiKey)
    {
      $file = __DIR__ . '/../../tests/.apiKey';
      file_put_contents($file, $apiKey);
    }


    function getApiKey()
    {
      $file = __DIR__ . '/../../tests/.apiKey';
      if (file_exists($file)) {
        return file_get_contents($file);
      }
    }

    function setApiKey($apiKey)
    {
      $file = __DIR__ . '/../../tests/.apiKey';
      file_put_contents($file, $apiKey);
    }
    //$client->addScope(\Google_Service_Drive::DRIVE);

    $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

    $client = new Google\Client();
    $client->setAuthConfig(__DIR__.DIRECTORY_SEPARATOR.'credentials.json');
    //$client->setAuthConfig($oauth_credentials);
    $client->setRedirectUri($redirect_uri);
    $client->addScope("https://www.googleapis.com/auth/drive");
    $service = new \Google_Service_Drive($client);

    $authUrl = $client->createAuthUrl();

    // This is uploading a file directly, with no metadata associated.
    $file = new \Google_Service_Drive_DriveFile();
    $result = $service->files->create(
      $file,
      array(
        'data' => 'test',
        'mimeType' => 'application/octet-stream',
        'uploadType' => 'media'
      )
    );

    // Now lets try and send the metadata as well using multipart!
    $file = new \Google_Service_Drive_DriveFile();
    $file->setName("Hello World!");
    $result2 = $service->files->create(
      $file,
      array(
        'data' => 'test2',
        'mimeType' => 'application/octet-stream',
        'uploadType' => 'multipart'
      )
    ); */

    //$client->setSubject($user_to_impersonate);


    $response->getBody()->write('hh');

    //$name = $request->getAttribute('name');
    //$response->getBody()->write($this->render->render('googledrive/drive.twig', []));
    return $response;
  }
}