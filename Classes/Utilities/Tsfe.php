<?php

namespace Causal\Cloudflare\Utilities;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;

use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;

/**
 * Inspired by and credits go to
 * https://bitbucket.org/99grad/nnhelpers/src/master/Classes/Utilities/Tsfe.php
 * https://bitbucket.org/99grad/nnhelpers/src/master/Classes/Utilities/Page.php
 */
class Tsfe implements SingletonInterface {

    private function t3Version() {
        if (class_exists(\TYPO3\CMS\Core\Information\Typo3Version::class)) {
            $version = GeneralUtility ::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class);

            return $version -> getMajorVersion();
        }

        return floor(VersionNumberUtility ::convertVersionNumberToInteger(TYPO3_version) / 1000000);
    }


    /**
     *    $GLOBALS['TSFE'] holen.
     *    Falls nicht vorhanden (weil im BE) initialisieren.
     *    ```
     *    \nn\t3::Tsfe()->get()
     *    ```
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    public function get() {
        if (!isset($GLOBALS['TSFE'])) $this -> init();

        return $GLOBALS['TSFE'];
    }

    /**
     *    $GLOBALS['TSFE']->cObj holen.
     *    ```
     *    \nn\t3::Tsfe()->cObj()
     *    ```
     *
     * @return \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    public function cObj() {
        if (!$GLOBALS['TSFE']) $this -> init();
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        if ($cObj = $configurationManager -> getContentObject()) return $cObj;
        if ($cObj = $GLOBALS['TSFE'] -> cObj) return $cObj;

        return GeneralUtility ::makeInstance(ContentObjectRenderer::class);
    }

    /**
     *    $GLOBALS['TSFE']->cObj->data holen.
     *    ```
     *    \nn\t3::Tsfe()->cObjData();            => array mit DB-row des aktuellen Content-Elementes
     *    \nn\t3::Tsfe()->cObjData('uid');    => uid des aktuellen Content-Elements
     *    ```
     *
     * @return mixed
     */
    public function cObjData($var = NULL) {
        $cObj = $this -> cObj();
        if (!$cObj) return FALSE;

        return $var ? $cObj -> data[$var] : $cObj -> data;
    }

    /**
     *    Ein TypoScript-Object rendern.
     *    FrÃ¼her: `$GLOBALS['TSFE']->cObj->cObjGetSingle()`
     *    ```
     *    \nn\t3::Tsfe()->cObjGetSingle('IMG_RESOURCE', ['file'=>'bild.jpg', 'file.'=>['maxWidth'=>200]] )
     *    ```
     */
    public function cObjGetSingle($type = '', $conf = []) {
        return $this -> cObj() -> cObjGetSingle($type, $conf);
    }

    /**
     *    Das TSFE initialisieren.
     *    Funktioniert auch im Backend-Context, z.B. innerhalb eines
     *    Backend-Moduls oder Scheduler-Jobs.
     *    ```
     *    \nn\t3::Tsfe()->init();
     *    ```
     */
    public function init($pid = 0, $typeNum = 0) {

        if (!$pid) $pid = \nn\t3 ::Page() -> getPid();

        try {
            if ($this -> t3Version() < 8) {

                $GLOBALS['TSFE'] = GeneralUtility ::makeInstance(TypoScriptFrontendController::class, $GLOBALS['TYPO3_CONF_VARS'], $pid, $typeNum);

                if (!is_object($GLOBALS['TT'])) {
                    $GLOBALS['TT'] = new \TYPO3\CMS\Core\TimeTracker\TimeTracker;
                    $GLOBALS['TT'] -> start();
                }

                $GLOBALS['TSFE'] -> config['config']['language'] = $_GET['L'];
                $GLOBALS['TSFE'] -> id = $pid;
                $GLOBALS['TSFE'] -> connectToDB();
                $GLOBALS['TSFE'] -> initLLVars();
                $GLOBALS['TSFE'] -> initFEuser();
                $GLOBALS['TSFE'] -> sys_page = \nn\t3 ::injectClass(\TYPO3\CMS\Frontend\Page\PageRepository::class);
                $GLOBALS['TSFE'] -> sys_page -> init($GLOBALS['TSFE'] -> showHiddenPage);

                $page = $GLOBALS['TSFE'] -> sys_page -> getPage($pid);
                $GLOBALS['TSFE'] -> getPageAndRootline();
                $GLOBALS['TSFE'] -> initTemplate();
                $GLOBALS['TSFE'] -> forceTemplateParsing = 1;
                $GLOBALS['TSFE'] -> tmpl -> start($GLOBALS['TSFE'] -> rootLine);
                $GLOBALS['TSFE'] -> getConfigArray();

                $GLOBALS['TSFE'] -> inituserGroups();
                $GLOBALS['TSFE'] -> connectToDB();
                //$GLOBALS['TSFE']->determineId();

            } else if ($this -> t3Version() < 9) {

                if (!is_object($GLOBALS['TT'])) {
                    $GLOBALS['TT'] = new \TYPO3\CMS\Core\TimeTracker\TimeTracker;
                    $GLOBALS['TT'] -> start();
                }

                $GLOBALS['TSFE'] = GeneralUtility ::makeInstance(TypoScriptFrontendController::class, $GLOBALS['TYPO3_CONF_VARS'], $pid, $typeNum);
                $GLOBALS['TSFE'] -> connectToDB();
                $GLOBALS['TSFE'] -> initFEuser();
                try {
                    $GLOBALS['TSFE'] -> determineId();
                } catch (\Exception $e) {
                    // Seite evtl. gesperrt? Rootpage pid nicht ermittelt?
                }
                $GLOBALS['TSFE'] -> initTemplate();
                $GLOBALS['TSFE'] -> getConfigArray();

                if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility ::isLoaded('realurl')) {
                    $rootline = \TYPO3\CMS\Backend\Utility\BackendUtility ::BEgetRootLine($pid);
                    $host = \TYPO3\CMS\Backend\Utility\BackendUtility ::firstDomainRecord($rootline);
                    $_SERVER['HTTP_HOST'] = $host;
                }

                $GLOBALS['TSFE'] -> newCObj();

            } else if ($this -> t3Version() < 10) {

                $context = GeneralUtility ::makeInstance(Context::class);

                $GLOBALS['TSFE'] = GeneralUtility ::makeInstance(TypoScriptFrontendController::class, $GLOBALS['TYPO3_CONF_VARS'], $pid, $typeNum);
                $GLOBALS['TSFE'] -> connectToDB();
                $GLOBALS['TSFE'] -> initFEuser();
                $GLOBALS['TSFE'] -> initUserGroups(); // ?
                $GLOBALS['TSFE'] -> checkAlternativeIdMethods();  // ?

                try {
                    $GLOBALS['TSFE'] -> determineId();
                } catch (\Exception $e) {
                    // Seite evtl. gesperrt? Rootpage pid nicht ermittelt?
                }

                $GLOBALS['TSFE'] -> tmpl = GeneralUtility ::makeInstance(TemplateService::class, $context);
                $GLOBALS['TSFE'] -> getConfigArray();

                $objectManager = GeneralUtility ::makeInstance(ObjectManager::class);
                $configurationManager = $objectManager -> get(ConfigurationManagerInterface::class);
                $GLOBALS['TSFE'] -> tmpl -> setup = $configurationManager -> getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

                $contentObject = GeneralUtility ::makeInstance(ContentObjectRenderer::class);
                $contentObject -> start([]);
                $GLOBALS['TSFE'] -> cObj = $contentObject;

                $GLOBALS['TSFE'] -> settingLanguage();
                $GLOBALS['TSFE'] -> settingLocale();
                $this -> bootstrap();

            } else if ($this -> t3Version() < 11) {

                $request = $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory ::fromGlobals();
                $site = $request -> getAttribute('site');
                if (!$site instanceof Site) {
                    $sites = GeneralUtility ::makeInstance(SiteFinder::class) -> getAllSites();
                    $site = reset($sites);
                    if (!$site instanceof Site) {
                        $site = new NullSite();
                    }
                }
                $language = $request -> getAttribute('language');
                if (!$language instanceof SiteLanguage) {
                    $language = $site -> getDefaultLanguage();
                }

                $id = $request -> getQueryParams()['id'] ?? $request -> getParsedBody()['id'] ?? $site -> getRootPageId();
                $type = $request -> getQueryParams()['type'] ?? $request -> getParsedBody()['type'] ?? '0';

                $GLOBALS['TSFE'] = GeneralUtility ::makeInstance(
                    TypoScriptFrontendController::class,
                    GeneralUtility ::makeInstance(Context::class),
                    $site,
                    $language,
                    $request -> getAttribute('routing', new PageArguments((int)$id, (string)$type, []))
                );

                $GLOBALS['TSFE'] -> sys_page = GeneralUtility ::makeInstance(PageRepository::class);
                $GLOBALS['TSFE'] -> tmpl = GeneralUtility ::makeInstance(TemplateService::class);

                $objectManager = GeneralUtility ::makeInstance(ObjectManager::class);
                $configurationManager = $objectManager -> get(ConfigurationManagerInterface::class);
                $GLOBALS['TSFE'] -> tmpl -> setup = $configurationManager -> getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

                $contentObject = GeneralUtility ::makeInstance(ContentObjectRenderer::class);
                $contentObject -> start([]);
                $GLOBALS['TSFE'] -> cObj = $contentObject;

                $GLOBALS['TSFE'] -> settingLanguage();
                $GLOBALS['TSFE'] -> settingLocale();

            } else if ($this -> t3Version() < 12) {

                $request = $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory ::fromGlobals();
                $site = $request -> getAttribute('site');
                if (!$site instanceof Site) {
                    $sites = GeneralUtility ::makeInstance(SiteFinder::class) -> getAllSites();
                    $site = reset($sites);
                    if (!$site instanceof Site) {
                        $site = new NullSite();
                    }
                }
                $language = $request -> getAttribute('language');
                if (!$language instanceof SiteLanguage) {
                    $language = $site -> getDefaultLanguage();
                }

                $id = $request -> getQueryParams()['id'] ?? $request -> getParsedBody()['id'] ?? $site -> getRootPageId();
                $type = $request -> getQueryParams()['type'] ?? $request -> getParsedBody()['type'] ?? '0';

                $feUserAuth = GeneralUtility ::makeInstance(\TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication::class);

                $GLOBALS['TSFE'] = GeneralUtility ::makeInstance(
                    TypoScriptFrontendController::class,
                    GeneralUtility ::makeInstance(Context::class),
                    $site,
                    $language,
                    $request -> getAttribute('routing', new PageArguments((int)$id, (string)$type, [])),
                    $feUserAuth
                );

                $GLOBALS['TSFE'] -> sys_page = GeneralUtility ::makeInstance(PageRepository::class);
                $GLOBALS['TSFE'] -> tmpl = GeneralUtility ::makeInstance(TemplateService::class);

                $objectManager = GeneralUtility ::makeInstance(ObjectManager::class);
                $configurationManager = $objectManager -> get(ConfigurationManagerInterface::class);
                $GLOBALS['TSFE'] -> tmpl -> setup = $configurationManager -> getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

                $contentObject = GeneralUtility ::makeInstance(ContentObjectRenderer::class);
                $contentObject -> start([]);
                //$contentObject->cObjectDepthCounter = 100;

                $GLOBALS['TSFE'] -> cObj = $contentObject;

                // Typo3 v11 -> v11.5 LTS: `settingLanguage()` ist private geworden
                if (is_callable([$GLOBALS['TSFE'], 'settingLanguage'])) {
                    $GLOBALS['TSFE'] -> settingLanguage();
                }

                $userSessionManager = \TYPO3\CMS\Core\Session\UserSessionManager ::create('FE');
                $userSession = $userSessionManager -> createAnonymousSession();
                $GLOBALS['TSFE'] -> fe_user = $userSession;

            }
        } catch (\Exception $e) {

            // Wenn das TSFE nicht initialisiert werden konnte, liegt das evtl. daran dass:
            // - die Root-Seite gesperrt ist
            // - die Root-Seite nur fÃ¼r fe_user zugÃ¤nglich ist

        }
    }

    /**
     * @param $pid      int
     * @param $params   array
     * @param $absolute bool
     *
     * @return mixed
     */
    public function getPageUrl(int $pid, array $params = [], bool $absolute = FALSE) {
        if (!(TYPO3_MODE === 'FE' && $GLOBALS['TSFE'] && $GLOBALS['TSFE'] -> id)) {
            $this -> init($pid);
        }

        return $this -> cObj() -> typolink_URL([
            'parameter' => $pid,
            'forceAbsoluteUrl' => ($absolute === TRUE),
            'additionalParams' => GeneralUtility ::implodeArrayForUrl(NULL, $params),
        ]);
    }
}
