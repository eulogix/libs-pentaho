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

    public function testLib()
    {
        $c = $this->getConnector();

        $params = $c->getExpectedParameters('test_job');

        $this->assertEquals(1, count($params));
        $this->assertEquals([
            'default' => '/tmp/test_job_tmp_file',
            'description' => 'The name of the temporary file to create'
        ], $params['file_name']);

        $tempFile = tempnam(sys_get_temp_dir(),'TMP');
        $c->runJob('test_job', PDIConnector::DEFAULT_JOB_PATH, [
            'file_name' => $tempFile
        ]);

        $this->assertEquals('test', file_get_contents($tempFile));
        @unlink($tempFile);

    }

    /**
     * @return PDIConnector
     */
    private function getConnector() {
        return  new PDIConnector('pentaho-libs_test_repo', 'fake', 'fake');
    }
}
