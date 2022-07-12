<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomSiteId;
use Piwik\Db;
use Piwik\Common;
use Piwik\CacheId;
use Piwik\Cache as PiwikCache;
use Piwik\Plugin\SettingsProvider;

class CustomSiteId extends \Piwik\Plugin
{
    /**
     * local site id cache
     */
    protected $siteIdCache = array();

    /**
     * eager site id cache (redis). Plugin base class already has a cache, but it's private.
     */
    private $_cache;

    public function __construct($pluginName = false)
    {
        parent::__construct($pluginName);
        $this->_cache = PiwikCache::getEagerCache();
    }

    public function registerEvents()
    {
        return [
            'SitesManager.getImageTrackingCode' => 'updateImageUrl',
            'Tracker.getJavascriptCode' => 'updateJavascriptCode',
            'Tracker.Request.getIdSite' => 'convertSiteId',
        ];
    }

    // modify the generated javascript code with the custom site Id
    public function updateJavascriptCode(&$codeImpl, $parameters)
    {
      $settings = new MeasurableSettings($codeImpl['idSite']);
      $customSiteId = $settings->customSiteId->getValue();
      if ($customSiteId) {
        $codeImpl['idSite'] = $customSiteId;
      }
    }

    // modify the image url with the custom site Id
    public function updateImageUrl(&$piwikUrl, &$urlParams)
    {
      $settings = new MeasurableSettings($urlParams['idsite']);
      $customSiteId = $settings->customSiteId->getValue();
      if ($customSiteId) {
        $urlParams['idsite'] = urlencode($customSiteId);
      }
    }

    // convert custom site id to idSite if needed
    public function convertSiteId(&$idSite, $params){
        // check if the site id is a custom site id
        if ($idSite > 0 || !isset($params['idsite'])) {
            return;
        }

        $cacheKey = 'CustomSiteId-'.$params['idsite'];
        // check if the site id is already in the local cache
        if(isset($this->siteIdCache[$cacheKey])){
            $idSite = $this->siteIdCache[$cacheKey];
            return;
        }
        
        $data = null;
        // check if the site id is in the eager cache (redis)
        if ($this->_cache->contains($cacheKey)) {
            $data = $this->_cache->fetch($cacheKey);
        } 

        if (!$data){
            // retrieve the site id from the database
            $data = $this->readSiteId($params['idsite']);
            // update caches
            $this->_cache->save($cacheKey, $data);
            $this->siteIdCache[$cacheKey] = $data;
        }

        // set the site id
        $idSite = $data;
    }

    protected function readSiteId($customSiteId)
    {
        $sql = "SELECT idsite from `" . Common::prefixTable("site_setting") . "`
                where setting_name = ? and setting_value = ?";
        $siteId = Db::fetchOne($sql, array('custom_site_id', $customSiteId));
        if(empty($siteId)){
            // send the error to handler.onException
            throw new Exception("Custom site id $customSiteId not found");
        }
        if (is_numeric($siteId)) {
            $siteId = intval($siteId);
        }
        return $siteId;
    }
}
