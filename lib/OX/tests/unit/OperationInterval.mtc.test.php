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

require_once RV_PATH . '/lib/RV.php';

require_once MAX_PATH . '/lib/Max.php';

require_once MAX_PATH . '/lib/OA.php';

require_once OX_PATH . '/lib/OX.php';
require_once LIB_PATH . '/OperationInterval.php';
require_once OX_PATH . '/lib/pear/Date.php';

/**
 * A class for testing the OX_OperationInterval class.
 *
 * @package    OpenX
 * @subpackage TestSuite
 */
class Test_OA_OperationIntveral extends UnitTestCase
{
    /**
     * The constructor method.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * A method to test the checkOperationIntervalValue() method.
     *
     */
    public function testCheckOperationIntervalValue()
    {
        RV::disableErrorHandling();
        for ($i = -1; $i <= 61; $i++) {
            $result = OX_OperationInterval::checkOperationIntervalValue($i);
            if (
                $i == 1 ||
                $i == 2 ||
                $i == 3 ||
                $i == 4 ||
                $i == 5 ||
                $i == 6 ||
                $i == 10 ||
                $i == 12 ||
                $i == 15 ||
                $i == 20 ||
                $i == 30 ||
                $i == 60
            ) {
                $this->assertTrue($result);
            } else {
                $this->assertTrue(PEAR::isError($result));
            }
            $result = OX_OperationInterval::checkOperationIntervalValue(120);
            $this->assertTrue(PEAR::isError($result));
        }
        RV::enableErrorHandling();
    }

    /**
     * A method to test the convertDateRangeToOperationIntervalID() method.
     */
    public function testConvertDateRangeToOperationIntervalID()
    {
        // Test the first operation interval ID range in the week the test was written,
        // using a default operation interval of 60 minutes
        $start = new Date('2004-08-15 00:00:00');
        $end = new Date('2004-08-15 00:59:59');
        $result = OX_OperationInterval::convertDateRangeToOperationIntervalID($start, $end, 60);
        $this->assertEqual($result, 0);
        // Test the same range with a new operation interval of 30 minutes to make
        // sure that the range is recognised as spanning two operation interval IDs
        $result = OX_OperationInterval::convertDateRangeToOperationIntervalID($start, $end, 30);
        $this->assertFalse($result);
        // Test the second operation interval ID range in the week the test was written,
        // using an operation interval of 30 minutes, and a non-definative date range
        $start = new Date('2004-08-15 00:35:00');
        $end = new Date('2004-08-15 00:40:00');
        $result = OX_OperationInterval::convertDateRangeToOperationIntervalID($start, $end, 30);
        $this->assertEqual($result, 1);
    }

    /**
     * A method to test the convertDateToOperationIntervalID() method.
     */
    public function testConvertDateToOperationIntervalID()
    {
        // Test a date in the first operation interval ID in the week before the test was
        // written, using a default operation interval of 60 minutes
        $date = new Date('2004-08-08 00:40:00');
        $result = OX_OperationInterval::convertDateToOperationIntervalID($date, 60);
        $this->assertEqual($result, 0);
        // Test a date in the last operation interval ID in the week before the test was
        // written, using an operation interval of 30 minutes
        $date = new Date('2004-08-14 23:40:00');
        $result = OX_OperationInterval::convertDateToOperationIntervalID($date, 30);
        $this->assertEqual($result, 335);
    }

    /**
     * A method to test the convertDateToOperationIntervalStartAndEndDates() method.
     */
    public function testConvertDateToOperationIntervalStartAndEndDates()
    {
        // Test a date in the first operation interval ID in the week before the test was
        // written, using a default operation interval of 60 minutes
        $date = new Date('2004-08-08 00:40:00');
        $aDates = OX_OperationInterval::convertDateToOperationIntervalStartAndEndDates($date, 60);
        $this->assertEqual($aDates['start'], new Date('2004-08-08 00:00:00'));
        $this->assertEqual($aDates['end'], new Date('2004-08-08 00:59:59'));
        // Test the same date, but with an operation interval of 30 minutes
        $aDates = OX_OperationInterval::convertDateToOperationIntervalStartAndEndDates($date, 30);
        $this->assertEqual($aDates['start'], new Date('2004-08-08 00:30:00'));
        $this->assertEqual($aDates['end'], new Date('2004-08-08 00:59:59'));
    }

    public function testAddOperationIntervalTimeSpan()
    {
        $date = new Date('2004-08-08 00:40:00');
        $nextDate = OX_OperationInterval::addOperationIntervalTimeSpan($date, 60);
        $this->assertEqual($nextDate, new Date('2004-08-08 01:40:00'));
        // Test the same date, but with an operation interval of 30 minutes
        $nextDate = OX_OperationInterval::addOperationIntervalTimeSpan($date, 30);
        $this->assertEqual($nextDate, new Date('2004-08-08 01:10:00'));
    }

    /**
     * A method to test the convertDateToPreviousOperationIntervalStartAndEndDates() method.
     */
    public function testConvertDateToPreviousOperationIntervalStartAndEndDates()
    {
        // Test a date in the first operation interval ID in the week before the test was
        // written, using a default operation interval of 60 minutes
        $date = new Date('2004-08-08 00:40:00');
        $aDates = OX_OperationInterval::convertDateToPreviousOperationIntervalStartAndEndDates($date, 60);
        $this->assertEqual($aDates['start'], new Date('2004-08-07 23:00:00'));
        $this->assertEqual($aDates['end'], new Date('2004-08-07 23:59:59'));
        // Test the same date, but with an operation interval of 30 minutes
        $aDates = OX_OperationInterval::convertDateToPreviousOperationIntervalStartAndEndDates($date, 30);
        $this->assertEqual($aDates['start'], new Date('2004-08-08 00:00:00'));
        $this->assertEqual($aDates['end'], new Date('2004-08-08 00:29:59'));
    }

    /**
     * A method to test the convertDateToNextOperationIntervalStartAndEndDates() method.
     */
    public function testConvertDateToNextOperationIntervalStartAndEndDates()
    {
        // Test a date in the first operation interval ID in the week before the test was
        // written, using a default operation interval of 60 minutes
        $date = new Date('2004-08-08 00:40:00');
        $aDates = OX_OperationInterval::convertDateToNextOperationIntervalStartAndEndDates($date, 60);
        $this->assertEqual($aDates['start'], new Date('2004-08-08 01:00:00'));
        $this->assertEqual($aDates['end'], new Date('2004-08-08 01:59:59'));
        // Test the same date, but with an operation interval of 30 minutes
        $aDates = OX_OperationInterval::convertDateToNextOperationIntervalStartAndEndDates($date, 30);
        $this->assertEqual($aDates['start'], new Date('2004-08-08 01:00:00'));
        $this->assertEqual($aDates['end'], new Date('2004-08-08 01:29:59'));
    }

    /**
     * A method to test the previousOperationIntervalID() method.
     */
    public function testPreviousOperationIntervalID()
    {
        $operationIntervalID = 1;
        $operationInterval = 60;
        $operationIntervalID = OX_OperationInterval::previousOperationIntervalID($operationIntervalID, $operationInterval);
        $this->assertEqual($operationIntervalID, 0);
        $operationIntervalID = OX_OperationInterval::previousOperationIntervalID($operationIntervalID, $operationInterval);
        $this->assertEqual($operationIntervalID, 167);
        $operationIntervalID = OX_OperationInterval::previousOperationIntervalID($operationIntervalID, $operationInterval);
        $this->assertEqual($operationIntervalID, 166);

        $operationIntervalID = 1;
        $operationInterval = 60;
        $intervals = 3;
        $operationIntervalID = OX_OperationInterval::previousOperationIntervalID($operationIntervalID, $operationInterval, $intervals);
        $this->assertEqual($operationIntervalID, 166);
        $operationIntervalID = OX_OperationInterval::previousOperationIntervalID($operationIntervalID, $operationInterval, $intervals);
        $this->assertEqual($operationIntervalID, 163);

        $operationIntervalID = 1;
        $operationInterval = 30;
        $operationIntervalID = OX_OperationInterval::previousOperationIntervalID($operationIntervalID, $operationInterval);
        $this->assertEqual($operationIntervalID, 0);
        $operationIntervalID = OX_OperationInterval::previousOperationIntervalID($operationIntervalID, $operationInterval);
        $this->assertEqual($operationIntervalID, 335);
        $operationIntervalID = OX_OperationInterval::previousOperationIntervalID($operationIntervalID, $operationInterval);
        $this->assertEqual($operationIntervalID, 334);

        $operationIntervalID = 1;
        $operationInterval = 30;
        $intervals = 3;
        $operationIntervalID = OX_OperationInterval::previousOperationIntervalID($operationIntervalID, $operationInterval, $intervals);
        $this->assertEqual($operationIntervalID, 334);
        $operationIntervalID = OX_OperationInterval::previousOperationIntervalID($operationIntervalID, $operationInterval, $intervals);
        $this->assertEqual($operationIntervalID, 331);
    }

    /**
     * A method to test the nextOperationIntervalID() method.
     */
    public function testNextOperationIntervalID()
    {
        $operationIntervalID = 166;
        $operationInterval = 60;
        $operationIntervalID = OX_OperationInterval::nextOperationIntervalID($operationIntervalID, $operationInterval);
        $this->assertEqual($operationIntervalID, 167);
        $operationIntervalID = OX_OperationInterval::nextOperationIntervalID($operationIntervalID, $operationInterval);
        $this->assertEqual($operationIntervalID, 0);
        $operationIntervalID = OX_OperationInterval::nextOperationIntervalID($operationIntervalID, $operationInterval);
        $this->assertEqual($operationIntervalID, 1);

        $operationIntervalID = 166;
        $operationInterval = 60;
        $intervals = 3;
        $operationIntervalID = OX_OperationInterval::nextOperationIntervalID($operationIntervalID, $operationInterval, $intervals);
        $this->assertEqual($operationIntervalID, 1);

        $operationIntervalID = 334;
        $operationInterval = 30;
        $operationIntervalID = OX_OperationInterval::nextOperationIntervalID($operationIntervalID, $operationInterval);
        $this->assertEqual($operationIntervalID, 335);
        $operationIntervalID = OX_OperationInterval::nextOperationIntervalID($operationIntervalID, $operationInterval);
        $this->assertEqual($operationIntervalID, 0);
        $operationIntervalID = OX_OperationInterval::nextOperationIntervalID($operationIntervalID, $operationInterval);
        $this->assertEqual($operationIntervalID, 1);

        $operationIntervalID = 334;
        $operationInterval = 30;
        $intervals = 3;
        $operationIntervalID = OX_OperationInterval::nextOperationIntervalID($operationIntervalID, $operationInterval, $intervals);
        $this->assertEqual($operationIntervalID, 1);
    }

    /**
     * A method to test the checkDatesInSameHour() method.
     */
    public function testCheckDatesInSameHour()
    {
        $start = new Date('2004-09-11 19:00:00');
        $end = new Date('2004-09-11 19:00:00');
        $return = OX_OperationInterval::checkDatesInSameHour($start, $end);
        $this->assertTrue($return);
        $start = new Date('2004-09-11 19:59:59');
        $end = new Date('2004-09-11 19:59:59');
        $return = OX_OperationInterval::checkDatesInSameHour($start, $end);
        $this->assertTrue($return);
        $start = new Date('2004-09-11 19:00:00');
        $end = new Date('2004-09-11 19:00:01');
        $return = OX_OperationInterval::checkDatesInSameHour($start, $end);
        $this->assertTrue($return);
        $start = new Date('2004-09-11 19:00:00');
        $end = new Date('2004-09-11 19:59:59');
        $return = OX_OperationInterval::checkDatesInSameHour($start, $end);
        $this->assertTrue($return);
        $start = new Date('2004-09-11 19:59:59');
        $end = new Date('2004-09-11 20:00:00');
        $return = OX_OperationInterval::checkDatesInSameHour($start, $end);
        $this->assertFalse($return);
        $start = new Date('2004-09-11 18:00:00');
        $end = new Date('2004-09-12 18:00:00');
        $return = OX_OperationInterval::checkDatesInSameHour($start, $end);
        $this->assertFalse($return);
        $start = new Date('2004-08-11 18:00:00');
        $end = new Date('2004-09-11 18:00:00');
        $return = OX_OperationInterval::checkDatesInSameHour($start, $end);
        $this->assertFalse($return);
        $start = new Date('2003-09-11 18:00:00');
        $end = new Date('2004-09-11 18:00:00');
        $return = OX_OperationInterval::checkDatesInSameHour($start, $end);
        $this->assertFalse($return);
    }

    /**
     * A method to test the checkIntervalDates() method.
     */
    public function testCheckIntervalDates()
    {
        $conf = &$GLOBALS['_MAX']['CONF'];
        // Set the operation interval
        $conf['maintenance']['operationInterval'] = 30;
        // Test less than one operation interval
        $start = new Date('2004-09-26 00:00:00');
        $end = new Date('2004-09-26 00:15:00');
        $this->assertFalse(OX_OperationInterval::checkIntervalDates($start, $end));
        // Test more than one operation inteterval
        $start = new Date('2004-09-26 00:00:00');
        $end = new Date('2004-09-26 00:45:00');
        $this->assertFalse(OX_OperationInterval::checkIntervalDates($start, $end));
        // Test exactly one operation interval
        $start = new Date('2004-09-26 00:00:00');
        $end = new Date('2004-09-26 00:29:59');
        $this->assertTrue(OX_OperationInterval::checkIntervalDates($start, $end));
        // Set the operation interval
        $conf['maintenance']['operationInterval'] = 60;
        // Test less than one operation interval/hour
        $start = new Date('2004-09-26 00:00:00');
        $end = new Date('2004-09-26 00:30:00');
        $this->assertFalse(OX_OperationInterval::checkIntervalDates($start, $end));
        // Test more than one operation inteterval/hour
        $start = new Date('2004-09-26 00:00:00');
        $end = new Date('2004-09-26 01:15:00');
        $this->assertFalse(OX_OperationInterval::checkIntervalDates($start, $end));
        // Test exactly one operation interval/hour
        $start = new Date('2004-09-26 00:00:00');
        $end = new Date('2004-09-26 00:59:59');
        $this->assertTrue(OX_OperationInterval::checkIntervalDates($start, $end));
        // Set the operation interval
        $conf['maintenance']['operationInterval'] = 120;
        // Test less than one hour
        $start = new Date('2004-09-26 00:00:00');
        $end = new Date('2004-09-26 00:15:00');
        $this->assertFalse(OX_OperationInterval::checkIntervalDates($start, $end));
        // Test more than one hour
        $start = new Date('2004-09-26 00:00:00');
        $end = new Date('2004-09-26 04:00:00');
        $this->assertFalse(OX_OperationInterval::checkIntervalDates($start, $end));
        // Test exactly one hour
        $start = new Date('2004-09-26 00:00:00');
        $end = new Date('2004-09-26 00:59:59');
        $this->assertTrue(OX_OperationInterval::checkIntervalDates($start, $end));
    }

    /**
     * A method to test the getOperationInterval() method.
     */
    public function testGetOperationInterval()
    {
        $this->assertEqual(
            OX_OperationInterval::getOperationInterval(),
            $GLOBALS['_MAX']['CONF']['maintenance']['operationInterval']
        );
    }

    /**
     * A method to test the secondsPerOperationInterval() method.
     *
     * @TODO Not implemented.
     */
    public function testSecondsPerOperationInterval()
    {
    }

    /**
     * A method to test the operationIntervalsPerDay() method.
     *
     * @TODO Not implemented.
     */
    public function testOperationIntervalsPerDay()
    {
    }

    /**
     * A method to test the operationIntervalsPerWeek() method.
     *
     * @TODO Not implemented.
     */
    public function testOperationIntervalsPerWeek()
    {
    }

    /**
     * A method to test the getIntervalsRemaining() method.
     *
     * @TODO Not implemented.
     */
    public function testGetIntervalsRemaining()
    {
    }
}
