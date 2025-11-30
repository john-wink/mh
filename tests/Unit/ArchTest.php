<?php

declare(strict_types=1);

arch()->preset()->php();
// arch()->preset()->strict(); // Disabled - Laravel models need protected methods for scopes
arch()->preset()->security();

arch('controllers')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed();

//
