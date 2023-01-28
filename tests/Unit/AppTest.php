<?php

use PHPUnit\Framework\TestCase;
use Unic\App;

class AppTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        $this->app = new App();
    }

    public function testCreateApp(): void
    {
        $this->assertInstanceOf(App::class, $this->app);
    }

    public function testSetConfig(): void
    {
        $this->app->set('view_engine', 'twig');
        $this->assertSame($this->app->get('view_engine'), 'twig');
    }
}
