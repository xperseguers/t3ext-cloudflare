<?php
namespace Causal\Cloudflare\Solr;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class DataUrlModifierBackwardCompatibility
 *
 * @package Causal\Cloudflare\Solr
 */
class DataUrlModifierBackwardCompatibility extends AbstractDataUrlModifier implements \Tx_Solr_IndexQueuePageIndexerDataUrlModifier, SingletonInterface
{
}
