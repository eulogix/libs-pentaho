<?php

/*
 * This file is part of the Eulogix\Lib package.
 *
 * (c) Eulogix <http://www.eulogix.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace Eulogix\Lib\Pentaho\Tests;

use Eulogix\Cool\Lib\Cool;
use Eulogix\Lib\Pentaho\CartePDIConnector;
use Eulogix\Lib\Pentaho\ConsolePDIConnector;
use Eulogix\Lib\Pentaho\JobExecutionResult;
use Eulogix\Lib\Pentaho\PDIConnector;

/**
 * @author Pietro Baricco <pietro@eulogix.com>
 */

class BaseTest extends \PHPUnit_Framework_TestCase
{

    /**
     * make sure that
     * - kitchen.sh is available in PATH
     * - a repository named libs-pentaho_test_repo is defined in repositories.xml and points to the "kettlerepo" folder in tests
     */

    public function testConsoleConnector()
    {
        $consoleConnector = $this->getConsoleConnector();
        if($consoleConnector->isAvailable()) {

            $params = $consoleConnector->getExpectedJobParameters('test_job');

            $this->assertEquals(1, count($params));

            $this->assertEquals([
                'default' => 'hello',
                'description' => 'Something to log'
            ], $params['something']);


            $this->checkTestJob($consoleConnector);

        } else echo "\nPDI Console connector not available\n";
    }

    public function testCarteConnector()
    {
        $carteConnector = $this->getCarteConnector();
        if($carteConnector->isAvailable()) {
            $this->checkTestJob($carteConnector);
        } else echo "\nPDI Carte connector not available\n";
    }

    /**
     * @param PDIConnector $connector
     */
    private function checkTestJob(PDIConnector $connector) {
        $result = $connector->runJob('test_job', ConsolePDIConnector::DEFAULT_JOB_PATH, [
            'something' => "beautiful"
        ]);

        $this->assertTrue($result->isSuccess());
        $this->assertTrue(preg_match('/TEST_LOG - beautiful/', $result->getOutput()));
    }

    /**
     * @return ConsolePDIConnector
     */
    private function getConsoleConnector() {
        return  new ConsolePDIConnector('pentaho-libs_test_repo', 'fake', 'fake');
    }

    /**
     * @return CartePDIConnector
     */
    private function getCarteConnector() {
        return  new CartePDIConnector('http://pentaho:8080', 'cluster', 'cluster', 'pentaho-libs_test_repo');
    }
}
