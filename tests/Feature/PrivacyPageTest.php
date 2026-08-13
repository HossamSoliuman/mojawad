<?php

/** Meta reads this URL before letting the Facebook app leave development mode. */
it('serves the privacy policy to guests', function () {
    $this->get(route('privacy'))
        ->assertOk()
        ->assertSeeText(__('Privacy policy'))
        ->assertSeeText(__('What we collect'))
        ->assertSeeText(__('Contact'))
        ->assertSee('mojawad.org@gmail.com', false);
});
