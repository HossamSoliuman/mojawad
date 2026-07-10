<?php

it('renders the mihrab mark with the emerald arch and gold soundwave', function () {
    $this->blade('<x-brand.mark :size="48" />')
        ->assertSee('brand-mark', false)
        ->assertSee('data-variant="full"', false)
        ->assertSee('#1DB954', false)
        ->assertSee('#E9C46A', false);
});

it('falls back to the simplified mark at small sizes', function () {
    $this->blade('<x-brand.mark :size="22" />')
        ->assertSee('data-variant="mini"', false);
});

it('shows the brand mark in the public nav island', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('brand-mark', false);
});
