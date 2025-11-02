<?php

declare(strict_types=1);

it('can visit the strategies page', function () {
    visit('/strategies')
        ->assertNoSmoke();
});
