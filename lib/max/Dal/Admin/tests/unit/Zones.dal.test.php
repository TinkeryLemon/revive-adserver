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

require_once MAX_PATH . '/lib/OA/Dal.php';
require_once MAX_PATH . '/lib/max/Dal/tests/util/DalUnitTestCase.php';
require_once MAX_PATH . '/lib/OA/Dll/Zone.php';

Language_Loader::load();

/**
 * A class for testing DAL Zones methods
 *
 * @package    MaxDal
 * @subpackage TestSuite
 *
 */
class MAX_Dal_Admin_ZonesTest extends DalUnitTestCase
{
    public $dalZones;

    /**
     * A sample structure for tests containing websites and zones
     *
     * @var array
     */
    public $_aWebsitesAndZones = [ 1 => [
                                            'name' => 'website 1',
                                            'oac_category_id' => 6510,
                                            'zones' =>
                                                 [
                                                   1 => [ 'zonename' => 'zone 1 on web 1', 'oac_category_id' => 6510 ],
                                                   2 => [ 'zonename' => 'zone 2 on web 1', 'oac_category_id' => 6502 ],
                                                   3 => [ 'zonename' => 'zone 3 on web 1', 'oac_category_id' => 'null' ]
                                                 ]
                                          ],
                                     2 => [
                                            'name' => 'website 2',
                                            'oac_category_id' => 'null',
                                            'zones' =>
                                                 [
                                                   1 => [ 'zonename' => 'zone 1 on web 2', 'oac_category_id' => 6581 ],
                                                   2 => [ 'zonename' => 'zone 2 on web 2', 'oac_category_id' => 6502 ],
                                                 ]
                                          ],
                                     3 => [
                                            'name' => 'website 3',
                                            'oac_category_id' => 6581,
                                            'zones' => 'null'
                                          ]
                             ];

    /**
     * A sample structure for tests containing advertisers and campaigns
     *
     * @var array
     */
    public $_aAdvertisersAndCampaigns = [ 1 => [
                                            'clientname' => 'Advertiser 1',
                                            'campaigns' =>
                                                 [
                                                   1 => [ 'campaignname' => 'campaign 1 adv 1'],
                                                   2 => [ 'campaignname' => 'campaign 2 adv 1'],
                                                 ]
                                          ],
                                          2 => [
                                            'clientname' => 'Advertiser 2',
                                            'campaigns' =>
                                                 [
                                                   1 => [ 'campaignname' => 'campaign 1 adv 2'],
                                                 ]
                                          ]
                                   ];
    /**
     * A sample structure for tests containing categories
     *
     * @var array
     */
    public $_aCategories = [
            65 => [
                'name' => 'Micro processor',
                'subcategories' => [
                    6502 => 'Apple II',
                    6510 => 'Commedore 64',
                    6581 => 'C64 SID'
                ]
            ]
        ];

    /**
     * The constructor method.
     */
    public function __construct()
    {
        parent::__construct();
        Mock::generatePartial(
            'OA_Dll_Zone',
            'PartialMockOA_Dll_Zone',
            ['checkPermissions']
        );
    }

    public function setUp()
    {
        $this->dalZones = OA_Dal::factoryDAL('zones');
    }

    public function tearDown()
    {
        DataGenerator::cleanUp();
    }

    public function testGetZoneByKeyword()
    {
        // Search for zones when none exist.
        $expected = 0;
        $rsZones = $this->dalZones->getZoneByKeyword('foo');
        $rsZones->find();
        $actual = $rsZones->getRowCount();
        $this->assertEqual($actual, $expected);

        $agencyId = 1;
        $rsZones = $this->dalZones->getZoneByKeyword('foo', $agencyId);
        $rsZones->find();
        $actual = $rsZones->getRowCount();
        $this->assertEqual($actual, $expected);

        $affiliateId = 1;
        $rsZones = $this->dalZones->getZoneByKeyword('foo', null, $affiliateId);
        $rsZones->find();
        $actual = $rsZones->getRowCount();
        $this->assertEqual($actual, $expected);

        $affiliateId = 1;
        $rsZones = $this->dalZones->getZoneByKeyword('foo', $agencyId, $affiliateId);
        $rsZones->find();
        $actual = $rsZones->getRowCount();
        $this->assertEqual($actual, $expected);

        // Add a zone (and parent objects)
        $doZones = OA_Dal::factoryDO('zones');
        $doZones->zonename = 'foo';
        $doZones->description = 'bar';
        $zoneId = DataGenerator::generateOne($doZones, true);
        $affiliateId1 = DataGenerator::getReferenceId('affiliates');
        $agencyId1 = DataGenerator::getReferenceId('agency');

        // Add another zone
        $doZones = OA_Dal::factoryDO('zones');
        $doZones->zonename = 'baz';
        $doZones->description = 'quux';
        $zoneId = DataGenerator::generateOne($doZones, true);
        $agencyId2 = DataGenerator::getReferenceId('agency');

        // Search for the zone by string
        $expected = 1;
        $rsZones = $this->dalZones->getZoneByKeyword('foo');
        $rsZones->find();
        $actual = $rsZones->getRowCount();
        $this->assertEqual($actual, $expected);

        $rsZones = $this->dalZones->getZoneByKeyword('bar');
        $rsZones->find();
        $actual = $rsZones->getRowCount();
        $this->assertEqual($actual, $expected);

        // Restrict the search to agency ID
        $expected = 0;
        $rsZones = $this->dalZones->getZoneByKeyword('foo', $agencyId = 0);
        $actual = $rsZones->getRowCount();
        $this->assertEqual($actual, $expected);

        $expected = 1;
        $rsZones = $this->dalZones->getZoneByKeyword('foo', $agencyId1);
        $rsZones->find();
        $actual = $rsZones->getRowCount();
        $this->assertEqual($actual, $expected);

        // Restrict the search to affiliate ID
        $expected = 0;
        $rsZones = $this->dalZones->getZoneByKeyword('foo', $agencyId, $affiliateId = 0);
        $rsZones->find();
        $actual = $rsZones->getRowCount();
        $this->assertEqual($actual, $expected);

        $expected = 1;
        $rsZones = $this->dalZones->getZoneByKeyword('foo', $agencyId1, $affiliateId1);
        $rsZones->find();
        $actual = $rsZones->getRowCount();
        $this->assertEqual($actual, $expected);

        $rsZones = $this->dalZones->getZoneByKeyword('bar', null, $affiliateId1);
        $rsZones->find();
        $actual = $rsZones->getRowCount();
        $this->assertEqual($actual, $expected);


        // Search for zone by zone ID
        $expected = 1;
        $rsZones = $this->dalZones->getZoneByKeyword($zoneId);
        $rsZones->find();
        $actual = $rsZones->getRowCount();
        $this->assertEqual($actual, $expected);
    }

    public function testGetZoneForInvocationForm()
    {
        // Insert a publisher
        $doAffiliates = OA_Dal::factoryDO('affiliates');
        $doAffiliates->website = 'http://example.com';
        $affiliateId = DataGenerator::generateOne($doAffiliates);

        // Insert a zone
        $doZone = OA_Dal::factoryDO('zones');
        $doZone->affiliateid = $affiliateId;
        $doZone->height = 5;
        $doZone->width = 10;
        $doZone->delivery = 1;
        $zoneId = DataGenerator::generateOne($doZone);

        $aExpected = [
            'affiliateid' => $affiliateId,
            'height' => 5,
            'width' => 10,
            'delivery' => 1,
            'website' => 'http://example.com'
        ];
        $aActual = $this->dalZones->getZoneForInvocationForm($zoneId);

        ksort($aExpected);
        ksort($aActual);

        $this->assertEqual($aActual, $aExpected);
    }

    /**
     * Tests getWebsitesAndZonesList method
     *
     */
    public function testGetWebsitesAndZonesList()
    {
        $dalZones = OA_Dal::factoryDAL('zones');

        // Test get all zones on empty database
        $aResult = $dalZones->getWebsitesAndZonesList(null);
        $this->assertTrue(is_array($aResult));
        $this->assertEqual(count($aResult), 0);

        // Create agency
        $doAgency = OA_Dal::factoryDO('agency');
        $doAgency->name = 'Ad Network Manager';
        $agencyId = DataGenerator::generateOne($doAgency);

        // Generate websites and zones
        $aAffiliatesIds = [];
        $aZonesIds = [];
        $this->_createWebsitesAndZones($this->_aWebsitesAndZones, $agencyId, $aAffiliatesIds, $aZonesIds);

        // Create agency 2 - to check if returned zones are only from one agency
        $doAgency->name = 'Ad Network Manager 2';
        $agencyId2 = DataGenerator::generateOne($doAgency);

        // Generate websites and zones for 2nd agency
        $aAffiliatesIds2 = [];
        $aZonesIds2 = [];
        $this->_createWebsitesAndZones($this->_aWebsitesAndZones, $agencyId2, $aAffiliatesIds2, $aZonesIds2);

        // Test get all zones (no categories)
        $aResult = $dalZones->getWebsitesAndZonesList($agencyId);
        $aExpected = $this->_buildExpectedArrayOfWebsitesAndZones($this->_aWebsitesAndZones, $aAffiliatesIds, $aZonesIds);

        $this->assertEqual($aResult, $aExpected);
        $this->assertEqual(count($aResult), 2);                              // We should get 2 websites
        $this->assertEqual(count($aResult[$aAffiliatesIds[1]]['zones']), 3); // First website has 3 zones

        // Generate advertisers and campaigns
        $aClientsIds = [];
        $aCampaignsIds = [];
        $this->_createAdvertisersAndCampaigns($this->_aAdvertisersAndCampaigns, $agencyId, $aClientsIds, $aCampaignsIds);

        // Link campaigns to zones
        $dllZonePartialMock = new PartialMockOA_Dll_Zone($this);
        $dllZonePartialMock->setReturnValue('checkPermissions', true);

        $dllZonePartialMock->linkCampaign($aZonesIds[1][1], $aCampaignsIds[1][1]);
        $dllZonePartialMock->linkCampaign($aZonesIds[1][1], $aCampaignsIds[1][2]);
        $dllZonePartialMock->linkCampaign($aZonesIds[1][1], $aCampaignsIds[2][1]);
        $dllZonePartialMock->linkCampaign($aZonesIds[1][2], $aCampaignsIds[1][1]);
        $dllZonePartialMock->linkCampaign($aZonesIds[1][3], $aCampaignsIds[1][1]);
        $dllZonePartialMock->linkCampaign($aZonesIds[2][1], $aCampaignsIds[2][1]);

        // Test linked
        $aResult = $dalZones->getWebsitesAndZonesList($agencyId, $aCampaignsIds[1][1]);
        $aExpected = $this->_buildExpectedArrayOfWebsitesAndZones($this->_aWebsitesAndZones, $aAffiliatesIds, $aZonesIds);
        // Manually set isLinked
        foreach ($aExpected as $affiliateId => $aWebsite) {
            foreach ($aWebsite['zones'] as $zoneId => $aZone) {
                // If zone is linked with campaign[1][1]
                if ($zoneId == $aZonesIds[1][1] ||
                    $zoneId == $aZonesIds[1][2] ||
                    $zoneId == $aZonesIds[1][3]) {
                    $aExpected[$affiliateId]['zones'][$zoneId]['linked'] = true;
                } else {
                    $aExpected[$affiliateId]['zones'][$zoneId]['linked'] = false;
                }
            }
        }
        $this->assertEqual($aResult, $aExpected);
    }

    /**
     * Tests getZonesList method
     *
     */
    public function CCC_testGetZonesList()
    {
        $dalZones = OA_Dal::factoryDAL('zones');

        // Test get all zones on empty database
        $aResult = $dalZones->getZonesList($agencyId);
        $this->assertTrue(is_array($aResult));
        $this->assertEqual(count($aResult), 0);

        // Create agency
        $doAgency = OA_Dal::factoryDO('agency');
        $doAgency->name = 'Ad Network Manager';
        $agencyId = DataGenerator::generateOne($doAgency);

        // Generate websites and zones
        $aAffiliatesIds = [];
        $aZonesIds = [];
        $this->_createWebsitesAndZones($this->_aWebsitesAndZones, $agencyId, $aAffiliatesIds, $aZonesIds);

        // Test get all zones
        $aResult = $dalZones->getZonesList($agencyId);
        $aExpected = $this->_buildExpectedArrayOfZones($this->_aWebsitesAndZones, $aAffiliatesIds, $aZonesIds);
        $this->assertEqual($aResult, $aExpected);
        $this->assertEqual(count($aResult), 5);      // found 5 zones

        // Generate advertisers and campaigns
        $aClientsIds = [];
        $aCampaignsIds = [];
        $this->_createAdvertisersAndCampaigns($this->_aAdvertisersAndCampaigns, $agencyId, $aClientsIds, $aCampaignsIds);

        // Link campaigns to zones
        $dllZonePartialMock = new PartialMockOA_Dll_Zone($this);
        $dllZonePartialMock->setReturnValue('checkPermissions', true);

        $dllZonePartialMock->linkCampaign($aZonesIds[1][1], $aCampaignsIds[1][1]);
        $dllZonePartialMock->linkCampaign($aZonesIds[1][1], $aCampaignsIds[1][2]);
        $dllZonePartialMock->linkCampaign($aZonesIds[1][1], $aCampaignsIds[2][1]);
        $dllZonePartialMock->linkCampaign($aZonesIds[1][2], $aCampaignsIds[1][1]);
        $dllZonePartialMock->linkCampaign($aZonesIds[1][3], $aCampaignsIds[1][1]);
        $dllZonePartialMock->linkCampaign($aZonesIds[2][1], $aCampaignsIds[2][1]);

        // Test linked
        $aResult = $dalZones->getZonesList($agencyId, $aCampaignsIds[1][1]);
        $aExpected = $this->_buildExpectedArrayOfZones($this->_aWebsitesAndZones, $aAffiliatesIds, $aZonesIds);
        // Manually set isLinked
        foreach ($aExpected as $key => $aZone) {
            // If zone is linked with campaign[1][1]
            if ($aZone['zoneid'] == $aZonesIds[1][1] ||
                $aZone['zoneid'] == $aZonesIds[1][2] ||
                $aZone['zoneid'] == $aZonesIds[1][3]) {
                $aExpected[$key]['islinked'] = true;
            } else {
                $aExpected[$key]['islinked'] = false;
            }
        }
        $this->assertEqual($aResult, $aExpected);

        // Test get 3 zones from first website by search string
        $aResult = $dalZones->getZonesList($agencyId, null, null, 'web 1');
        $aExpected = $this->_buildExpectedArrayOfZones($this->_aWebsitesAndZones, $aAffiliatesIds, $aZonesIds, null, 'web 1');
        $this->assertEqual($aResult, $aExpected);
    }

    /**
     * Add websites and zones to database
     *
     * @param array $aWebsitesAndZones formated as var _aWebsitesAndZones
     * @param int $agencyId agency Id of existing agency
     * @param array $aAffiliatesIds return array with affiliates Ids
     * @param array $aZonesIds      return array with zones Ids
     */
    public function _createWebsitesAndZones($aWebsitesAndZones, $agencyId, &$aAffiliatesIds, &$aZonesIds)
    {
        $doAffiliate = OA_Dal::factoryDO('affiliates');
        $doZones = OA_Dal::factoryDO('zones');
        foreach ($aWebsitesAndZones as $websiteKey => $aWebsite) {
            $doAffiliate->name = $aWebsite['name'];
            $doAffiliate->agencyid = $agencyId;
            $aAffiliatesIds[$websiteKey] = DataGenerator::generateOne($doAffiliate);
            if (is_array($aWebsite['zones'])) {
                foreach ($aWebsite['zones'] as $zoneKey => $aZone) {
                    $doZones->zonename = $aZone['zonename'];
                    $doZones->affiliateid = $aAffiliatesIds[$websiteKey];
                    if (array_key_exists('width', $aZone) && array_key_exists('height', $aZone)) {
                        $doZones->width = $aZone['width'];
                        $doZones->height = $aZone['height'];
                    }
                    if (array_key_exists('delivery', $aZone)) {
                        $doZones->delivery = $aZone['delivery'];
                    }
                    $aZonesIds[$websiteKey][$zoneKey] = DataGenerator::generateOne($doZones);
                }
            }
        }
    }

    /**
     * Add advertiosers and campaigns to database
     *
     * @param array $aAdvertisersAndCampaigns formated as var _aAdvertisersAndCampaigns
     * @param int $agencyId agency Id of existing agency
     * @param array $aClientsIds   return array with clients Ids
     * @param array $aCampaignsIds return array with campaigns Ids
     */
    public function _createAdvertisersAndCampaigns($aAdvertisersAndCampaigns, $agencyId, &$aClientsIds, &$aCampaignsIds)
    {
        $doClients = OA_Dal::factoryDO('clients');
        $doCampaigns = OA_Dal::factoryDO('campaigns');
        foreach ($aAdvertisersAndCampaigns as $advKey => $aAdvertiser) {
            $doClients->clientname = $aAdvertiser['clientname'];
            $doClients->agencyid = $agencyId;
            $aClientsIds[$advKey] = DataGenerator::generateOne($doClients);
            if (is_array($aAdvertiser['campaigns'])) {
                foreach ($aAdvertiser['campaigns'] as $campaignKey => $aCampaign) {
                    $doCampaigns->campaignname = $aCampaign['campaignname'];
                    $doCampaigns->clientid = $aClientsIds[$advKey];
                    $aCampaignsIds[$advKey][$campaignKey] = DataGenerator::generateOne($doCampaigns);
                }
            }
        }
    }

    /**
     * Function returns expected array of websites and zones for given array of websites and zones
     * Sets all statistics to null
     *
     * supplementary function to test getWebsitesAndZonesList
     *
     * @param array $aWebsitesAndZones formated as var _aWebsitesAndZones
     * @param array $aAffiliatesIds array of affiliates Id
     * @param array $aZonesIds array of zones Id
     * @return array
     */
    public function _buildExpectedArrayOfWebsitesAndZones($aWebsitesAndZones, $aAffiliatesIds, $aZonesIds)
    {
        $aZones = $this->_buildExpectedArrayOfZones($aWebsitesAndZones, $aAffiliatesIds, $aZonesIds);
        $aExpected = [];
        foreach ($aZones as $aZone) {
            if (!array_key_exists($aZone['affiliateid'], $aExpected)) {
                $aExpected[$aZone['affiliateid']] =
                    [
                        'name' => $aZone['affiliatename'],
                        'linked' => null,
                        'zones' => [],
                    ];
            }
            $aExpected[$aZone['affiliateid']]['zones'][$aZone['zoneid']] =
                [
                    'name' => $aZone['zonename'],
                    'linked' => $aZone['linked'] ?? null,
                ];
        }
        return $aExpected;
    }

    /**
     * Function returns expected array of zones for given array of websites and zones
     *
     * supplementary function to test getZonesList
     *
     * @param array $aWebsitesAndZones formated as var _aWebsitesAndZones
     * @param array $aAffiliatesIds array of affiliates Id
     * @param array $aZonesIds array of zones Id
     * @param boolean $linked true - return only linked zones, false - return only unlinked zones
     * @param string $searchString string matched to zones/websites names
     * @return array
     */
    public function _buildExpectedArrayOfZones($aWebsitesAndZones, $aAffiliatesIds, $aZonesIds, $searchString = null)
    {
        $aExpected = [];
        foreach ($aWebsitesAndZones as $websiteKey => $aWebsite) {
            if (is_array($aWebsite['zones'])) {
                foreach ($aWebsite['zones'] as $zoneKey => $aZone) {
                    // Add zone to list if zone name or parent website name includes search string
                    if (!isset($searchString) ||
                             (
                                 (stripos($aWebsitesAndZones[$websiteKey]['name'], $searchString) !== false) ||
                              (stripos($aWebsitesAndZones[$websiteKey]['zones'][$zoneKey]['zonename'], $searchString) !== false)
                             )
                         ) {
                        $aExpected[] = [
                                        'zoneid' => $aZonesIds[$websiteKey][$zoneKey],
                                        'zonename' => $aWebsitesAndZones[$websiteKey]['zones'][$zoneKey]['zonename'],
                                        'affiliateid' => $aAffiliatesIds[$websiteKey],
                                        'affiliatename' => $aWebsitesAndZones[$websiteKey]['name'],
                                        'islinked' => null
                                      ];
                    }
                }
            }
        }
        return $aExpected;
    }

    /**
     * Method to test countZones method
     *
     */
    public function testcountZones()
    {
        $dalZones = OA_Dal::factoryDAL('zones');

        // Create agency
        $doAgency = OA_Dal::factoryDO('agency');
        $doAgency->name = 'Ad Network Manager';
        $agencyId = DataGenerator::generateOne($doAgency);

        $result = $dalZones->countZones($agencyId, null, true);
        $this->assertEqual($result, 0);
        $result = $dalZones->countZones($agencyId, null, false);
        $this->assertEqual($result, 0);

        // Generate websites and zones
        $aAffiliatesIds = [];
        $aZonesIds = [];
        $this->_createWebsitesAndZones($this->_aWebsitesAndZones, $agencyId, $aAffiliatesIds, $aZonesIds);

        // Generate advertisers and campaigns
        $aClientsIds = [];
        $aCampaignsIds = [];
        $this->_createAdvertisersAndCampaigns($this->_aAdvertisersAndCampaigns, $agencyId, $aClientsIds, $aCampaignsIds);

        // Create agency 2
        $doAgency->name = 'Ad Network Manager 2';
        $agencyId2 = DataGenerator::generateOne($doAgency);

        // Generate websites and zones for agency 2
        $aAffiliatesIds2 = [];
        $aZonesIds2 = [];
        $this->_createWebsitesAndZones($this->_aWebsitesAndZones, $agencyId2, $aAffiliatesIds2, $aZonesIds2);

        // Generate advertisers and campaigns for agency 2
        $aClientsIds2 = [];
        $aCampaignsIds2 = [];
        $this->_createAdvertisersAndCampaigns($this->_aAdvertisersAndCampaigns, $agencyId2, $aClientsIds2, $aCampaignsIds2);

        $result = $dalZones->countZones($agencyId, null, true);
        $this->assertEqual($result, 0);
        $result = $dalZones->countZones($agencyId, null, false);
        $this->assertEqual($result, 5);

        $result = $dalZones->countZones($agencyId, $aCampaignsIds[1][1], true);
        $this->assertEqual($result, 0);
        $result = $dalZones->countZones($agencyId, $aCampaignsIds[1][1], false);
        $this->assertEqual($result, 5);

        // Link zones to campaigns
        $aFlatZonesIds1 = [$aZonesIds[1][1], $aZonesIds[1][2], $aZonesIds[2][1], $aZonesIds[2][2]];
        $result = $dalZones->linkZonesToCampaign($aFlatZonesIds1, $aCampaignsIds[1][1]);
        $aFlatZonesIds2 = [$aZonesIds[1][1], $aZonesIds[2][1]];
        $result = $dalZones->linkZonesToCampaign($aFlatZonesIds2, $aCampaignsIds[1][2]);

        $result = $dalZones->countZones($agencyId, $aCampaignsIds[1][1], true);
        $this->assertEqual($result, 4);
        $result = $dalZones->countZones($agencyId, $aCampaignsIds[1][1], false);
        $this->assertEqual($result, 1);

        $result = $dalZones->countZones($agencyId, null, true);
        $this->assertEqual($result, 0);
        $result = $dalZones->countZones($agencyId, null, false);
        $this->assertEqual($result, 5);
    }

    /**
     * Method to test linkZonesToCampaign method
     *
     */
    public function _internalTestLinkZonesToCampaign()
    {
        $dalZones = OA_Dal::factoryDAL('zones');

        // Create agency
        $doAgency = OA_Dal::factoryDO('agency');
        $doAgency->name = 'Ad Network Manager';
        $agencyId = DataGenerator::generateOne($doAgency);

        // Generate websites and zones
        $aWebsitesAndZones = $this->_aWebsitesAndZones;
        $aWebsitesAndZones[1]['zones'][1]['width'] = 468;
        $aWebsitesAndZones[1]['zones'][1]['height'] = 60;
        $aWebsitesAndZones[1]['zones'][1]['delivery'] = phpAds_ZoneBanner;
        $aWebsitesAndZones[1]['zones'][2]['width'] = 468;
        $aWebsitesAndZones[1]['zones'][2]['height'] = 60;
        $aWebsitesAndZones[1]['zones'][2]['delivery'] = phpAds_ZoneText;
        $aWebsitesAndZones[1]['zones'][3]['width'] = 468;
        $aWebsitesAndZones[1]['zones'][3]['height'] = 60;
        $aWebsitesAndZones[1]['zones'][3]['delivery'] = MAX_ZoneEmail;
        $aWebsitesAndZones[2]['zones'][1]['width'] = -1;
        $aWebsitesAndZones[2]['zones'][1]['height'] = 60;
        $aWebsitesAndZones[2]['zones'][1]['delivery'] = phpAds_ZoneBanner;
        $aWebsitesAndZones[2]['zones'][2]['width'] = 120;
        $aWebsitesAndZones[2]['zones'][2]['height'] = -1;
        $aWebsitesAndZones[2]['zones'][1]['delivery'] = phpAds_ZoneBanner;

        $aAffiliatesIds = [];
        $aZonesIds = [];
        $this->_createWebsitesAndZones($aWebsitesAndZones, $agencyId, $aAffiliatesIds, $aZonesIds);

        // Generate advertisers and campaigns
        $aClientsIds = [];
        $aCampaignsIds = [];
        $this->_createAdvertisersAndCampaigns($this->_aAdvertisersAndCampaigns, $agencyId, $aClientsIds, $aCampaignsIds);

        // Create agency 2
        $doAgency->name = 'Ad Network Manager 2';
        $agencyId2 = DataGenerator::generateOne($doAgency);

        // Generate websites and zones for agency 2
        $aAffiliatesIds2 = [];
        $aZonesIds2 = [];
        $this->_createWebsitesAndZones($aWebsitesAndZones, $agencyId2, $aAffiliatesIds2, $aZonesIds2);

        // Generate advertisers and campaigns for agency 2
        $aClientsIds2 = [];
        $aCampaignsIds2 = [];
        $this->_createAdvertisersAndCampaigns($this->_aAdvertisersAndCampaigns, $agencyId2, $aClientsIds2, $aCampaignsIds2);

        $aBannerIds = [];

        // Add banners to campaigns
        $doBanners = OA_Dal::factoryDO('banners');
        $doBanners->width = 468;
        $doBanners->height = 60;
        $doBanners->storagetype = 'web';

        $doBanners->name = 'Banner 1 campaign 1 adv 1';
        $doBanners->campaignid = $aCampaignsIds[1][1];
        $aBannerIds[1][1][1] = DataGenerator::generateOne($doBanners);

        $doBanners->name = 'Banner 1 campaign 1 adv 2';
        $doBanners->campaignid = $aCampaignsIds[2][1];
        $aBannerIds[2][1][1] = DataGenerator::generateOne($doBanners);

        $doBanners->width = 120;
        $doBanners->height = 600;

        $doBanners->name = 'Banner 2 campaign 1 adv 1';
        $doBanners->campaignid = $aCampaignsIds[1][1];
        $aBannerIds[1][1][2] = DataGenerator::generateOne($doBanners);


        $doBanners->name = 'Banner 1 campaign 2 adv 1';
        $doBanners->campaignid = $aCampaignsIds[1][2];
        $aBannerIds[1][2][1] = DataGenerator::generateOne($doBanners);

        // One banner for agency 2
        $doBanners->name = 'Banner 1 campaign 1 adv 1 (agency 2)';
        $doBanners->campaignid = $aCampaignsIds2[1][1];
        $aBannerIds2[1][1][1] = DataGenerator::generateOne($doBanners);

        // One text banner
        $doBanners->storagetype = 'txt';
        $doBanners->width = 0;
        $doBanners->height = 0;

        $doBanners->name = 'Banner 3 campaign 1 adv 1';
        $doBanners->campaignid = $aCampaignsIds[1][1];
        $aBannerIds[1][1][3] = DataGenerator::generateOne($doBanners);

        // Empty zones array
        $result = $dalZones->linkZonesToCampaign([], $aCampaignsIds[1][1]);
        $this->assertEqual($result, -1);

        $doPlacementZoneAssoc = OA_Dal::factoryDO('placement_zone_assoc');
        $this->assertEqual($doPlacementZoneAssoc->count(), 0);

        $doAdZoneAssoc = OA_Dal::factoryDO('ad_zone_assoc');
        $doAdZoneAssoc->whereAdd('zone_id <> 0');
        $this->assertEqual($doAdZoneAssoc->count(), 0);

        // One of zones is from different agency
        $result = $dalZones->linkZonesToCampaign([$aZonesIds2[1][1], $aZonesIds[1][1]], $aCampaignsIds[1][1]);
        $this->assertEqual($result, -1);

        $doPlacementZoneAssoc = OA_Dal::factoryDO('placement_zone_assoc');
        $this->assertEqual($doPlacementZoneAssoc->count(), 0);

        $doAdZoneAssoc = OA_Dal::factoryDO('ad_zone_assoc');
        $doAdZoneAssoc->whereAdd('zone_id <> 0');
        $this->assertEqual($doAdZoneAssoc->count(), 0);

        // Add 5 zones to campaign
        $aFlatZonesIds = [$aZonesIds[1][1], $aZonesIds[1][2], $aZonesIds[1][3], $aZonesIds[2][1], $aZonesIds[2][2]];
        $result = $dalZones->linkZonesToCampaign($aFlatZonesIds, $aCampaignsIds[1][1]);
        $this->assertEqual($result, 4);

        $doPlacementZoneAssoc = OA_Dal::factoryDO('placement_zone_assoc');
        $this->assertEqual($doPlacementZoneAssoc->count(), 4);

        $doPlacementZoneAssoc->orderBy('zone_id');
        $doPlacementZoneAssoc->find();
        $counter = 0;
        $aExpectedLinkedZonesIds = [$aZonesIds[1][1], $aZonesIds[1][2], $aZonesIds[2][1], $aZonesIds[2][2]];
        while ($doPlacementZoneAssoc->fetch()) {
            $aPlacementZoneAssoc = $doPlacementZoneAssoc->toArray();
            $this->assertEqual($aPlacementZoneAssoc['zone_id'], $aExpectedLinkedZonesIds[$counter]);
            $this->assertEqual($aPlacementZoneAssoc['placement_id'], $aCampaignsIds[1][1]);
            $counter++;
        }

        // expected result:
        //  $aBannerIds[1][1][1] (468x60)  should be linked to $ZonesIds[1][1], $ZonesIds[2][1]
        //  $aBannerIds[1][1][2] (120x600) should be linked to $ZonesIds[2][2]
        //  $aBannerIds[1][1][3] (txt    ) should be linked to $ZonesIds[1][2]
        $doAdZoneAssoc = OA_Dal::factoryDO('ad_zone_assoc');
        $doAdZoneAssoc->whereAdd('zone_id <> 0');
        $this->assertEqual($doAdZoneAssoc->count(), 4);

        $aExpectedAdZoneAssocs = [
                        ['zone_id' => $aZonesIds[1][1], 'ad_id' => $aBannerIds[1][1][1]],
                        ['zone_id' => $aZonesIds[2][1], 'ad_id' => $aBannerIds[1][1][1]],
                        ['zone_id' => $aZonesIds[2][2], 'ad_id' => $aBannerIds[1][1][2]],
                        ['zone_id' => $aZonesIds[1][2], 'ad_id' => $aBannerIds[1][1][3]]
                ];
        foreach ($aExpectedAdZoneAssocs as $row => $aExpectedAdZoneAssoc) {
            $doAdZoneAssoc = OA_Dal::factoryDO('ad_zone_assoc');
            $doAdZoneAssoc->zone_id = $aExpectedAdZoneAssoc['zone_id'];
            $doAdZoneAssoc->ad_id = $aExpectedAdZoneAssoc['ad_id'];
            $this->assertEqual($doAdZoneAssoc->count(), 1, "found {$doAdZoneAssoc->count()} row ad_zone_assoc for \$aExpectedAdZoneAssocs[{$row}] when expected 1 row");
        }
    }


    public function testLinkZonesToCampaignWithAuditTrail()
    {
        $GLOBALS['_MAX']['CONF']['audit']['enabledForZoneLinking'] = true;
        $this->_internalTestLinkZonesToCampaign();
    }


    public function testLinkZonesToCampaignWithNoAuditTrail()
    {
        $GLOBALS['_MAX']['CONF']['audit']['enabledForZoneLinking'] = false;
        $this->_internalTestLinkZonesToCampaign();
    }


    /**
     * Method to test checkZonesRealm method
     *
     */
    public function test_checkZonesRealm()
    {
        $dalZones = OA_Dal::factoryDAL('zones');

        // Create agency
        $doAgency = OA_Dal::factoryDO('agency');
        $doAgency->name = 'Ad Network Manager';
        $agencyId = DataGenerator::generateOne($doAgency);

        // Generate websites and zones
        $aAffiliatesIds = [];
        $aZonesIds = [];
        $this->_createWebsitesAndZones($this->_aWebsitesAndZones, $agencyId, $aAffiliatesIds, $aZonesIds);

        // Generate advertisers and campaigns
        $aClientsIds = [];
        $aCampaignsIds = [];
        $this->_createAdvertisersAndCampaigns($this->_aAdvertisersAndCampaigns, $agencyId, $aClientsIds, $aCampaignsIds);

        // Create agency 2
        $doAgency->name = 'Ad Network Manager 2';
        $agencyId2 = DataGenerator::generateOne($doAgency);

        // Generate websites and zones for agency 2
        $aAffiliatesIds2 = [];
        $aZonesIds2 = [];
        $this->_createWebsitesAndZones($this->_aWebsitesAndZones, $agencyId2, $aAffiliatesIds2, $aZonesIds2);

        // Generate advertisers and campaigns for agency 2
        $aClientsIds2 = [];
        $aCampaignsIds2 = [];
        $this->_createAdvertisersAndCampaigns($this->_aAdvertisersAndCampaigns, $agencyId2, $aClientsIds2, $aCampaignsIds2);

        // Add banners to campaigns
        $doBanners = OA_Dal::factoryDO('banners');
        $doBanners->width = 468;
        $doBanners->height = 60;
        $doBanners->storagetype = 'web';

        $doBanners->name = 'Banner 1 campaign 1 ag 1';
        $doBanners->campaignid = $aCampaignsIds[1][1];
        $aBannerIds[1][1][1] = DataGenerator::generateOne($doBanners);

        $doBanners->name = 'Banner 1 campaign 1 ag 2';
        $doBanners->campaignid = $aCampaignsIds2[1][1];
        $aBannerIds2[1][1][1] = DataGenerator::generateOne($doBanners);

        // Test empty zones - campaign
        $result = $dalZones->_checkZonesRealm([], $aCampaignsIds[1][1]);
        $this->assertFalse($result);

        // Test one matching zone and one from other agency - campaign
        $result = $dalZones->_checkZonesRealm([$aZonesIds2[1][1], $aZonesIds[1][1]], $aCampaignsIds[1][1]);
        $this->assertFalse($result);

        // Test zones from the same agency - campaign
        $result = $dalZones->_checkZonesRealm([$aZonesIds[1][1], $aZonesIds[1][3], $aZonesIds[2][1]], $aCampaignsIds[1][1]);
        $this->assertTrue($result);

        // Test non existing campaign
        $result = $dalZones->_checkZonesRealm([$aZonesIds[1][1], $aZonesIds[1][3], $aZonesIds[2][1]], -1);
        $this->assertFalse($result);

        // Test empty zones - banner
        $result = $dalZones->_checkZonesRealm([], null, $aBannerIds[1][1][1]);
        $this->assertFalse($result);

        // Test one matching zone and one from other agency - banner
        $result = $dalZones->_checkZonesRealm([$aZonesIds2[1][1], $aZonesIds[1][1]], null, $aBannerIds[1][1][1]);
        $this->assertFalse($result);

        // Test zones from the same agency - banner
        $result = $dalZones->_checkZonesRealm([$aZonesIds[1][1], $aZonesIds[1][3], $aZonesIds[2][1]], null, $aBannerIds[1][1][1]);
        $this->assertTrue($result);

        // Test non existing banner
        $result = $dalZones->_checkZonesRealm([$aZonesIds[1][1], $aZonesIds[1][3], $aZonesIds[2][1]], null, -1);
        $this->assertFalse($result);

        // Test empty zones - campaign and banner
        $result = $dalZones->_checkZonesRealm([], $aCampaignsIds[1][1], $aBannerIds[1][1][1]);
        $this->assertFalse($result);

        // Test one matching zone and one from other agency - campaign and banner
        $result = $dalZones->_checkZonesRealm([$aZonesIds2[1][1], $aZonesIds[1][1]], $aCampaignsIds[1][1], $aBannerIds[1][1][1]);
        $this->assertFalse($result);

        // Test zones from the same agency - campaign and banner
        $result = $dalZones->_checkZonesRealm([$aZonesIds[1][1], $aZonesIds[1][3], $aZonesIds[2][1]], $aCampaignsIds[1][1], $aBannerIds[1][1][1]);
        $this->assertTrue($result);

        // Test non existing campaign and banner
        $result = $dalZones->_checkZonesRealm([$aZonesIds[1][1], $aZonesIds[1][3], $aZonesIds[2][1]], -1, -1);
        $this->assertFalse($result);

        // Test zones from the same agency - mismatching campaign and banner
        $result = $dalZones->_checkZonesRealm([$aZonesIds[1][1], $aZonesIds[1][3], $aZonesIds[2][1]], $aCampaignsIds[1][1], $aBannerIds2[1][1][1]);
        $this->assertFalse($result);
    }

    /**
     * Tests unlinkZonesFromCampaign method
     *
     */
    public function testUnlinkZonesFromCampaign()
    {
        $dalZones = OA_Dal::factoryDAL('zones');

        // Create agency
        $doAgency = OA_Dal::factoryDO('agency');
        $doAgency->name = 'Ad Network Manager';
        $agencyId = DataGenerator::generateOne($doAgency);

        // Generate websites and zones
        $aWebsitesAndZones = $this->_aWebsitesAndZones;
        foreach ($aWebsitesAndZones as $websiteKey => $aWebsite) {
            if (is_array($aWebsite['zones'])) {
                foreach ($aWebsite['zones'] as $zoneKey => $aZone) {
                    $aWebsitesAndZones[$websiteKey]['zones'][$zoneKey]['width'] = 468;
                    $aWebsitesAndZones[$websiteKey]['zones'][$zoneKey]['height'] = 60;
                    $aWebsitesAndZones[$websiteKey]['zones'][$zoneKey]['delivery'] = phpAds_ZoneBanner;
                }
            }
        }

        $aAffiliatesIds = [];
        $aZonesIds = [];
        $this->_createWebsitesAndZones($aWebsitesAndZones, $agencyId, $aAffiliatesIds, $aZonesIds);

        // Generate advertisers and campaigns
        $aClientsIds = [];
        $aCampaignsIds = [];
        $this->_createAdvertisersAndCampaigns($this->_aAdvertisersAndCampaigns, $agencyId, $aClientsIds, $aCampaignsIds);

        $aBannerIds = [];

        // Add banners to campaigns
        $doBanners = OA_Dal::factoryDO('banners');
        $doBanners->width = 468;
        $doBanners->height = 60;

        $doBanners->name = 'Banner 1 campaign 1 adv 1';
        $doBanners->campaignid = $aCampaignsIds[1][1];
        $aBannerIds[1][1][1] = DataGenerator::generateOne($doBanners);

        $doBanners->name = 'Banner 2 campaign 1 adv 1';
        $doBanners->campaignid = $aCampaignsIds[1][1];
        $aBannerIds[1][1][2] = DataGenerator::generateOne($doBanners);

        $doBanners->name = 'Banner 1 campaign 1 adv 2';
        $doBanners->campaignid = $aCampaignsIds[2][1];
        $aBannerIds[2][1][1] = DataGenerator::generateOne($doBanners);

        $doBanners->name = 'Banner 1 campaign 2 adv 1';
        $doBanners->campaignid = $aCampaignsIds[1][2];
        $aBannerIds[1][2][1] = DataGenerator::generateOne($doBanners);

        // Link banners and zones to campaigns
        $aFlatZonesIds1 = [$aZonesIds[1][1], $aZonesIds[1][2], $aZonesIds[1][3], $aZonesIds[2][1], $aZonesIds[2][2]];
        $result = $dalZones->linkZonesToCampaign($aFlatZonesIds1, $aCampaignsIds[1][1]);
        $aFlatZonesIds2 = [$aZonesIds[1][1], $aZonesIds[2][1]];
        $result = $dalZones->linkZonesToCampaign($aFlatZonesIds2, $aCampaignsIds[1][2]);

        // Empty zones array
        $result = $dalZones->unlinkZonesFromCampaign([], $aCampaignsIds[1][1]);
        $this->assertEqual($result, 0);

        // Check if there is still 7 placement_zone_assoc (5 zones linked to $aCampaignsIds[1][1] and 2 zones linked to $aCampaignsIds[1][2])
        $doPlacementZoneAssoc = OA_Dal::factoryDO('placement_zone_assoc');
        $this->assertEqual($doPlacementZoneAssoc->count(), 7);

        // Check if there is still 12 ad_zone_assoc (5*2=10 banners linked to $aCampaignsIds[1][1] and 2*1=2 banners linked to $aCampaignsIds[1][2])
        $doAdZoneAssoc = OA_Dal::factoryDO('ad_zone_assoc');
        $doAdZoneAssoc->whereAdd('zone_id <> 0');
        $this->assertEqual($doAdZoneAssoc->count(), 12);

        // remove all zones from $aCampaignsIds[1][2]
        $result = $dalZones->unlinkZonesFromCampaign($aFlatZonesIds2, $aCampaignsIds[1][2]);
        $this->assertEqual($result, 2);

        $doPlacementZoneAssoc = OA_Dal::factoryDO('placement_zone_assoc');
        $this->assertEqual($doPlacementZoneAssoc->count(), 5);

        $doAdZoneAssoc = OA_Dal::factoryDO('ad_zone_assoc');
        $doAdZoneAssoc->whereAdd('zone_id <> 0');
        $this->assertEqual($doAdZoneAssoc->count(), 10);

        $doPlacementZoneAssoc = OA_Dal::factoryDO('placement_zone_assoc');
        $doPlacementZoneAssoc->placement_id = $aCampaignsIds[1][2];
        $this->assertEqual($doPlacementZoneAssoc->count(), 0);

        // Remove 4 zones from campaign 1
        $aFlatZonesIds3 = [$aZonesIds[1][2], $aZonesIds[1][3], $aZonesIds[2][1], $aZonesIds[2][2]];
        $result = $dalZones->unlinkZonesFromCampaign($aFlatZonesIds3, $aCampaignsIds[1][1]);
        $this->assertEqual($result, 4);

        $doPlacementZoneAssoc = OA_Dal::factoryDO('placement_zone_assoc');
        $this->assertEqual($doPlacementZoneAssoc->count(), 1);       // one zone<->campaing

        $doPlacementZoneAssoc->find();
        $doPlacementZoneAssoc->fetch();
        $aPlacementZoneAssoc = $doPlacementZoneAssoc->toArray();
        $this->assertEqual($aPlacementZoneAssoc['zone_id'], $aZonesIds[1][1]);
        $this->assertEqual($aPlacementZoneAssoc['placement_id'], $aCampaignsIds[1][1]);

        $doAdZoneAssoc = OA_Dal::factoryDO('ad_zone_assoc');
        $doAdZoneAssoc->whereAdd('zone_id <> 0');
        $this->assertEqual($doAdZoneAssoc->count(), 2);              // zone with 2 banners

        // expected result:
        //  $aBannerIds[1][1][1] linked to zone $aZonesIds[1][1]
        //  $aBannerIds[1][1][2] linked to zone $aZonesIds[1][1]

        $aExpectedAdZoneAssocs = [
                        ['zone_id' => $aZonesIds[1][1], 'ad_id' => $aBannerIds[1][1][1]],
                        ['zone_id' => $aZonesIds[1][1], 'ad_id' => $aBannerIds[1][1][2]],
                ];
        foreach ($aExpectedAdZoneAssocs as $aExpectedAdZoneAssoc) {
            $doAdZoneAssoc = OA_Dal::factoryDO('ad_zone_assoc');
            $doAdZoneAssoc->zone_id = $aExpectedAdZoneAssoc['zone_id'];
            $doAdZoneAssoc->ad_id = $aExpectedAdZoneAssoc['ad_id'];
            $this->assertEqual($doAdZoneAssoc->count(), 1, "ad_zone_assoc not found for zone_id={$aExpectedAdZoneAssoc['zone_id']} and ad_id={$aExpectedAdZoneAssoc['ad_id']}");
        }

        // Remove last linked zone
        $result = $dalZones->unlinkZonesFromCampaign([$aZonesIds[1][1]], $aCampaignsIds[1][1]);
        $this->assertEqual($result, 1);

        $doPlacementZoneAssoc = OA_Dal::factoryDO('placement_zone_assoc');
        $this->assertEqual($doPlacementZoneAssoc->count(), 0);

        $doAdZoneAssoc = OA_Dal::factoryDO('ad_zone_assoc');
        $doAdZoneAssoc->whereAdd('zone_id <> 0');
        $this->assertEqual($doAdZoneAssoc->count(), 0);
    }

    public function testCheckZoneLinkedToActiveCampaign()
    {
        $dllZonePartialMock = new PartialMockOA_Dll_Zone();
        $dllZonePartialMock->setReturnValue('checkPermissions', true);

        $doZones = OA_Dal::factoryDO('zones');
        $doZones->width = '468';
        $doZones->height = '60';
        $zoneId = DataGenerator::generateOne($doZones);
        $zoneId2 = DataGenerator::generateOne($doZones);

        $doCampaigns = OA_Dal::factoryDo('campaigns');
        $campaignId1 = DataGenerator::generateOne($doCampaigns);

        $doBanners = OA_Dal::factoryDO('banners');
        $doBanners->width = '468';
        $doBanners->height = '60';
        $doBanners->campaignid = $campaignId1;
        $bannerId = DataGenerator::generateOne($doBanners);

        $doCampaigns = OA_Dal::factoryDo('campaigns');
        $campaignId2 = DataGenerator::generateOne($doCampaigns);

        // Test zone without banners or campaigns
        $this->assertFalse($this->dalZones->checkZoneLinkedToActiveCampaign($zoneId));

        $dllZonePartialMock->linkBanner($zoneId, $bannerId);
        $dllZonePartialMock->linkCampaign($zoneId2, $campaignId2);

        // Test one zone with banner and one with empty campaign
        $this->assertTrue($this->dalZones->checkZoneLinkedToActiveCampaign($zoneId));
        $this->assertTrue($this->dalZones->checkZoneLinkedToActiveCampaign($zoneId2));

        $doCampaigns = OA_Dal::staticGetDO('campaigns', $campaignId2);
        $doCampaigns->active = 'f';
        $doCampaigns->expire_time = '1970-01-01'; // This date expires campaign
        $doCampaigns->update();

        // Test zone with expired campaign
        $this->assertFalse($this->dalZones->checkZoneLinkedToActiveCampaign($zoneId2));
    }

    /**
     * Method to test linkZonesToBanner method
     *
     */
    public function _internalTestLinkZonesToBanner()
    {
        $dalZones = OA_Dal::factoryDAL('zones');

        // Create agency
        $doAgency = OA_Dal::factoryDO('agency');
        $doAgency->name = 'Ad Network Manager';
        $agencyId = DataGenerator::generateOne($doAgency);

        // Generate websites and zones
        $aWebsitesAndZones = $this->_aWebsitesAndZones;
        $aWebsitesAndZones[1]['zones'][1]['width'] = 468;
        $aWebsitesAndZones[1]['zones'][1]['height'] = 60;
        $aWebsitesAndZones[1]['zones'][1]['delivery'] = phpAds_ZoneBanner;
        $aWebsitesAndZones[1]['zones'][2]['width'] = 468;
        $aWebsitesAndZones[1]['zones'][2]['height'] = 60;
        $aWebsitesAndZones[1]['zones'][2]['delivery'] = phpAds_ZoneText;
        $aWebsitesAndZones[1]['zones'][3]['width'] = 468;
        $aWebsitesAndZones[1]['zones'][3]['height'] = 60;
        $aWebsitesAndZones[1]['zones'][3]['delivery'] = MAX_ZoneEmail;
        $aWebsitesAndZones[2]['zones'][1]['width'] = -1;
        $aWebsitesAndZones[2]['zones'][1]['height'] = 60;
        $aWebsitesAndZones[2]['zones'][1]['delivery'] = phpAds_ZoneBanner;
        $aWebsitesAndZones[2]['zones'][2]['width'] = 120;
        $aWebsitesAndZones[2]['zones'][2]['height'] = -1;
        $aWebsitesAndZones[2]['zones'][1]['delivery'] = phpAds_ZoneBanner;

        $aAffiliatesIds = [];
        $aZonesIds = [];
        $this->_createWebsitesAndZones($aWebsitesAndZones, $agencyId, $aAffiliatesIds, $aZonesIds);

        // Generate advertisers and campaigns
        $aClientsIds = [];
        $aCampaignsIds = [];
        $this->_createAdvertisersAndCampaigns($this->_aAdvertisersAndCampaigns, $agencyId, $aClientsIds, $aCampaignsIds);

        // Create agency 2
        $doAgency->name = 'Ad Network Manager 2';
        $agencyId2 = DataGenerator::generateOne($doAgency);

        // Generate websites and zones for agency 2
        $aAffiliatesIds2 = [];
        $aZonesIds2 = [];
        $this->_createWebsitesAndZones($aWebsitesAndZones, $agencyId2, $aAffiliatesIds2, $aZonesIds2);

        // Generate advertisers and campaigns for agency 2
        $aClientsIds2 = [];
        $aCampaignsIds2 = [];
        $this->_createAdvertisersAndCampaigns($this->_aAdvertisersAndCampaigns, $agencyId2, $aClientsIds2, $aCampaignsIds2);

        $aBannerIds = [];

        // Add banners to campaigns
        $doBanners = OA_Dal::factoryDO('banners');
        $doBanners->width = 468;
        $doBanners->height = 60;
        $doBanners->storagetype = 'web';

        $doBanners->name = 'Banner 1 campaign 1 adv 1';
        $doBanners->campaignid = $aCampaignsIds[1][1];
        $aBannerIds[1][1][1] = DataGenerator::generateOne($doBanners);

        $doBanners->name = 'Banner 1 campaign 1 adv 2';
        $doBanners->campaignid = $aCampaignsIds[2][1];
        $aBannerIds[2][1][1] = DataGenerator::generateOne($doBanners);

        $doBanners->width = 120;
        $doBanners->height = 600;

        $doBanners->name = 'Banner 2 campaign 1 adv 1';
        $doBanners->campaignid = $aCampaignsIds[1][1];
        $aBannerIds[1][1][2] = DataGenerator::generateOne($doBanners);


        $doBanners->name = 'Banner 1 campaign 2 adv 1';
        $doBanners->campaignid = $aCampaignsIds[1][2];
        $aBannerIds[1][2][1] = DataGenerator::generateOne($doBanners);

        // One banner for agency 2
        $doBanners->name = 'Banner 1 campaign 1 adv 1 (agency 2)';
        $doBanners->campaignid = $aCampaignsIds2[1][1];
        $aBannerIds2[1][1][1] = DataGenerator::generateOne($doBanners);

        // One text banner
        $doBanners->storagetype = 'txt';
        $doBanners->width = 0;
        $doBanners->height = 0;

        $doBanners->name = 'Banner 3 campaign 1 adv 1';
        $doBanners->campaignid = $aCampaignsIds[1][1];
        $aBannerIds[1][1][3] = DataGenerator::generateOne($doBanners);

        // Empty zones array
        $result = $dalZones->linkZonesToCampaign([], $aBannerIds[1][1][1]);
        $this->assertEqual($result, -1);

        $doPlacementZoneAssoc = OA_Dal::factoryDO('placement_zone_assoc');
        $this->assertEqual($doPlacementZoneAssoc->count(), 0);

        $doAdZoneAssoc = OA_Dal::factoryDO('ad_zone_assoc');
        $doAdZoneAssoc->whereAdd('zone_id <> 0');
        $this->assertEqual($doAdZoneAssoc->count(), 0);

        // One of zones is from different agency
        $result = $dalZones->linkZonesToBanner([$aZonesIds2[1][1], $aZonesIds[1][1]], $aBannerIds2[1][1][1]);
        $this->assertEqual($result, -1);

        $doPlacementZoneAssoc = OA_Dal::factoryDO('placement_zone_assoc');
        $this->assertEqual($doPlacementZoneAssoc->count(), 0);

        $doAdZoneAssoc = OA_Dal::factoryDO('ad_zone_assoc');
        $doAdZoneAssoc->whereAdd('zone_id <> 0');
        $this->assertEqual($doAdZoneAssoc->count(), 0);

        // Add 5 zones to banner 1, only 2 should succeed
        $aFlatZonesIds = [$aZonesIds[1][1], $aZonesIds[1][2], $aZonesIds[1][3], $aZonesIds[2][1], $aZonesIds[2][2]];
        $result = $dalZones->linkZonesToBanner($aFlatZonesIds, $aBannerIds[1][1][1]);
        $this->assertEqual($result, 2);

        // Add 5 zones to banner 2, only 1 should succeed
        $aFlatZonesIds = [$aZonesIds[1][1], $aZonesIds[1][2], $aZonesIds[1][3], $aZonesIds[2][1], $aZonesIds[2][2]];
        $result = $dalZones->linkZonesToBanner($aFlatZonesIds, $aBannerIds[1][1][2]);
        $this->assertEqual($result, 1);

        // Add 5 zones to banner 3, only 1 should succeed
        $aFlatZonesIds = [$aZonesIds[1][1], $aZonesIds[1][2], $aZonesIds[1][3], $aZonesIds[2][1], $aZonesIds[2][2]];
        $result = $dalZones->linkZonesToBanner($aFlatZonesIds, $aBannerIds[1][1][3]);
        $this->assertEqual($result, 1);

        // No campiagn-zone links
        $doPlacementZoneAssoc = OA_Dal::factoryDO('placement_zone_assoc');
        $this->assertEqual($doPlacementZoneAssoc->count(), 0);

        // expected result:
        //  $aBannerIds[1][1][1] (468x60)  should be linked to $ZonesIds[1][1], $ZonesIds[2][1]
        //  $aBannerIds[1][1][2] (120x600) should be linked to $ZonesIds[2][2]
        //  $aBannerIds[1][1][3] (txt    ) should be linked to $ZonesIds[1][2]
        $doAdZoneAssoc = OA_Dal::factoryDO('ad_zone_assoc');
        $doAdZoneAssoc->whereAdd('zone_id <> 0');
        $this->assertEqual($doAdZoneAssoc->count(), 4);

        $aExpectedAdZoneAssocs = [
                        ['zone_id' => $aZonesIds[1][1], 'ad_id' => $aBannerIds[1][1][1]],
                        ['zone_id' => $aZonesIds[2][1], 'ad_id' => $aBannerIds[1][1][1]],
                        ['zone_id' => $aZonesIds[2][2], 'ad_id' => $aBannerIds[1][1][2]],
                        ['zone_id' => $aZonesIds[1][2], 'ad_id' => $aBannerIds[1][1][3]]
                ];
        foreach ($aExpectedAdZoneAssocs as $row => $aExpectedAdZoneAssoc) {
            $doAdZoneAssoc = OA_Dal::factoryDO('ad_zone_assoc');
            $doAdZoneAssoc->zone_id = $aExpectedAdZoneAssoc['zone_id'];
            $doAdZoneAssoc->ad_id = $aExpectedAdZoneAssoc['ad_id'];
            $this->assertEqual($doAdZoneAssoc->count(), 1, "found {$doAdZoneAssoc->count()} row ad_zone_assoc for \$aExpectedAdZoneAssocs[{$row}] when expected 1 row");
        }
    }

    public function testLinkZonesToBannerWithAuditTrail()
    {
        $GLOBALS['_MAX']['CONF']['audit']['enabledForZoneLinking'] = true;
        $this->_internalTestLinkZonesToBanner();
    }


    public function testLinkZonesToBannerWithNoAuditTrail()
    {
        $GLOBALS['_MAX']['CONF']['audit']['enabledForZoneLinking'] = false;
        $this->_internalTestLinkZonesToBanner();
    }

    /**
     * Tests unlinkZonesFromBanner method
     *
     */
    public function testUnlinkZonesFromBanner()
    {
        $dalZones = OA_Dal::factoryDAL('zones');

        // Create agency
        $doAgency = OA_Dal::factoryDO('agency');
        $doAgency->name = 'Ad Network Manager';
        $agencyId = DataGenerator::generateOne($doAgency);

        // Generate websites and zones
        $aWebsitesAndZones = $this->_aWebsitesAndZones;
        foreach ($aWebsitesAndZones as $websiteKey => $aWebsite) {
            if (is_array($aWebsite['zones'])) {
                foreach ($aWebsite['zones'] as $zoneKey => $aZone) {
                    $aWebsitesAndZones[$websiteKey]['zones'][$zoneKey]['width'] = 468;
                    $aWebsitesAndZones[$websiteKey]['zones'][$zoneKey]['height'] = 60;
                    $aWebsitesAndZones[$websiteKey]['zones'][$zoneKey]['delivery'] = phpAds_ZoneBanner;
                }
            }
        }

        $aAffiliatesIds = [];
        $aZonesIds = [];
        $this->_createWebsitesAndZones($aWebsitesAndZones, $agencyId, $aAffiliatesIds, $aZonesIds);

        // Generate advertisers and campaigns
        $aClientsIds = [];
        $aCampaignsIds = [];
        $this->_createAdvertisersAndCampaigns($this->_aAdvertisersAndCampaigns, $agencyId, $aClientsIds, $aCampaignsIds);

        $aBannerIds = [];

        // Add banners to campaigns
        $doBanners = OA_Dal::factoryDO('banners');
        $doBanners->width = 468;
        $doBanners->height = 60;

        $doBanners->name = 'Banner 1 campaign 1 adv 1';
        $doBanners->campaignid = $aCampaignsIds[1][1];
        $aBannerIds[1][1][1] = DataGenerator::generateOne($doBanners);

        $doBanners->name = 'Banner 2 campaign 1 adv 1';
        $doBanners->campaignid = $aCampaignsIds[1][1];
        $aBannerIds[1][1][2] = DataGenerator::generateOne($doBanners);

        $doBanners->name = 'Banner 1 campaign 1 adv 2';
        $doBanners->campaignid = $aCampaignsIds[2][1];
        $aBannerIds[2][1][1] = DataGenerator::generateOne($doBanners);

        $doBanners->name = 'Banner 1 campaign 2 adv 1';
        $doBanners->campaignid = $aCampaignsIds[1][2];
        $aBannerIds[1][2][1] = DataGenerator::generateOne($doBanners);

        // Link banners and zones to campaigns
        $aFlatZonesIds1 = [$aZonesIds[1][1], $aZonesIds[1][2], $aZonesIds[1][3], $aZonesIds[2][1], $aZonesIds[2][2]];
        $result = $dalZones->linkZonesToCampaign($aFlatZonesIds1, $aCampaignsIds[1][1]);
        $aFlatZonesIds2 = [$aZonesIds[1][1], $aZonesIds[2][1]];
        $result = $dalZones->linkZonesToCampaign($aFlatZonesIds2, $aCampaignsIds[1][2]);

        // Empty zones array
        $result = $dalZones->unlinkZonesFromBanner([], $aBannerIds[1][1][1]);
        $this->assertEqual($result, 0);

        // Check if there is still 7 placement_zone_assoc (5 zones linked to $aCampaignsIds[1][1] and 2 zones linked to $aCampaignsIds[1][2])
        $doPlacementZoneAssoc = OA_Dal::factoryDO('placement_zone_assoc');
        $this->assertEqual($doPlacementZoneAssoc->count(), 7);

        // Check if there is still 12 ad_zone_assoc (5*2=10 banners linked to $aCampaignsIds[1][1] and 2*1=2 banners linked to $aCampaignsIds[1][2])
        $doAdZoneAssoc = OA_Dal::factoryDO('ad_zone_assoc');
        $doAdZoneAssoc->whereAdd('zone_id <> 0');
        $this->assertEqual($doAdZoneAssoc->count(), 12);

        // remove all zones from $aBannerIds[1][2][1]
        $result = $dalZones->unlinkZonesFromBanner($aFlatZonesIds2, $aBannerIds[1][2][1]);
        $this->assertEqual($result, 2);

        // still 7, as placement_zone_assoc should be untouched
        $doPlacementZoneAssoc = OA_Dal::factoryDO('placement_zone_assoc');
        $this->assertEqual($doPlacementZoneAssoc->count(), 7);

        $doAdZoneAssoc = OA_Dal::factoryDO('ad_zone_assoc');
        $doAdZoneAssoc->whereAdd('zone_id <> 0');
        $this->assertEqual($doAdZoneAssoc->count(), 10);

        // Remove 4 zones from campaign 1
        $aFlatZonesIds3 = [$aZonesIds[1][2], $aZonesIds[1][3], $aZonesIds[2][1], $aZonesIds[2][2]];
        $result = $dalZones->unlinkZonesFromBanner($aFlatZonesIds3, $aBannerIds[1][1][1]);
        $this->assertEqual($result, 4);
        $result = $dalZones->unlinkZonesFromBanner($aFlatZonesIds3, $aBannerIds[1][1][2]);
        $this->assertEqual($result, 4);

        $doAdZoneAssoc = OA_Dal::factoryDO('ad_zone_assoc');
        $doAdZoneAssoc->whereAdd('zone_id <> 0');
        $this->assertEqual($doAdZoneAssoc->count(), 2);              // zone with 2 banners

        // expected result:
        //  $aBannerIds[1][1][1] linked to zone $aZonesIds[1][1]
        //  $aBannerIds[1][1][2] linked to zone $aZonesIds[1][1]

        $aExpectedAdZoneAssocs = [
                        ['zone_id' => $aZonesIds[1][1], 'ad_id' => $aBannerIds[1][1][1]],
                        ['zone_id' => $aZonesIds[1][1], 'ad_id' => $aBannerIds[1][1][2]],
                ];
        foreach ($aExpectedAdZoneAssocs as $aExpectedAdZoneAssoc) {
            $doAdZoneAssoc = OA_Dal::factoryDO('ad_zone_assoc');
            $doAdZoneAssoc->zone_id = $aExpectedAdZoneAssoc['zone_id'];
            $doAdZoneAssoc->ad_id = $aExpectedAdZoneAssoc['ad_id'];
            $this->assertEqual($doAdZoneAssoc->count(), 1, "ad_zone_assoc not found for zone_id={$aExpectedAdZoneAssoc['zone_id']} and ad_id={$aExpectedAdZoneAssoc['ad_id']}");
        }

        // Remove last linked zone
        $result = $dalZones->unlinkZonesFromBanner([$aZonesIds[1][1]], $aBannerIds[1][1][1]);
        $this->assertEqual($result, 1);
        $result = $dalZones->unlinkZonesFromBanner([$aZonesIds[1][1]], $aBannerIds[1][1][2]);
        $this->assertEqual($result, 1);

        $doAdZoneAssoc = OA_Dal::factoryDO('ad_zone_assoc');
        $doAdZoneAssoc->whereAdd('zone_id <> 0');
        $this->assertEqual($doAdZoneAssoc->count(), 0);
    }
}
