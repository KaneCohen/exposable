<?php

namespace ArjanWestdorp\Exposable\Test\Unit\Traits;

use ArjanWestdorp\Exposable\Traits\Exposable;
use ArjanWestdorp\Exposable\Test\TestCase;
use Illuminate\Database\Eloquent\Model;

class ModelSettingsTest extends TestCase
{
    public function test_absent_properties_use_config_and_overrides_stay_out_of_attributes()
    {
        $this->config('default-guard', 'authenticated');
        $this->config('lifetime', 25);
        $first = new SettingsModel();
        $second = new SettingsModel();
        $this->assertSame(['authenticated', 25], $first->settings());
        $first->setExposableGuard('unauthenticated')->setExposableLifetime(null);
        $this->assertSame(['unauthenticated', null], $first->settings());
        $this->assertSame(['authenticated', 25], $second->settings());
        $this->assertSame([], $first->getAttributes());
        $this->assertSame([], $first->getDirty());
        $this->assertFalse(property_exists($first, 'exposableGuard'));
        $this->assertFalse(property_exists($first, 'exposableLifetime'));
        // Saving must not attempt to write runtime settings as DB columns.
        $attachment = $this->createAttachment()->setExposableGuard('authenticated')->setExposableLifetime(5);
        $attachment->name = 'changed.pdf';
        $this->assertTrue($attachment->save());
        $this->assertArrayNotHasKey('exposableGuard', $attachment->getAttributes());
        $this->assertArrayNotHasKey('exposableLifetime', $attachment->getAttributes());
    }

    public function test_declared_null_and_declared_values_take_precedence_over_defaults()
    {
        $this->config('default-guard', 'authenticated');
        $this->config('lifetime', 25);
        $null = new NullSettingsModel();
        $valued = new DeclaredSettingsModel();
        $this->assertSame([null, null], $null->settings());
        $this->assertSame(['unauthenticated', 0], $valued->settings());
        $null->setExposableGuard('authenticated')->setExposableLifetime(15);
        $valued->setExposableGuard('authenticated')->setExposableLifetime(null);
        $this->assertSame(['authenticated', 15], $null->settings());
        $this->assertSame(['authenticated', null], $valued->settings());
        $this->assertSame([null, null], (new NullSettingsModel())->settings());
        $this->assertSame(['unauthenticated', 0], (new DeclaredSettingsModel())->settings());
    }

    public function test_clones_retain_settings_and_then_override_independently()
    {
        $original = (new SettingsModel())->setExposableGuard('authenticated')->setExposableLifetime(5);
        $clone = clone $original;
        $this->assertSame(['authenticated', 5], $clone->settings());
        $clone->setExposableGuard('unauthenticated')->setExposableLifetime(null);
        $this->assertSame(['unauthenticated', null], $clone->settings());
        $this->assertSame(['authenticated', 5], $original->settings());
    }

    public function test_plain_objects_get_defaults_without_dynamic_properties()
    {
        $plain = new PlainSettingsModel();
        $this->assertSame([config('exposable.default-guard'), config('exposable.lifetime')], $plain->settings());
        $plain->setExposableGuard('authenticated')->setExposableLifetime(1);
        $this->assertSame(['authenticated', 1], $plain->settings());
        $this->assertSame([], get_object_vars($plain));
    }
}

class SettingsModel extends Model
{
    use Exposable;
    public function expose() { return response('fixture'); }
    public function settings() { return [$this->getExposableGuard(), $this->getExposableLifetime()]; }
}
class NullSettingsModel extends SettingsModel
{
    protected $exposableGuard = null;
    protected $exposableLifetime = null;
}
class DeclaredSettingsModel extends SettingsModel
{
    protected string $exposableGuard = 'unauthenticated';
    protected int $exposableLifetime = 0;
}
class PlainSettingsModel
{
    use Exposable;
    public function expose() { return 'fixture'; }
    public function settings() { return [$this->getExposableGuard(), $this->getExposableLifetime()]; }
}
