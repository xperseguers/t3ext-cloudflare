<?php
namespace Causal\Cloudflare\Solr;

use ApacheSolrForTypo3\Solr\IndexQueue\PageIndexerDataUrlModifier;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Solr url modifier
 *
 * @package Causal\Cloudflare\Solr
 */
class DataUrlModifier extends AbstractDataUrlModifier implements PageIndexerDataUrlModifier, SingletonInterface
{
}
