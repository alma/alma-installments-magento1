<?php
/**
 * 2018 Alma / Nabla SAS
 *
 * THE MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and
 * to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @author    Alma / Nabla SAS <contact@getalma.eu>
 * @copyright 2018 Alma / Nabla SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 *
 */

class Alma_Installments_Helper_Availability extends Mage_Core_Helper_Abstract
{
    /**
     * @var Alma_Installments_Helper_Config
     */
    private $config;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Alma_Installments_Helper_AlmaClient
     */
    private $almaClient;
    /**
     * @var Alma_Installments_Helper_Logger
     */
    private $logger;

    public function __construct() {
        $this->config = Mage::helper('alma/config');
        // $this->storeManager = $storeManager;
        $this->almaClient = Mage::helper('alma/AlmaClient');
        $this->logger = Mage::helper('alma/logger');
    }

    public function isAvailable()
    {
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        // $countryCode = ??

        return $this->isFullyConfigured() &&
            $this->isAvailableForCurrency($currencyCode) /*&&
            $this->isAvailableForCountry($countryCode)*/
            ;
    }

    public function isAvailableForCurrency($currencyCode)
    {
        // We only support Euros at the moment
        return $currencyCode === 'EUR';
    }

    public function isAvailableForCountry($countryCode)
    {
        // We only support France at the moment
        return $countryCode === 'FR';
    }

    public function isFullyConfigured()
    {
        return $this->config->isFullyConfigured();
    }

    public function canConnectToAlma($mode = null, $apiKey = null)
    {
        if ($mode) {
            $modes = [$mode];
        } else {
            $modes = ['live', 'test'];
        }

        $keys = [
            'live' => $this->config->getLiveKey(),
            'test' => $this->config->getTestKey(),
        ];

        if ($apiKey) {
            if ($mode) {
                $keys[$mode] = $apiKey;
            } else {
                $keys = [
                    'live' => $apiKey,
                    'test' => $apiKey,
                ];
            }
        }

        foreach ($modes as $mode) {
            $key = $keys[$mode];

            $alma = $this->almaClient->createInstance($key, $mode);
            if (!$alma) {
                $this->logger->error("Could not create API client to check {$mode} API key");
                return false;
            }

            try {
                $alma->merchants->me();
            } catch (\Alma\API\RequestError $e) {
                if ($e->response && $e->response->responseCode === 401) {
                    return false;
                } else {
                    $this->logger->error("Error while connecting to Alma API: {$e->getMessage()}");
                    return false;
                }
            }
        }

        return true;
    }
}
