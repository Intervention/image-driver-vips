<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Tests\Unit\Modifiers;

use Intervention\Image\Colors\Profile;
use Intervention\Image\Drivers\Vips\Modifiers\ProfileModifier;
use Intervention\Image\Drivers\Vips\Tests\BaseTestCase;
use Intervention\Image\Exceptions\ColorException;

class ProfileModifierTest extends BaseTestCase
{
    public function testApply(): void
    {
        $image = $this->readTestImage('tile.png'); // no profile

        try {
            $profile = $image->profile();
        } catch (ColorException) {
            $profile = null;
        }

        // assert no profile
        $this->assertNull($profile);

        // add profile
        $image->modify(new ProfileModifier(
            new Profile(
                file_get_contents($this->getTestResourcePath('profile.icc'))
            )
        ));

        $this->assertInstanceOf(Profile::class, $image->profile());
    }
}
