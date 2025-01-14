<?php

/*
+---------------------------------------------------------------------------+
| Revive Adserver                                                           |
| http://www.revive-adserver.com                                            |
|                                                                           |
| Copyright: See the COPYRIGHT.txt file.                                    |
| License: GPLv2 or later, see the LICENSE.txt file.                        |
+---------------------------------------------------------------------------+
*/

require_once MAX_PATH . '/lib/OA/Maintenance/Priority/AdServer/Task/ECPMforContract.php';
require_once MAX_PATH . '/lib/OA/Maintenance/Priority/AdServer/Task/ECPMCommon.php';

/**
 * A class for testing the OA_Maintenance_Priority_AdServer_Task_ECPMforContract class.
 *
 * @package    OpenXMaintenance
 * @subpackage TestSuite
 */
class Test_OA_Maintenance_Priority_AdServer_Task_ECPMforContract extends UnitTestCase
{
    private $mockDal;

    public const IDX_ADS = OA_Maintenance_Priority_AdServer_Task_ECPMforContract::IDX_ADS;
    public const IDX_WEIGHT = OA_Maintenance_Priority_AdServer_Task_ECPMforContract::IDX_WEIGHT;
    public const IDX_ZONES = OA_Maintenance_Priority_AdServer_Task_ECPMforContract::IDX_ZONES;

    public const ALPHA = OA_Maintenance_Priority_AdServer_Task_ECPMforContract::ALPHA;

    /**
     * The constructor method.
     */
    public function __construct()
    {
        parent::__construct();
        Mock::generate(
            'OA_Dal_Maintenance_Priority',
            $this->mockDal = 'MockOA_Dal_Maintenance_Priority' . rand()
        );
        Mock::generatePartial(
            'OA_Maintenance_Priority_AdServer_Task_ECPMforContract',
            'PartialMock_OA_Maintenance_Priority_AdServer_Task_ECPMforContract',
            ['_getDal', '_factoryDal', 'calculateCampaignEcpm'
            ]
        );
    }

    /**
     * Used for asserting that two arrays are equal even if
     * both arrays contain floats. All values are first rounded
     * to the given precision before comparing
     */
    public function assertEqualsFloatsArray($aExpected, $aChecked, $precision = 4)
    {
        $this->assertTrue(is_array($aExpected));
        $this->assertTrue(is_array($aChecked));
        $aExpected = $this->roundArray($aExpected, $precision);
        $aChecked = $this->roundArray($aChecked, $precision);
        $this->assertEqual($aExpected, $aChecked);
    }

    public function roundArray($arr, $precision)
    {
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $arr[$k] = $this->roundArray($v, $precision);
            } else {
                $arr[$k] = round($v, $precision);
            }
        }
        return $arr;
    }

    /**
     * A method to test the prepareCampaignsParameters() method.
     */
    public function DISABLED_testPrepareCampaignsParameters()
    {
        $aCampaignsInfo = [];
        $aEcpm = [];
        $aCampaignsDeliveredImpressions = [];
        $aExpAdsEcpmPowAlpha = [];
        $aExpZonesEcpmPowAlphaSums = [];
        $aAdsGoals = [];

        ///////////////////////////////////////////////////
        // one ad linked to one zone
        ///////////////////////////////////////////////////
        $aCampaignsInfo[$campaignId1 = 1] = [
            self::IDX_ADS => [
                $adId1 = 1 => [
                    self::IDX_WEIGHT => 1,
                    self::IDX_ZONES => [$zoneId1 = 1],
                ]
            ],
        ];
        $aAdsGoals[$zoneId1][$adId1] = 100;
        $aEcpm[$campaignId1] = 0.5;
        $aExpAdsEcpmPowAlpha[$adId1] = pow(0.5, self::ALPHA);
        $aExpZonesEcpmPowAlphaSums[$zoneId1] = self::MU_2 * $aAdsGoals[$zoneId1][$adId1] *
            $aExpAdsEcpmPowAlpha[$adId1];
        $aExpZonesGuaranteedImpr[$zoneId1] = self::MU_1 * $aAdsGoals[$zoneId1][$adId1];

        ///////////////////////////////////////////////////
        // one ad linked to two zones
        ///////////////////////////////////////////////////
        $aCampaignsInfo[$campaignId2 = 2] = [
            self::IDX_ADS => [
                $adId2 = 2 => [
                    self::IDX_WEIGHT => 1,
                    self::IDX_ZONES => [$zoneId2 = 2, $zoneId3 = 3],
                ]
            ],
        ];
        $aAdsGoals[$zoneId2][$adId2] = 200;
        $aAdsGoals[$zoneId3][$adId2] = 200;
        $aEcpm[$campaignId2] = 0.6;
        $aExpAdsEcpmPowAlpha[$adId2] = pow(0.6, self::ALPHA);
        $aExpZonesEcpmPowAlphaSums[$zoneId2] = self::MU_2 * $aAdsGoals[$zoneId2][$adId2] *
            $aExpAdsEcpmPowAlpha[$adId2];
        $aExpZonesGuaranteedImpr[$zoneId2] = self::MU_1 * $aAdsGoals[$zoneId2][$adId2];
        $aExpZonesEcpmPowAlphaSums[$zoneId3] = self::MU_2 * $aAdsGoals[$zoneId3][$adId2] *
            $aExpAdsEcpmPowAlpha[$adId2];
        $aExpZonesGuaranteedImpr[$zoneId3] = self::MU_1 * $aAdsGoals[$zoneId3][$adId2];

        // Partially mock the OA_Maintenance_Priority_AdServer_Task_ECPMforContract class
        $oDal = new $this->mockDal($this);
        $oDal->setReturnReference('getRequiredAdZoneImpressions', $aAdsGoals);

        $oEcpm = new PartialMock_OA_Maintenance_Priority_AdServer_Task_ECPMforContract($this);
        $oEcpm->setReturnReference('_getDal', $oDal);
        $oEcpm->__construct();
        foreach ($aEcpm as $campId => $ecpm) {
            $oEcpm->setReturnValue('calculateCampaignEcpm', $ecpm, [$campId, '*']);
        }

        // Test
        $oEcpm->prepareCampaignsParameters($aCampaignsInfo);

        $this->assertEqual($aExpAdsEcpmPowAlpha, $oEcpm->aAdsEcpmPowAlpha);
        $this->assertEqual($aExpZonesEcpmPowAlphaSums, $oEcpm->aZonesEcpmPowAlphaSums);
        $this->assertEqual($aExpZonesGuaranteedImpr, $oEcpm->aZonesGuaranteedImpressionsSums);
    }

    /**
     * A method to test the calculateDeliveryProbabilities() method.
     */
    public function DISABLED_testCalculateDeliveryProbabilities()
    {
        $aExpAdZonesProbabilities = [];
        $aZonesAvailableImpressions = [];
        $aZoneAdGoal = [];

        ///////////////////////////////////////////////////
        // one ad linked to one zone
        ///////////////////////////////////////////////////
        $aCampaignsInfo[$campaignId1 = 1] = [
            self::IDX_ADS => [
                $adId1 = 1 => [
                    self::IDX_WEIGHT => 1,
                    self::IDX_ZONES => [$zoneId1 = 1],
                ]
            ],
        ];
        $aEcpm[$campaignId1] = 0.1;
        $aZoneAdGoal[$zoneId1][$adId1] = $G = 20;
        $aZonesAvailableImpressions[$zoneId1] = $M = 10;
        // probability equal 1.0 because more required than available impressions
        $aExpAdZonesProbabilities[$adId1][$zoneId1] = 1.0;

        ///////////////////////////////////////////////////
        // one ad linked to two zones
        ///////////////////////////////////////////////////
        $aCampaignsInfo[$campaignId2 = 2] = [
            self::IDX_ADS => [
                $adId2 = 2 => [
                    self::IDX_WEIGHT => 1,
                    self::IDX_ZONES => [$zoneId2 = 2, $zoneId3 = 3],
                ]
            ],
        ];
        $aEcpm[$campaignId2] = 0.6;
        // as many impressions in first zone as in second, sum equal to required minimum
        $aZoneAdGoal[$zoneId2][$adId2] = $G1 = 200;
        $aZoneAdGoal[$zoneId3][$adId2] = $G2 = 200;
        $aZonesAvailableImpressions[$zoneId2] = $M1 = 100;
        $aZonesAvailableImpressions[$zoneId3] = $M2 = 100;

        // simple case
        $aExpAdZonesProbabilities[$adId2][$zoneId2] = 1.0;
        $aExpAdZonesProbabilities[$adId2][$zoneId3] = 1.0;

        ///////////////////////////////////////////////////
        // one ad linked to one zone (undersubscribed)
        ///////////////////////////////////////////////////
        $aCampaignsInfo[$campaignId3 = 3] = [
            self::IDX_ADS => [
                $adId3 = 3 => [
                    self::IDX_WEIGHT => 1,
                    self::IDX_ZONES => [$zoneId4 = 4],
                ]
            ],
        ];
        $aEcpm[$campaignId4] = 0.1;
        $aZoneAdGoal[$zoneId4][$adId3] = $G = 5;
        $aZonesAvailableImpressions[$zoneId4] = $M = 10;
        // probability not set because campaign is undersubscribed
        // the stanndad MPE should calculate the probability in this case
        // $aExpAdZonesProbabilities[$adId3][$zoneId4] = as calculated by MPE;

        ///////////////////////////////////////////////////
        // two ads with different eCPM linked to same zone
        ///////////////////////////////////////////////////
        $aCampaignsInfo[$campaignId4 = 4] = [
            self::IDX_ADS => [
                $adId4 = 4 => [
                    self::IDX_WEIGHT => 1,
                    self::IDX_ZONES => [$zoneId5 = 5],
                ]
            ],
        ];
        $aCampaignsInfo[$campaignId5 = 5] = [
            self::IDX_ADS => [
                $adId5 = 5 => [
                    self::IDX_WEIGHT => 1,
                    self::IDX_ZONES => [$zoneId5 = 5],
                ]
            ],
        ];
        $aEcpm[$campaignId4] = $ecpm1 = 0.3;
        $aEcpm[$campaignId5] = $ecpm2 = 0.6;
        $aZoneAdGoal[$zoneId5][$adId4] = $G = 100;
        $aZoneAdGoal[$zoneId5][$adId5] = $G = 100;
        $aZonesAvailableImpressions[$zoneId5] = $M = 100;

        $a = self::MU_1 * $G;
        $b = self::MU_2 * $G;
        $ecpmZone = $b * pow($ecpm1, self::ALPHA) + $b * pow($ecpm2, self::ALPHA);
        $p1 = $b * pow($ecpm1, self::ALPHA) / $ecpmZone;
        $p2 = $b * pow($ecpm2, self::ALPHA) / $ecpmZone;

        $aExpAdZonesProbabilities[$adId4][$zoneId5] = $a / $M + (1 - 2 * $a / $M) * $p1;
        $aExpAdZonesProbabilities[$adId5][$zoneId5] = $a / $M + (1 - 2 * $a / $M) * $p2;

        /////////////////////////////////////////////////////////

        $oDal = new $this->mockDal($this);
        $oDal->setReturnReference('getRequiredAdZoneImpressions', $aZoneAdGoal);

        // Partially mock the OA_Maintenance_Priority_AdServer_Task_ECPM class
        $oEcpm = new PartialMock_OA_Maintenance_Priority_AdServer_Task_ECPMforContract($this);
        foreach ($aEcpm as $campId => $ecpm) {
            $oEcpm->setReturnValue('calculateCampaignEcpm', $ecpm, [$campId, '*']);
        }
        $oEcpm->setReturnReference('_getDal', $oDal);
        $oEcpm->__construct();
        $oEcpm->aZonesAvailableImpressions = $aZonesAvailableImpressions;
        $oEcpm->prepareCampaignsParameters($aCampaignsInfo);

        // Test
        $aAdZonesProbabilities = $oEcpm->calculateDeliveryProbabilities($aCampaignsInfo);
        $this->assertEqualsFloatsArray($aExpAdZonesProbabilities, $aAdZonesProbabilities);
    }
}
