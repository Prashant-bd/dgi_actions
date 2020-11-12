<?php

namespace Drupal\dgi_actions_ark_identifier\Plugin\Action;

use Drupal\dgi_actions_ark_identifier\Utility\EzidTextParser;
use Drupal\dgi_actions\Plugin\Action\DeleteIdentifier;
use Drupal\dgi_actions\Utility\IdentifierUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Drupal\Core\Config\ConfigFactory;
use Psr\Log\LoggerInterface;
use Drupal\Core\State\State;

/**
 * Deletes an ARK Identifier Record on CDL EZID.
 *
 * @Action(
 *   id = "dgi_actions_delete_ark_identifier",
 *   label = @Translation("Delete ARK EZID Identifier"),
 *   type = "entity"
 * )
 */
class DeleteArkIdentifier extends DeleteIdentifier {

  // @codingStandardsIgnoreStart

  /**
   * CDL EZID Text Parser.
   *
   * @var \Drupal\dgi_actions\Utilities\EzidTextParser
   */
  protected $ezidParser;

  /**
   * State.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\Client $client
   *   Http Client connection.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config Factory.
   * @param \Drupal\dgi_actions\Utilities\IdentifierUtils $utils
   *   Identifier utils.
   * @param \Drupal\dgi_actions\Utilities\EzidTextParser $ezid_parser
   *   CDL EZID Text parser.
   * @param \Drupal\Core\State\State $state
   *   State API.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Client $client,
    LoggerInterface $logger,
    ConfigFactory $config_factory,
    IdentifierUtils $utils,
    EzidTextParser $ezid_parser,
    State $state
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $client, $logger, $config_factory, $utils);
    $this->ezidParser = $ezid_parser;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('logger.channel.dgi_actions'),
      $container->get('config.factory'),
      $container->get('dgi_actions.utils'),
      $container->get('dgi_actions.ezidtextparser'),
      $container->get('state')
    );
  }

  // @codingStandardsIgnoreEnd

  /**
   * {@inheritdoc}
   */
  protected function getRequestType() {
    return 'DELETE';
  }

  /**
   * {@inheritdoc}
   */
  protected function getUri() {
    $identifier = $this->getIdentifierFromEntity();

    return $identifier;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestParams() {
    $creds = $this->state->get($this->serviceDataConfig->get('data.state_key'));
    $requestParams = [
      'auth' => [
        $creds['username'],
        $creds['password'],
      ],
    ];

    return $requestParams;
  }

  /**
   * {@inheritdoc}
   */
  protected function handleResponse(Response $response) {
    $contents = $response->getBody()->getContents();
    $filteredResponse = $this->ezidParser->parseEzidResponse($contents);

    if (array_key_exists('success', $filteredResponse)) {
      $this->logger->info('ARK Identifier Deleted: @contents', ['@contents' => $contents]);
    }
    else {
      $this->logger->error('There was an issue deleting the ARK Identifier: @contents', ['@contents' => $contents]);
    }
  }

}
