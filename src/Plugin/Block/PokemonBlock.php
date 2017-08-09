<?php

namespace Drupal\pokemon_block\Plugin\Block;

use GuzzleHttp\Client;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;


/**
 * Provides a 'Hello' Block.
 *
 * @Block(
 *   id = "pokemon_block",
 *   admin_label = @Translation("Pokemon block"),
 *   category = @Translation("Pokemon"),
 * )
 */
class PokemonBlock extends BlockBase implements ContainerFactoryPluginInterface
{
	/**
   	* @var \GuzzleHttp\Client
   	*/
    private $http_client;

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  private $config_factory;


  public function __construct(array $configuration, $plugin_id, $plugin_definition, Client $http_client, ConfigFactory $config_factory) 
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->http_client = $http_client;
    $this->config_factory = $config_factory;
  }


  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) 
  {
    return new static(
      $configuration, 
      $plugin_id, $plugin_definition, 
      $container->get('http_client'), 
      $container->get('config.factory'));
  }

    /**
   * {@inheritdoc}
   */
    public function defaultConfiguration() {
      return [
      'count' => 5,
      ];
    }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) 
  {
    $form['count'] = [
    '#type' => 'number', 
    '#title' => $this->t('Amount to display'), 
    '#default_value' => $this->configuration['count'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) 
  {
    if ($form_state->hasAnyErrors()) 
    {
      return;
    }
    else 
    {
      $this->configuration['count'] = $form_state->getValue('count');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() 
  {

    $build = array();
    $resource_name = $this->config_factory->get('pokemon_block.settings')->get('resource');
    $count = $this->configuration['count'];

    for($id = 1; $id <= $count; $id++ ) 
    {
      $build['children'][$id] = [
      '#theme' => $this->getChildTemplate($resource_name), 
      '#data' => $this->fetchChildData($resource_name, $id),
      ];
    }

    return $build;
  }


  private function fetchChildData($resource_name, $id)
  {
    $response = $this->http_client->get("http://pokeapi.co/api/v2/{$resource_name}/{$id}", ['headers' => ['Accept' => 'application/json']]);

    return Json::decode($response->getBody());
  }


  private function getChildTemplate($resource_name)
  {
    return 'pokemon_block_' . $resource_name;
  }
}
