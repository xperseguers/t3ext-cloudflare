<?php

namespace Causal\Cloudflare\Hooks;

use Causal\Cloudflare\Solr\AbstractDataUrlModifier;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class TypoLinkPostProcessor
 * @package Causal\Cloudflare\Hooks
 */
class TypoLinkPostProcessor
{
    /**
     * @var TypoScriptFrontendController
     */
    protected $tsfe = null;

    public function __construct()
    {
        $this->tsfe = $GLOBALS['TSFE'];
    }

    /**
     * Modify last typolink if it's solr request and requested page is shortcut
     * @param array $params
     * @param ContentObjectRenderer $pObj
     */
    public function processTypoLink(array $params, ContentObjectRenderer $pObj)
    {
        // If it's getting link on it self
        // Also this hook is called only on
        if (isset($_GET[AbstractDataUrlModifier::GET_PARAM_NAME])
            && isset($_SERVER['HTTP_X_TX_SOLR_IQ'])
            && ((int)$params['parameter'] === (int)$this->tsfe->page['uid'])
        ) {
            $urlInfo = parse_url($pObj->lastTypoLinkUrl);
            $pObj->lastTypoLinkUrl = sprintf(
                '%s%s%s=%s',
                $pObj->lastTypoLinkUrl,
                empty($urlInfo['query']) ? '?' : '&',
                AbstractDataUrlModifier::GET_PARAM_NAME,
                $_GET[AbstractDataUrlModifier::GET_PARAM_NAME]
            );
        }
    }
}
