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


class Alma_Installments_Helper_FeePlansHelper extends Alma_Installments_Helper_Config
{
    const ONE_INSTALLMENT_KEY = "general_1_0_0";
    const ALMA_BASE_FEE_PLANS_PATH = 'payment/alma_installments/base_fee_plans';
    const ALMA_PNX_CONFIG_FEE_PLANS_PATH = 'payment/alma_installments/pnx_config';
    const MIN_PURCHASE_AMOUNT_KEY = 'min_purchase_amount';
    const MIN_DISPLAY_KEY = 'custom_min_purchase_amount';
    const MAX_DISPLAY_KEY = 'custom_max_purchase_amount';
    const MAX_PURCHASE_AMOUNT_KEY = 'max_purchase_amount';
    const FEE_PLAN_ENABLE_KEY = 'enable';

    private $almaClient;
    /**
     * @var Alma_Installments_Helper_Logger
     */
    private $logger;
    /**
     * @var Alma_Installments_Helper_Functions
     */
    private $functionsHelper;
    /**
     * @var Mage_Core_Helper_String
     */
    private $unserializeArrayHelper;
    /**
     * @var Alma_Installments_Helper_AlmaClient
     */
    private $almaHelper;

    public function __construct()
    {
        $this->almaHelper = Mage::helper('alma/AlmaClient');
        $this->almaClient = $this->almaHelper->getDefaultClient();
        $this->logger = Mage::helper('alma/logger')->getLogger();
        $this->functionsHelper = Mage::helper('alma/Functions');
        $this->unserializeArrayHelper = Mage::helper('core/unserializeArray');
    }
    /**
     * @return array
     */
    public function getFeePlansFromAlmaApi()
    {
        $almaFeePlans=[];
        try {
            $almaFeePlans = $this->almaClient->merchants->feePlans();
        } catch (\Exception $e) {
            $this->logger->error('Get Alma Fee plans :', [$e->getMessage()]);
        }
        $this->almaHelper->saveMerchantId($almaFeePlans);
        $this->unsetOneInstallmentPlan($almaFeePlans);
        return $almaFeePlans;
    }

    /**
     * @return array
     */
    public function getFormattedFeePlansFromAlmaApi()
    {
        $almaFeePlans = $this->getFeePlansFromAlmaApi();
        $formattedFeePlans=[];

        foreach ($almaFeePlans as $almaFeePlan) {
            // remove no allowed plan
            if($almaFeePlan->allowed){
                $formattedFeePlans[$almaFeePlan->id] = $this->formatApiFeePlan($almaFeePlan);
            }
        }
        // Save to config
        try {
            $this->saveBaseFeePlansToConfig($formattedFeePlans);
        } catch (Mage_Core_Exception $e) {
            $this->logger->error('Save Fee plans to config DB error ',[$e->getMessage()]);
        }
        return $formattedFeePlans;
    }

    /**
     * @return array
     */
    public function formatApiFeePlan($almaFeePlan)
    {
        return [
            'allowed' => $almaFeePlan->allowed,
            'enable' => 0,
            'pnx_label' => $this->getPlanLabel($almaFeePlan->id),
            'kind' => $almaFeePlan->kind,
            'id' => $almaFeePlan->id,
            'installments_count'=>$almaFeePlan->installments_count,
            'deferred_days'=> $almaFeePlan->deferred_days,
            'deferred_months'=> $almaFeePlan->deferred_months,
            'min_purchase_amount'=> $almaFeePlan->min_purchase_amount,
            'custom_min_purchase_amount'=> $almaFeePlan->min_purchase_amount,
            'custom_max_purchase_amount'=> $almaFeePlan->max_purchase_amount,
            'max_purchase_amount'=>  $almaFeePlan->max_purchase_amount,
            'deferred_trigger_limit_days'=> $almaFeePlan->deferred_trigger_limit_days,
            'merchant_fee_variable'=> $almaFeePlan->merchant_fee_variable,
        ];
    }

    /**
     * @param Alma\API\Endpoints\Results\Eligibility[] $almaFeePlans (work in reference)
     * @return void
     */
    public function unsetOneInstallmentPlan(&$almaFeePlans)
    {
        foreach ($almaFeePlans as $index => $almaFeePlan) {
            if ($almaFeePlan->getPlanKey() == self::ONE_INSTALLMENT_KEY){
                unset($almaFeePlans[$index]);
            }
        }
    }

    /**
     * @param array $almaFormattedFeePlans
     * @return void
     * @throws Mage_Core_Exception
     */
    public function saveBaseFeePlansToConfig($almaFormattedFeePlans)
    {
        try {
            $this->unserializeArrayHelper->unserialize(serialize($almaFormattedFeePlans));
        } catch (Exception $e) {
            Mage::throwException(Mage::helper('adminhtml')->__('Serialized data is incorrect'));
        }
        Mage::getConfig()->saveConfig(self::ALMA_BASE_FEE_PLANS_PATH,serialize($almaFormattedFeePlans));
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getBaseFeePlansFromConfig()
    {
        return $this->unserializeArrayHelper->unserialize($this->get(self::ALMA_BASE_FEE_PLANS_PATH));
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getFeePlansConfigFromBackOffice()
    {
        return $this->unserializeArrayHelper->unserialize($this->get(self::ALMA_PNX_CONFIG_FEE_PLANS_PATH));
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getEnabledFeePlansConfigFromBackOffice()
    {
        $enabledFeePlans = [];
        $feePlans = $this->getFeePlansConfigFromBackOffice();
        foreach ($feePlans as $feePlan) {
            if($feePlan['enable']){
                $enabledFeePlans[$feePlan['id']] = $feePlan;
            }
        }
        return $enabledFeePlans;
    }

    /**
     * @param array $configFeePlans
     * @param array $formFeePlans
     * @return array
     */
    public function mergeConfigAndFormFeePlan($configFeePlans, $formFeePlans)
    {
        foreach ($configFeePlans as $feePlanKey=> $almaFeePlan) {
            if(isset($formFeePlans[$feePlanKey])){
            $configFeePlans[$feePlanKey] = $this->includeFormDataInFeePlan($almaFeePlan,$formFeePlans[$feePlanKey]);
            }
         }
        return $configFeePlans;
    }

    /**
     * @param array $almaFeePlan
     * @param array $formFeePlan
     * @return array
     */
    private function includeFormDataInFeePlan($almaFeePlan,$formFeePlan)
    {
        $formKeysToInclude = $this->getFormKeysToInclude();

        foreach ($formKeysToInclude as $key){
            // If no form data - default value
            if($formFeePlan[$key]){
                $almaFeePlan[$key]=$formFeePlan[$key];
            }
        }
        return $almaFeePlan;
    }

    /**
     * @return string[]
     */
    private function getFormKeysToInclude(){
        return [
            self::MIN_DISPLAY_KEY,
            self::MAX_DISPLAY_KEY,
            self::FEE_PLAN_ENABLE_KEY
        ];
    }

    /**
     * @return string[]
     */
    private function getFeePlansPriceKeysToConvertForDisplay(){
        return [
            self::MIN_PURCHASE_AMOUNT_KEY,
            self::MIN_DISPLAY_KEY,
            self::MAX_PURCHASE_AMOUNT_KEY,
            self::MAX_DISPLAY_KEY
        ];
    }

    /**
     * @return string[]
     */
    private function getFeePlansPriceKeysToConvertForSave(){
        return [
            self::MIN_DISPLAY_KEY,
            self::MAX_DISPLAY_KEY
        ];
    }

    /**
     * @param array $almaFeePlans
     * @return array
     */
    public function convertFeePlansPricesForSave($almaFeePlans)
    {
       $priceKeys = $this->getFeePlansPriceKeysToConvertForSave();
        foreach ($almaFeePlans as $planKey => $feePlan) {
            foreach ($priceKeys as $priceKey) {
                $price = $this->functionsHelper->priceToCents($feePlan[$priceKey]);
                $almaFeePlans[$planKey][$priceKey] = $price;
            }
        }
        return $almaFeePlans;
    }

    /**
     * @param $almaFeePlans
     * @return array
     */
    public function convertFeePlansPricesForDisplay($almaFeePlans)
    {
        $priceKeys = $this->getFeePlansPriceKeysToConvertForDisplay();
        foreach ($almaFeePlans as $planKey => $feePlan) {
            foreach ($priceKeys as $priceKey) {
                $price = $this->functionsHelper->priceFromCents($feePlan[$priceKey]);
                $almaFeePlans[$planKey][$priceKey] = $price;
            }
        }
        return $almaFeePlans;
    }

    /**
     * @param $planKey
     * @return string
     */
    public function getPlanLabel($planKey)
    {
        preg_match('/general_([\d]{1,2})_([\d]{1,2})_([\d]{1,2})/', $planKey, $matches);
        $installments = $matches[1];
        $deferred = $matches[2];

        $stringForLabel = '%s installments payment';
        $valueForLabel = $installments;

        if ($deferred > 0 ){
            $stringForLabel = 'Payment in %s days';
            $valueForLabel = $deferred;
        }
        return sprintf($stringForLabel,$valueForLabel);
    }

    /**
     * @param array $feePlans
     * @return array
     */
    public function validateFeePlanMinAndMaxCustomAmount($feePlans)
    {
        foreach ($feePlans as $feePlanKey => $feePlan) {
            if(
                $feePlan[self::MIN_DISPLAY_KEY]<$feePlan[self::MIN_PURCHASE_AMOUNT_KEY]||
                $feePlan[self::MIN_DISPLAY_KEY] >= $feePlan[self::MAX_DISPLAY_KEY])
            {
                Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('adminhtml')->__('Warning %s custom Min display amount is incorrect',$feePlan['pnx_label']));
                $feePlans[$feePlanKey][self::MIN_DISPLAY_KEY] = $feePlan[self::MIN_PURCHASE_AMOUNT_KEY];
            }
            if(
                $feePlan[self::MAX_DISPLAY_KEY]>$feePlan[self::MAX_PURCHASE_AMOUNT_KEY]||
                $feePlan[self::MAX_DISPLAY_KEY] <= $feePlan[self::MIN_DISPLAY_KEY])
            {
                Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('adminhtml')->__('Warning %s custom Max display amount is incorrect',$feePlan['pnx_label']));
                $feePlans[$feePlanKey][self::MAX_DISPLAY_KEY] = $feePlan[self::MAX_PURCHASE_AMOUNT_KEY];
            }
        }
        return $feePlans;
    }


    /**
     * @return string
     */
    public function getEnableFeePlansForBadge()
    {
        $enableFeePlansFromBackOffice = $this->getEnabledFeePlansConfigFromBackOffice();
        $plansForBadge = [];
        foreach ($enableFeePlansFromBackOffice as $plan) {
            $plansForBadge[] = $this->formatPlanForBadge($plan);
        }
        return json_encode($plansForBadge);
    }

    /**
     * @param $plan
     * @return array
     */
    public function formatPlanForBadge($plan)
    {
        return [
            'installmentsCount'=> $plan['installments_count'],
            'deferredDays'=> $plan['deferred_days'],
            'deferredMonths'=> $plan['deferred_months'],
            'minAmount'=> $plan['custom_min_purchase_amount'],
            'maxAmount'=> $plan['custom_max_purchase_amount'],
        ];
    }

}