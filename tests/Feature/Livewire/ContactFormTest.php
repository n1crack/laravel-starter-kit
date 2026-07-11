<?php

declare(strict_types=1);

use App\Livewire\ContactForm;
use Livewire\Livewire;

it('renders on the home page', function (): void {
    $response = $this->get(route('home'));

    $response->assertOk()->assertSeeLivewire(ContactForm::class);
});

it('sends a message', function (): void {
    Livewire::test(ContactForm::class)
        ->set('name', 'Ayşe Yılmaz')
        ->set('email', 'ayse@example.com')
        ->set('message', 'Merhaba!')
        ->call('submit')
        ->assertSet('sent', true)
        ->assertSet('name', '')
        ->assertHasNoErrors();
});

it('validates required fields', function (): void {
    Livewire::test(ContactForm::class)
        ->call('submit')
        ->assertSet('sent', false)
        ->assertHasErrors(['name', 'email', 'message']);
});

it('validates the email format', function (): void {
    Livewire::test(ContactForm::class)
        ->set('name', 'Ayşe Yılmaz')
        ->set('email', 'not-an-email')
        ->set('message', 'Merhaba!')
        ->call('submit')
        ->assertHasErrors(['email']);
});
