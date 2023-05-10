<?php

namespace Drupal\fogbugz_api\FogBugz;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\key\Entity\Key;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Utils;

/**
 * FogBugz API Manager.
 *
 * @package Drupal\fogbugz_api\FogBugz
 */
class ApiManager {
  /**
   * FogBugz API config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $fbConfig;

  /**
   * Key entity containing FogBugz username and password.
   *
   * @var \Drupal\key\Entity\Key
   */
  protected $fbKey;

  /**
   * FogBugz URL constructed from FogBugz API admin config setting.
   *
   * @var string
   */
  protected $fbUrl;

  /**
   * FogBugz API connection token.
   *
   * @var string
   */
  protected $fbToken;

  /**
   * HTTP Client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * FogBugz API constructor.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   A mutable config object.
   * @param \Drupal\key\Entity\Key|null $key
   *   A key entity containing FogBugz authorization information.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   *
   * @throws \Exception
   */
  public function __construct(ImmutableConfig $config, Key $key = NULL) {
    $this->httpClient = \Drupal::httpClient();
    $this->fbConfig = $config;

    $this->fbUrl = $this->fbConfig->get('your_fogbugz');
    if (empty($this->fbUrl) === TRUE) {
      throw new \Exception("Oops! Could not retrieve FogBugz settings - please have the Systems Administrator configure the FogBugz Settings admin form.");
    }
    else {
      $this->fbUrl .= '/api.asp';
    }

    $this->fbKey = $key;
    if (empty($this->fbKey) === TRUE) {
      throw new \Exception("Oops! Could not find FogBugz authentication info - please have the Systems Administrator configure the FogBugz API key.");
    }

    $this->getToken();
    if (empty($this->fbToken) === TRUE) {
      throw new \Exception("Oops! Could not obtain FogBugz token - please have the Systems Administrator check the FogBugz Settings configuration.");
    }
    else {
      // Debug info - Token history.
      \Drupal::logger('fogbugz_api')->notice('Created token: ' . $this->fbToken);
    }
  }

  /**
   * Declare a destructor.
   */
  public function __destruct() {
    $this->invalidateToken();
    // Debug info - Token history.
    \Drupal::logger('fogbugz_api')->notice('Invalidated token: ' . $this->fbToken);
  }


  /**
   * Return the FogBugz config object.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   */
  public function getConfig() {
    return $this->fbConfig;
  }

  /**
   * FogBugz API: Get the token string from endpoint.
   *
   * @return string|bool
   *   Alphanumeric 30-char string for the FogBugz session.
   */
  protected function getToken() {
    if ($this->fbToken === NULL) {
      $credentials = $this->getCredentials();
      $params = [
        'email' => $credentials['username'],
        'password' => $credentials['password'],
      ];
      $xml = $this->sendRequest('logon', $params);

      if ($xml === FALSE) {
        \Drupal::logger('fogbugz_api')->notice('Unable to fetch token');
        return FALSE;
      }
      $this->fbToken = (string) $xml->token;
    }

    return $this->fbToken;
  }

  /**
   * Retrieve the authentication credentials.
   *
   * @return array|bool
   *   Array containing "username" and "password".
   */
  protected function getCredentials() {
    try {
      return $this
        ->fbKey
        ->getKeyValues();
    }
    catch (\Exception $e) {
      \Drupal::logger('fogbugz_api')->notice('Caught getCredentials method exception: ' . $e->getMessage());
      return FALSE;
    }
  }

  /**
   * Log off / invalidate token.
   */
  protected function invalidateToken() {
    $this->sendRequest('logoff', []);
    $this->fbToken = NULL;
  }

  /**
   * Create a case in FogBugz system based on user input from a request form.
   *
   * @param array $params
   *   Array of params for creating the case.
   *
   * @return \Drupal\fogbugz_api\FogBugz\FogBugzCase|bool
   *   The Case object if case creation was successful, FALSE otherwise.
   */
  public function createCase(array $params) {
    $xml = $this->sendRequest('new', $params);

    if ($xml === FALSE) {
      return FALSE;
    }

    $case = FogBugzCase::createFromXml($xml->case);

    if(empty($case->getCaseId())) {
      return FALSE;
    }

    return $case;
  }

  /**
   * Adds a forward message event to an existing case.
   *
   * @param array $params
   *   Array of params for the forward message.
   *
   * @return bool
   *   TRUE if successful, FALSE otherwise.
   */
  public function addForwardEvent(array $params) {
    $xml = $this->sendRequest('forward', $params);

    if ($xml === FALSE) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Search FogBugz cases.
   *
   * @param string $query
   *   Query to use.
   *
   * @param string $cols
   *   Columns to return.
   *
   * @return $array
   *   Array of Case objects.
   */
  public function searchCases(string $query, string $cols = NULL) {
    $cols = $cols ?? 'sTitle,fOpen,dtOpened,dtClosed,sStatus,ixProject,sProject,ixCategory,sCategory,ixPriority,sPriority,ixMailbox,sCustomerEmail,events,tags,plugin_customfields_at_fogcreek_com_natureg119,plugin_customfields_at_fogcreek_com_alertxstatusw51d,plugin_customfields_at_fogcreek_com_alertxheaderv51b';
    $params = [
      'q' => $query,
      'cols' => $cols,
    ];

    $xml = $this->sendRequest('search', $params);
    if ($xml === FALSE) {
      return [];
    }

    $cases = [];
    foreach ($xml->cases->children() as $case) {
      $cases[] = FogBugzCase::createFromXml($case);
    }

    return $cases;
  }

  /**
   * Gets specified FogBugz case.
   *
   * @param int $id
   *   FogBugz case ID
   *
   * @return \Drupal\fogbugz_api\FogBugz\FogBugzCase
   *   Case object.
   */
  function getCase(int $id) {
    $cases = $this->searchCases($id);
    return empty($cases) ? NULL : $cases[0];
  }

  /**
   * Gets alerts from FogBugz.
   *
   * @param bool $linkOnly
   *   FogBugz cases with catalogue links.
   *
   * @return array
   *   Array of Case objects.
   */
  public function getActiveAlerts($linkOnly = FALSE) {
    $query = 'alertxdisplay:"yes" -status:"closed"';
    if ($linkOnly) {
      $query = 'alertxdisplay:"Yes and include link in catalogue tab" -status:"closed"';
    }

    $alerts = $this->searchCases($query);
    usort($alerts, function($a, $b) {
      return $b->getDateOpened() <=> $a->getDateOpened();
    });

    return $alerts;
  }

  /**
   * Send a request to FogBugz.
   *
   * @param string $command
   *   The Fogbugz command argument.
   * @param array $params
   *   Array of query parameters.
   *
   * @return \SimpleXMLElement|bool
   *   FALSE if failure, a SimpleXMLElement if successful.
   */
  public function sendRequest(string $command, array $params) {
    $url = $this->fbUrl;
    switch ($command) {
      case 'logon':
      case 'logoff':
      case 'forward':
      case 'new':
      case 'search':
        $url .= "?cmd=$command";
        break;

      default:
        return FALSE;
    }

    $postData = $this->prepPostParams($params);
    if ($command != 'logon') {
      $postData[] = ['name' => 'token', 'contents' => $this->fbToken];
    }

    try {
      $response = $this->httpClient->request('POST', $url, ['multipart' => $postData]);
    }
    catch (ClientException $e) {
      \Drupal::logger('fogbugz_api')->notice('Failed Request: ' . $e->getMessage());
      return FALSE;
    }

    try {
      $xml = new \SimpleXMLElement((string) $response->getBody(), LIBXML_NOCDATA);
    }
    catch (\Exception $e) {
      \Drupal::logger('fogbugz_api')->notice('Failed to Parse XML: ' . $e->getMessage());
      return FALSE;
    }

    return $xml;
  }

  /**
   * Map user supplied params to params for POST.
   *
   * @param array $params
   *   Array of user supplied  parameters.
   *
   * @return array
   *   Array of parameters for a POST request.
   */
  private function prepPostParams(array $params) {
    // An incomplete list of fields and their prefixes.
    // Note: there is a shortcut for keys ending in Id for the 'ix' prefix,
    // so only their string ('s') equivalents should be listed here.
    $prefixes = [
      'ix' => ['bug', 'mailbox', 'priority'],
      's' => ['project', 'category', 'title', 'customerEmail', 'from', 'to', 'CC', 'BCC', 'subject', 'event', 'tags', 'personAssignedTo'],
      'n' => ['filesCount'],
      'f' => ['open'],
      'dt' => ['opened', 'closed'],
    ];

    $postData = [];
    foreach ($params as $key => $value) {
      // Special case for keys ending in Id as a shortcut for "ix" prefix.
      // This useful because some keys can have matching string and
      // integer values (eg. sProject and ixProject).
      if (preg_match('/Id$/', $key)) {
        $newKey = 'ix' . ucfirst(substr($key, 0, -2));
        $postData[] = ['name' => $newKey, 'contents' => $value];
        continue;
      }

      // Special case for files.
      if ($key == 'files') {
        $postData[] = ['name' => 'nFilesCount', 'contents' => count($value)];
        for ($i = 0, $size = count($value); $i < $size; ++$i) {
          $postData[] = [
            'name' => "File{$i}",
            'filename' => basename($value[$i]),
            'contents' => Utils::tryFopen($value[$i], 'r'),
          ];
        }
        continue;
      }

      $match = FALSE;
      foreach ($prefixes as $prefix => $keys) {
        if (in_array($key, $keys)) {
          $newKey = $prefix . ucfirst($key);
          $postData[] = ['name' => $newKey, 'contents' => $value];
          $match = TRUE;
          break;
        }
      }

      // Pass along any unmatched keys unaltered.
      if (!$match) {
        $postData[] = ['name' => $key, 'contents' => $value];
      }
    }

    return $postData;
  }

}
