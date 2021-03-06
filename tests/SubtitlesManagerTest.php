<?php
namespace OpenSubtitlesApi\Tests;

use OpenSubtitlesApi\SubtitlesManager;

class SubtitlesManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SubtitlesManager
     */
    protected $subtitleManager;

    protected function setUp()
    {
        $this->subtitleManager = new SubtitlesManager('username', 'password', 'SPA');
    }

    public function testGetSubtitlesReturnsEmptyArray()
    {
        $subtitles = $this->subtitleManager->get('randomFile');
        $this->assertEquals(array(), $subtitles);
    }
}
