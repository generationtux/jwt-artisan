<?php

namespace GenTux\Jwt\Tests\Support;

use GenTux\Jwt\GetsJwtToken;

/**
 * Test class that uses the GetsJwtToken trait for testing purposes
 */
class GetsJwtTokenTestClass
{
    use GetsJwtToken;

    private $mockRequest;
    private $mockDriver;

    public function setMockRequest($request)
    {
        $this->mockRequest = $request;
    }

    public function setMockDriver($driver)
    {
        $this->mockDriver = $driver;
    }

    private function makeRequest()
    {
        return $this->mockRequest;
    }

    private function makeDriver()
    {
        return $this->mockDriver;
    }
}
