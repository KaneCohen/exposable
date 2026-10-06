<?php

namespace ArjanWestdorp\Exposable\Test\Integration;

use ArjanWestdorp\Exposable\Test\TestCase;

class ShowExposableTest extends TestCase
{
    /**
     * Setup default settings for this test.
     * We use default the authenticated guard.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->useAuthenticatedGuard();
    }

    public function test_a_model_can_be_exposed()
    {
        $attachment = $this->createAttachment();

        $this->get($attachment->exposeUrl())->assertStatus(200)->assertSee('You did it');
    }

    public function test_a_404_will_be_thrown_if_the_model_could_not_be_found()
    {
        $attachment = $this->createAttachment();

        $url = $attachment->exposeUrl();

        $attachment->delete();

        $this->get($url)->assertStatus(404);
    }
}
