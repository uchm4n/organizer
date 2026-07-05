<?php

test('the welcome page is rendered as a plain blade api landing page', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee(config('app.name'))
        ->assertSee('stateless JSON API')
        ->assertDontSee('data-page', false);
});
